<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Support\Website\PrivateMediaUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class MediaLibraryController
{
    public function index(): View
    {
        $this->authorizeMedia();

        return view('workspace.media-library', [
            'media' => DB::table('furniture_media')->orderByDesc('created_at')->orderByDesc('id')->paginate(20),
            'publicHeroId' => config('furniture.home_editor_enabled') ? DB::table('storefront_home_content')->where('id', 1)->value('hero_media_id') : null,
        ]);
    }

    public function store(Request $request, PrivateMediaUploads $uploads): RedirectResponse
    {
        $this->authorizeMedia();
        if (is_string($request->input('title'))) {
            $request->merge(['title' => trim($request->input('title'))]);
        }
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'image' => ['required', 'file', 'max:2048'],
        ]);
        $uploads->store((int) Auth::id(), $data['title'], $data['image']);

        return redirect()->to(route('admin.website.media', absolute: false))->with('status', 'Image added to the library. It stays private until you select and save it in Site Overview.');
    }

    public function show(string $id): BinaryFileResponse
    {
        $this->authorizeMedia();
        $media = DB::table('furniture_media')->where('id', $id)->first();
        abort_if($media === null || ! in_array($media->mime, ['image/png', 'image/jpeg'], true), 404);
        $disk = Storage::disk('furniture_media');
        $path = PrivateMediaUploads::path($media->id, $media->mime);
        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => $media->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function authorizeMedia(): void
    {
        abort_unless(config('furniture.media_library_enabled'), 404);
        Gate::authorize('workspace.view');
        Gate::authorize('storefront.edit');
    }
}
