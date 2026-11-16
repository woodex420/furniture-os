<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
