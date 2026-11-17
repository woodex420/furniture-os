<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Prevent normal workspace responses from being stored by shared/browser caches. */
final class PrivateWorkspaceResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->is('admin', 'admin/*', 'login', 'login/*', 'logout', 'workspace/livewire/update')) {
            $response->headers->set('Cache-Control', 'private, no-store');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

            // A reverse proxy may forward an internal host. Keep same-origin redirects
            // root-relative, but never turn a double-slash path into an external URL.
            $location = $response->headers->get('Location');
            $origin = $request->getSchemeAndHttpHost();
            if (is_string($location) && str_starts_with($location, $origin.'/')) {
                $path = substr($location, strlen($origin));
                if (! str_starts_with($path, '//')) {
                    $response->headers->set('Location', $path);
                }
            }
        }

        return $response;
    }
}
