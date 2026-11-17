<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Support\Website\PrivateMediaUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Only the image referenced by the current public first slide may be served. */
final class PublicHeroImageController
{
    public function show(string $id): BinaryFileResponse
    {
        abort_unless(config('furniture.home_editor_enabled'), 404);
        $media = DB::table('storefront_home_content as home')
            ->join('furniture_media as media', 'media.id', '=', 'home.hero_media_id')
            ->where('home.id', 1)->where('media.id', $id)->first(['media.id', 'media.mime']);
        abort_if($media === null || ! in_array($media->mime, ['image/png', 'image/jpeg'], true), 404);
        $disk = Storage::disk('furniture_media');
        $path = PrivateMediaUploads::path($media->id, $media->mime);
        abort_unless($disk->exists($path), 404);

        return new BinaryFileResponse($disk->path($path), 200, [
            'Content-Type' => $media->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, no-store',
        ], public: false, autoLastModified: false);
    }
}
