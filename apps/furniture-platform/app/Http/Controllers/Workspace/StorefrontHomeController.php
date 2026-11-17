<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Support\Website\StorefrontCanvasUrl;
use App\Support\Website\StorefrontHomeRevisions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** First, limited connection between Furniture admin and the real homepage component. */
final class StorefrontHomeController
{
    public function publicContent(): JsonResponse
    {
        abort_unless(config('furniture.home_editor_enabled'), 404);
        $content = DB::table('storefront_home_content')->where('id', 1)->first(['hero_title', 'hero_subtitle', 'revision']);
        abort_if($content === null, 503);

        return response()->json($content)->header('Cache-Control', 'no-store');
    }

    public function edit(): View
    {
        $this->authorizeEditing();
        $content = DB::table('storefront_home_content')->where('id', 1)->first();
        abort_if($content === null, 503);

        return view('workspace.storefront-home', [
            'content' => $content,
            'canvasUrl' => StorefrontCanvasUrl::configured(),
            'versions' => DB::table('storefront_home_versions')->orderByDesc('revision')->limit(20)->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeEditing();
        $request->merge([
            'hero_title' => is_string($request->input('hero_title')) ? trim($request->input('hero_title')) : $request->input('hero_title'),
            'hero_subtitle' => is_string($request->input('hero_subtitle')) ? trim($request->input('hero_subtitle')) : $request->input('hero_subtitle'),
        ]);
        $data = $request->validate([
            'hero_title' => ['required', 'string', 'max:120'],
            'hero_subtitle' => ['required', 'string', 'max:240'],
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
