<?php

namespace App\Providers;

use App\Http\Middleware\PrivateWorkspaceResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Browser assets stay same-origin when a reverse proxy supplies an internal Host.
        // A separately configured CDN or subdirectory deployment needs its own review.
        Vite::createAssetPathsUsing(fn (string $path): string => '/'.ltrim($path, '/'));

        // Provisional first-owner-only access. No blanket owner bypass for other abilities.
        Gate::define('workspace.view', fn (User $user): bool => $user->is_owner === true);
        // Until staff policy and record visibility are designed, only the owner can read CRM records.
        Gate::define('leads.view', fn (User $user): bool => $user->is_owner === true);
        Gate::define('leads.create', fn (User $user): bool => $user->is_owner === true);
        Gate::define('contacts.view', fn (User $user): bool => $user->is_owner === true);
        // Experimental owner-only contact creation; the UI is feature-flagged off by default.
        Gate::define('contacts.create', fn (User $user): bool => $user->is_owner === true);
        Gate::define('storefront.edit', fn (User $user): bool => $user->is_owner === true);

        // All current Livewire components are private workspace components. Future public
        // components need a separately reviewed endpoint, not a relaxation of this one.
        $this->app->booted(function (): void {
            Livewire::setUpdateRoute(fn ($handle) => Route::post('/workspace/livewire/update', $handle)
                ->middleware(['web', PrivateWorkspaceResponse::class, 'auth', 'can:workspace.view'])
                ->name('workspace.livewire.update'));
        });

        RateLimiter::for('workspace-login', function (Request $request): array {
            $email = $request->input('email');
            $email = is_string($email) ? Str::lower(trim($email)) : '';
            $ip = (string) $request->ip();
            // Preview visitors and local tests can share one reverse-proxy IP. An explicit
            // double opt-in in local/testing environments separates those buckets and
            // raises, but does not remove, limits. Never enable for real accounts/data.
            $synthetic = config('furniture.admin_preview_landing')
                && config('furniture.synthetic_preview_login_limit')
                && $this->app->environment(['local', 'testing']);
            $prefix = $synthetic ? 'synthetic-preview-v1:' : '';

            return [
                // Do not put a raw email address in the cache key.
                Limit::perMinute($synthetic ? 60 : 5)->by($prefix.'account-ip:'.hash('sha256', $email.'|'.$ip)),
                Limit::perMinute($synthetic ? 300 : 30)->by($prefix.'login-ip:'.hash('sha256', $ip)),
            ];
        });
    }
}
