<?php

declare(strict_types=1);

namespace App\Support\Website;

/** Validate an explicitly configured storefront origin, never a user-supplied URL. */
final class StorefrontCanvasUrl
{
    public static function configured(): ?string
    {
        $url = config('furniture.storefront_canvas_url');
        if (! is_string($url) || $url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        $parts = parse_url($url);
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) || ! in_array($parts['path'] ?? '', ['', '/'], true)) {
            return null;
        }
        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $local = app()->environment(['local', 'testing']) && in_array($host, ['localhost', '127.0.0.1'], true);
        if ($scheme !== 'https' && ! ($local && $scheme === 'http')) {
            return null;
        }
        if (isset($parts['port']) && ($parts['port'] < 1 || $parts['port'] > 65535)) {
            return null;
        }

        return $scheme.'://'.$host.(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
