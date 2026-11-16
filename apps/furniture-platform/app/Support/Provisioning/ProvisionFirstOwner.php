<?php

declare(strict_types=1);

namespace App\Support\Provisioning;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

/** Initial installation only; does not create users on an existing workspace. */
final class ProvisionFirstOwner
{
    public function provision(string $name, string $email, string $password): User
    {
        $name = trim($name);
        $email = Str::lower(trim($email));

        Validator::make(['name' => $name, 'email' => $email, 'password' => $password], [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255'],
            // Bcrypt only uses the first 72 bytes. Reject longer inputs rather than truncate.
            'password' => ['required', 'string', 'min:12', function (string $attribute, mixed $value, \Closure $fail): void {
                if (strlen($value) > 72) {
                    $fail('The password must not exceed 72 bytes.');
                }
            }],
        ])->validate();

        try {
            return DB::transaction(function () use ($name, $email, $password): User {
                // The unique singleton also arbitrates simultaneous initialization attempts.
                DB::table('owner_initializations')->insert(['id' => 1, 'created_at' => now()]);
                if (User::query()->exists()) {
                    throw new RuntimeException('Initial owner setup is unavailable on a non-empty installation.');
                }

                $user = new User;
                // This is the only initial owner grant; is_owner is not mass assignable.
                $user->forceFill(['name' => $name, 'email' => $email, 'password' => $password, 'is_owner' => true]);
                $user->save();

                return $user;
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw new RuntimeException('Initial owner setup is unavailable on this installation.', previous: $exception);
        }
    }
}
