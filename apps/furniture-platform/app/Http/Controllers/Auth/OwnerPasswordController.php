<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Owner-only password change. Account recovery and multi-factor authentication are separate work. */
final class OwnerPasswordController
{
    public function edit(): View
    {
        return view('workspace.owner-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'max:1024'],
            // Match initial provisioning. Bcrypt can only use the first 72 bytes.
            'password' => ['required', 'string', 'min:12', 'confirmed', function (string $attribute, mixed $value, Closure $fail): void {
                if (is_string($value) && strlen($value) > 72) {
                    $fail('The password must not exceed 72 bytes.');
                }
            }],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            // Serialize concurrent changes and check against the latest stored hash.
            $user = User::query()->whereKey($request->user()->getKey())->lockForUpdate()->firstOrFail();
            if (! $user->is_owner || ! Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
            }
            if (Hash::check($validated['password'], $user->password)) {
                throw ValidationException::withMessages(['password' => 'Choose a different password.']);
            }

            $user->forceFill(['password' => $validated['password'], 'remember_token' => Str::random(60)])->save();
            // Keep this request's authenticated model in sync for AuthenticateSession.
            $request->user()->setRawAttributes($user->getAttributes(), true);
        });

        // Preserve this signed-in session while rotating both its identifier and CSRF token.
        $request->session()->regenerate(true);

        return redirect()->route('admin.owner.password.edit')->with('status', 'Password updated. Other sessions that have used the updated application will be signed out on their next request.');
    }
}
