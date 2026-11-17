<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Support\Website\HeroImage;
use App\Support\Website\HeroTextOrder;
use App\Support\Website\StorefrontCanvasUrl;
use App\Support\Website\StorefrontHomeRevisions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** First, limited connection between Furniture admin and the real homepage component. */
final class StorefrontHomeController
{
    public function publicContent(): JsonResponse
    {
        abort_unless(config('furniture.home_editor_enabled'), 404);
        $content = DB::table('storefront_home_content')->where('id', 1)->first(['hero_title', 'hero_subtitle', 'hero_order', 'hero_cta_label', 'hero_cta_path', 'hero_image', 'hero_media_id', 'revision']);
        abort_if($content === null, 503);
        $content->hero_order = HeroTextOrder::resolve($content->hero_order);
        $content->hero_image = HeroImage::resolve($content->hero_image);

        return response()->json($content)->header('Cache-Control', 'no-store');
    }

    public function edit(): View
    {
        $this->authorizeEditing();
        $content = DB::table('storefront_home_content')->where('id', 1)->first();
        abort_if($content === null, 503);
        $content->hero_order = HeroTextOrder::resolve($content->hero_order);
        $content->hero_image = HeroImage::resolve($content->hero_image);
        $versions = DB::table('storefront_home_versions as versions')
            ->leftJoin('furniture_media as media', 'media.id', '=', 'versions.hero_media_id')
            ->orderByDesc('versions.revision')->limit(20)
            ->get(['versions.*', 'media.title as media_title']);
        foreach ($versions as $version) {
            $version->hero_order = HeroTextOrder::resolve($version->hero_order);
            $version->hero_image = HeroImage::resolve($version->hero_image);
        }

        $mediaChoices = collect();
        if (config('furniture.media_library_enabled')) {
            $mediaChoices = DB::table('furniture_media')->orderByDesc('created_at')->orderByDesc('id')->limit(100)->get(['id', 'title']);
            foreach ([$content->hero_media_id, old('hero_media_id')] as $id) {
                if (is_string($id) && ! $mediaChoices->contains('id', $id)) {
                    $extra = DB::table('furniture_media')->where('id', $id)->first(['id', 'title']);
                    if ($extra !== null) {
                        $mediaChoices->push($extra);
                    }
                }
            }
        }

        return view('workspace.storefront-home', [
            'content' => $content,
            'mediaChoices' => $mediaChoices,
            'canvasUrl' => StorefrontCanvasUrl::configured(),
            'imageChoices' => HeroImage::choices(),
            'versions' => $versions,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeEditing();
        $request->merge([
            'hero_title' => is_string($request->input('hero_title')) ? trim($request->input('hero_title')) : $request->input('hero_title'),
            'hero_subtitle' => is_string($request->input('hero_subtitle')) ? trim($request->input('hero_subtitle')) : $request->input('hero_subtitle'),
        ]);
        foreach (['hero_cta_label', 'hero_cta_path'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $request->merge([$field => trim($request->input($field))]);
            }
        }
        if (! config('furniture.media_library_enabled') && array_key_exists('hero_media_id', $request->all())) {
            throw ValidationException::withMessages(['hero_media_id' => 'Media selection is currently disabled.']);
        }
        $data = $request->validate([
            'hero_title' => ['required', 'string', 'max:120'],
            'hero_subtitle' => ['required', 'string', 'max:240'],
            'hero_order' => ['sometimes', 'required', 'string', Rule::in(HeroTextOrder::values())],
            'hero_cta_label' => ['sometimes', 'required', 'string', 'max:80'],
            'hero_image' => ['sometimes', 'required', 'string', Rule::in(array_keys(HeroImage::choices()))],
            'hero_media_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('furniture_media', 'id')],
            // Root-relative ASCII paths only: no external URLs, backslashes, percent escapes or control characters.
            'hero_cta_path' => ['sometimes', 'required', 'string', 'max:240', 'regex:#\A/(?!/)[A-Za-z0-9._~/?=&-]*\z#D'],
            'revision' => ['required', 'integer', 'min:0', 'max:4294967294'],
        ]);
        app(StorefrontHomeRevisions::class)->edit((int) Auth::id(), $data);

        return redirect()->to(route('admin.storefront.home', absolute: false))->with('status', 'Home slide updated. The storefront preview will refresh.');
    }

    public function restore(Request $request, StorefrontHomeRevisions $revisions): RedirectResponse
    {
        $this->authorizeEditing();
        $data = $request->validate([
            'revision' => ['required', 'integer', 'min:0', 'max:4294967294'],
            'source_revision' => ['required', 'integer', 'min:0', 'max:4294967294'],
        ]);
        $revisions->restore((int) Auth::id(), (int) $data['revision'], (int) $data['source_revision']);

        return redirect()->to(route('admin.storefront.home', absolute: false))->with('status', 'Home slide restored. The storefront preview will refresh.');
    }

    private function authorizeEditing(): void
    {
        abort_unless(config('furniture.home_editor_enabled'), 404);
        Gate::authorize('workspace.view');
        Gate::authorize('storefront.edit');
    }
}
