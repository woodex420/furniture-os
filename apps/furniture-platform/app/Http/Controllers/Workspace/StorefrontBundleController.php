<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use Illuminate\Http\Response;

/**
 * Serves only the explicitly built preview shell, never an arbitrary file.
 * Static bundle files still require a separately tested hosting layout.
 */
final class StorefrontBundleController
{
    public function __invoke(): Response
    {
        abort_unless(config('furniture.storefront_bundle_enabled'), 404);

        $index = config('furniture.storefront_bundle_index');
        if (! is_string($index) || ! is_file($index) || ! is_readable($index)) {
            return response('Storefront preview bundle is not installed.', 503, [
                'Cache-Control' => 'no-store',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
        }

        $html = file_get_contents($index);
        if ($html === false) {
            return response('Storefront preview bundle is not available.', 503, [
                'Cache-Control' => 'no-store',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
