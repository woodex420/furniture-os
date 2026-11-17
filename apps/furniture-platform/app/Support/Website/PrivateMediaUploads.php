<?php

declare(strict_types=1);

namespace App\Support\Website;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Limited private image intake, not a public media publishing system. */
final class PrivateMediaUploads
{
    public function store(int $actorId, string $title, UploadedFile $file): string
    {
        abort_unless(extension_loaded('gd') && function_exists('imagecreatefromstring') && class_exists(\finfo::class), 503, 'Image processing is not available on this server.');
        $bytes = file_get_contents($file->getRealPath());
        if ($bytes === false || strlen($bytes) > 2 * 1024 * 1024) {
            throw ValidationException::withMessages(['image' => 'The image must be at most 2 MB.']);
        }
        $info = @getimagesizefromstring($bytes);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (! is_array($info) || ! in_array($mime, ['image/png', 'image/jpeg'], true) || ($info['mime'] ?? null) !== $mime) {
            throw ValidationException::withMessages(['image' => 'Upload a valid PNG or JPEG image.']);
        }
        [$width, $height] = $info;
        if ($width < 1 || $height < 1 || $width > 3000 || $height > 3000 || $width * $height > 4000000) {
            throw ValidationException::withMessages(['image' => 'The image must fit within 3000 × 3000 pixels and 4 megapixels.']);
        }
        if ($mime === 'image/png' && self::isAnimatedPng($bytes)) {
            throw ValidationException::withMessages(['image' => 'Animated PNG images are not supported.']);
        }
        if ($mime === 'image/jpeg') {
            // Re-encoding strips EXIF. Refuse non-default orientation instead of silently rotating a photo.
            if (! function_exists('exif_read_data')) {
                throw ValidationException::withMessages(['image' => 'JPEG upload requires the server EXIF extension. PNG is available.']);
            }
            $exif = @exif_read_data($file->getRealPath(), 'IFD0', true);
            if ($exif === false || (isset($exif['IFD0']['Orientation']) && (int) $exif['IFD0']['Orientation'] !== 1)) {
                throw ValidationException::withMessages(['image' => 'JPEG orientation cannot be safely preserved. Rotate and export it upright first.']);
            }
        }
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            throw ValidationException::withMessages(['image' => 'The image could not be decoded.']);
        }
        try {
            if ($mime === 'image/png') {
                imagealphablending($image, false);
                imagesavealpha($image, true);
            }
            ob_start();
            try {
                $ok = $mime === 'image/png' ? imagepng($image) : imagejpeg($image, null, 85);
                $encoded = ob_get_contents();
            } finally {
                ob_end_clean();
            }
        } finally {
            imagedestroy($image);
        }
        if (! $ok || ! is_string($encoded) || $encoded === '' || strlen($encoded) > 12 * 1024 * 1024) {
            throw ValidationException::withMessages(['image' => 'The image could not be safely processed.']);
        }
        $id = (string) Str::uuid();
        $path = self::path($id, $mime);
        $disk = Storage::disk('furniture_media');
        try {
            if (! $disk->put($path, $encoded, ['visibility' => 'private'])) {
                throw new \RuntimeException('Could not store the private image.');
            }
            DB::transaction(function () use ($actorId, $id, $title, $mime, $width, $height, $encoded): void {
                $actor = User::query()->lockForUpdate()->find($actorId);
                if ($actor === null) {
                    throw new AuthorizationException;
                }
                Gate::forUser($actor)->authorize('workspace.view');
                Gate::forUser($actor)->authorize('storefront.edit');
                DB::table('furniture_media')->insert([
                    'id' => $id, 'title' => $title, 'mime' => $mime, 'width' => $width, 'height' => $height,
                    'bytes' => strlen($encoded), 'created_by' => $actorId, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }, 3);
        } catch (Throwable $exception) {
            // Best effort: a cleanup failure is reported rather than hiding the original failure.
            try {
                $disk->delete($path);
            } catch (Throwable $cleanup) {
                report($cleanup);
            }
            throw $exception;
        }

        return $id;
    }

    private static function isAnimatedPng(string $bytes): bool
    {
        $offset = 8;
        $length = strlen($bytes);
        while ($offset + 12 <= $length) {
            $chunkLength = unpack('N', substr($bytes, $offset, 4))[1];
            if ($chunkLength > $length - $offset - 12) {
                break; // GD will reject malformed data.
            }
            $type = substr($bytes, $offset + 4, 4);
            if ($type === 'acTL') {
                return true;
            }
            if ($type === 'IEND') {
                break;
            }
            $offset += 12 + $chunkLength;
        }

        return false;
    }

    public static function path(string $id, string $mime): string
    {
        return $id.($mime === 'image/png' ? '.png' : '.jpg');
    }
}
