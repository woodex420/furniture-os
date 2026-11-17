<?php

namespace App\Providers;

use App\Http\Middleware\PrivateWorkspaceResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
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
        // Provisional first-owner-only access. No blanket owner bypass for other abilities.
        Gate::define('workspace.view', fn (User $user): bool => $user->is_owner === true);

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

            return [
                // Do not put a raw email address in the cache key.
                Limit::perMinute(5)->by('account-ip:'.hash('sha256', $email.'|'.$ip)),
                Limit::perMinute(30)->by('login-ip:'.hash('sha256', $ip)),
            ];
        });
    }
}
