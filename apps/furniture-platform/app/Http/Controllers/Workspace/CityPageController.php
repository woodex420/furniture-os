<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Support\Website\CityPageRevisions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class CityPageController
{
    public function publicIndex(Request $request): JsonResponse
    {
        abort_unless(config('furniture.city_pages_enabled'), 404);
        $data = $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:1000000']]);
        $page = DB::table('storefront_city_pages')->where('published', true)->orderBy('city_name')->orderBy('slug')
            ->paginate(20, ['slug', 'city_name', 'title', 'summary'], 'page', $data['page'] ?? 1);

        // Do not expose framework-generated absolute proxy URLs or private metadata.
        return response()->json(['data' => $page->items(), 'current_page' => $page->currentPage(), 'last_page' => $page->lastPage()])->header('Cache-Control', 'no-store');
    }

    public function publicShow(string $slug): JsonResponse
    {
        if (! config('furniture.city_pages_enabled')) {
            return response()->json(['message' => 'Not found'], 404)->header('Cache-Control', 'no-store');
        }
        $page = DB::table('storefront_city_pages')->where('slug', $slug)->where('published', true)
            ->first(['slug', 'city_name', 'title', 'summary', 'body']);
        if ($page === null) {
            return response()->json(['message' => 'Not found'], 404)->header('Cache-Control', 'no-store');
        }

        return response()->json($page)->header('Cache-Control', 'no-store');
    }

    public function index(): View
    {
        $this->authorizeEditing();

        $pages = DB::table('storefront_city_pages')->orderBy('city_name')->orderBy('slug')->paginate(20)
            ->withPath(route('admin.website.cities', absolute: false));

        return view('workspace.city-pages-index', ['pages' => $pages]);
    }

    public function create(): View
    {
        $this->authorizeEditing();

        return view('workspace.city-page-form', ['page' => null, 'versions' => collect()]);
    }

    public function edit(string $slug): View
    {
        $this->authorizeEditing();
        $page = DB::table('storefront_city_pages')->where('slug', $slug)->first();
        abort_if($page === null, 404);

        return view('workspace.city-page-form', ['page' => $page, 'versions' => DB::table('storefront_city_page_versions')->where('slug', $slug)->orderByDesc('revision')->limit(20)->get()]);
    }

    public function store(Request $request, CityPageRevisions $revisions): RedirectResponse
    {
        $this->authorizeEditing();
        $data = $this->validated($request, true);
        $revisions->create((int) Auth::id(), $data);

        return redirect()->to(route('admin.website.cities.edit', ['slug' => $data['slug']], absolute: false))->with('status', 'City page saved. Published pages appear in the connected preview.');
    }

    public function update(Request $request, string $slug, CityPageRevisions $revisions): RedirectResponse
    {
        $this->authorizeEditing();
        $data = $this->validated($request, false);
        $revisions->change((int) Auth::id(), $slug, (int) $data['revision'], $data);

        return redirect()->to(route('admin.website.cities.edit', ['slug' => $slug], absolute: false))->with('status', 'City page saved. The connected preview will refresh.');
    }

    public function restore(Request $request, string $slug, CityPageRevisions $revisions): RedirectResponse
    {
        $this->authorizeEditing();
        $data = $request->validate([
            'revision' => ['required', 'integer', 'min:1', 'max:4294967294'],
            'source_revision' => ['required', 'integer', 'min:1', 'max:4294967294'],
        ]);
        $revisions->change((int) Auth::id(), $slug, (int) $data['revision'], null, (int) $data['source_revision']);

        return redirect()->to(route('admin.website.cities.edit', ['slug' => $slug], absolute: false))->with('status', 'City page version restored, including its visibility.');
    }

    private function validated(Request $request, bool $creating): array
    {
        foreach (['slug', 'city_name', 'title', 'summary', 'body'] as $field) {
            if (is_string($request->input($field))) {
                $request->merge([$field => trim($request->input($field))]);
            }
        }
        $rules = [
            'city_name' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:240'],
            'body' => ['required', 'string', 'max:4000'],
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
        abort_unless(config('furniture.city_pages_enabled'), 404);
        Gate::authorize('workspace.view');
        Gate::authorize('storefront.edit');
    }
}
