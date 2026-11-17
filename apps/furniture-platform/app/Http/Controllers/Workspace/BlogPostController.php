<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Support\Website\BlogPostRevisions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class BlogPostController
{
    public function publicIndex(Request $request): JsonResponse
    {
        abort_unless(config('furniture.blog_posts_enabled'), 404);
        $data = $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:1000000']]);
        $page = DB::table('storefront_blog_posts')->where('published', true)->orderByDesc('created_at')->orderBy('slug')
            ->paginate(12, ['slug', 'title', 'summary'], 'page', $data['page'] ?? 1);

        return response()->json(['data' => $page->items(), 'current_page' => $page->currentPage(), 'last_page' => $page->lastPage()])->header('Cache-Control', 'no-store');
    }

    public function publicShow(string $slug): JsonResponse
    {
        if (! config('furniture.blog_posts_enabled')) {
            return response()->json(['message' => 'Not found'], 404)->header('Cache-Control', 'no-store');
        }
        $post = DB::table('storefront_blog_posts')->where('slug', $slug)->where('published', true)->first(['slug', 'title', 'summary', 'body']);
        if ($post === null) {
            return response()->json(['message' => 'Not found'], 404)->header('Cache-Control', 'no-store');
        }

        return response()->json($post)->header('Cache-Control', 'no-store');
    }

    public function index(): View
    {
        $this->authorizeEditing();
        $posts = DB::table('storefront_blog_posts')->orderByDesc('created_at')->orderBy('slug')->paginate(20)
            ->withPath(route('admin.website.blog', absolute: false));

        return view('workspace.blog-posts-index', ['posts' => $posts]);
    }

    public function create(): View
    {
        $this->authorizeEditing();

        return view('workspace.blog-post-form', ['post' => null, 'versions' => collect()]);
    }

    public function edit(string $slug): View
    {
        $this->authorizeEditing();
        $post = DB::table('storefront_blog_posts')->where('slug', $slug)->first();
        abort_if($post === null, 404);

        return view('workspace.blog-post-form', ['post' => $post, 'versions' => DB::table('storefront_blog_post_versions')->where('slug', $slug)->orderByDesc('revision')->limit(20)->get()]);
    }

    public function store(Request $request, BlogPostRevisions $revisions): RedirectResponse
    {
        $this->authorizeEditing();
        $data = $this->validated($request, true);
        $revisions->create((int) Auth::id(), $data);

        return redirect()->to(route('admin.website.blog.edit', ['slug' => $data['slug']], absolute: false))->with('status', 'Article saved. Published articles appear in the connected preview.');
    }

    public function update(Request $request, string $slug, BlogPostRevisions $revisions): RedirectResponse
    {
        $this->authorizeEditing();
        $data = $this->validated($request, false);
        $revisions->change((int) Auth::id(), $slug, (int) $data['revision'], $data);

        return redirect()->to(route('admin.website.blog.edit', ['slug' => $slug], absolute: false))->with('status', 'Article saved. The connected preview will refresh.');
    }

    public function restore(Request $request, string $slug, BlogPostRevisions $revisions): RedirectResponse
    {
        $this->authorizeEditing();
        $data = $request->validate([
            'revision' => ['required', 'integer', 'min:1', 'max:4294967294'],
            'source_revision' => ['required', 'integer', 'min:1', 'max:4294967294'],
        ]);
        $revisions->change((int) Auth::id(), $slug, (int) $data['revision'], null, (int) $data['source_revision']);

        return redirect()->to(route('admin.website.blog.edit', ['slug' => $slug], absolute: false))->with('status', 'Article version restored, including its visibility.');
    }

    private function validated(Request $request, bool $creating): array
    {
        foreach (['slug', 'title', 'summary', 'body'] as $field) {
            if (is_string($request->input($field))) {
                $request->merge([$field => trim($request->input($field))]);
            }
        }
        $validUtf8 = static function (string $attribute, mixed $value, \Closure $fail): void {
            if (is_string($value) && ! mb_check_encoding($value, 'UTF-8')) {
                $fail('The '.$attribute.' must be valid text.');
            }
        };
        $rules = [
            'title' => ['required', 'string', 'max:160', $validUtf8],
            'summary' => ['required', 'string', 'max:300', $validUtf8],
            'body' => ['required', 'string', 'max:12000', $validUtf8],
            'published' => ['required', Rule::in(['0', '1'])],
        ];
        if ($creating) {
            $rules['slug'] = ['required', 'string', 'max:80', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D'];
        } else {
            $rules['revision'] = ['required', 'integer', 'min:1', 'max:4294967294'];
        }

        return $request->validate($rules);
    }

    private function authorizeEditing(): void
    {
        abort_unless(config('furniture.blog_posts_enabled'), 404);
        Gate::authorize('workspace.view');
        Gate::authorize('storefront.edit');
    }
}
