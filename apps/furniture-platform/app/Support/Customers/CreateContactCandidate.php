<?php

declare(strict_types=1);

namespace App\Support\Customers;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Experimental creation service. The optional owner-only UI is for isolated testing;
 * policy and MySQL locking still require review before a real deployment.
 * Only callers authenticated through the application may invoke this service.
 */
final class CreateContactCandidate
{
    public function create(array $input): Contact
    {
        Gate::authorize('contacts.view');
        Gate::authorize('contacts.create');
        $actorId = Auth::id();
        if ($actorId === null) {
            throw new LogicException('An authenticated actor is required.');
        }

        $values = [];
        foreach (['name', 'email', 'phone', 'request_key'] as $key) {
            $value = $input[$key] ?? null;
            $values[$key] = is_string($value) ? trim($value) : $value;
        }
        foreach (['email', 'phone'] as $key) {
            if ($values[$key] === '') {
                $values[$key] = null;
            }
        }
        if (is_string($values['email'])) {
            $values['email'] = strtolower($values['email']);
        }
        if (is_string($values['request_key'])) {
            $values['request_key'] = strtolower($values['request_key']);
        }

        $validUtf8 = static function (string $attribute, mixed $value, \Closure $fail): void {
            if (is_string($value) && ! mb_check_encoding($value, 'UTF-8')) {
                $fail('The '.$attribute.' must be valid text.');
            }
        };
        $data = Validator::make($values, [
            'name' => ['required', 'string', 'max:255', $validUtf8],
            // Provisional policy: ASCII email only until international address rules are reviewed.
            'email' => ['nullable', 'string', 'email', 'max:255', 'regex:/\A[\x21-\x7e]+\z/', $validUtf8],
            'phone' => ['nullable', 'string', 'max:50', $validUtf8],
            'request_key' => ['required', 'string', 'uuid'],
        ])->validate();
        $payloadHash = hash('sha256', json_encode([
            'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'],
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actorId, $data, $payloadHash): Contact {
            // Serialize this candidate's duplicate checks. This only covers this service;
            // SQLite and MySQL concurrency/other write paths require separate verification.
            if (! DB::table('customer_write_guards')->where('id', 1)->lockForUpdate()->first()) {
                throw new LogicException('Customer write guard is missing.');
            }
            $actor = User::query()->lockForUpdate()->find($actorId);
            if ($actor === null) {
                throw new AuthorizationException;
            }
            Gate::forUser($actor)->authorize('contacts.view');
            Gate::forUser($actor)->authorize('contacts.create');
            $previous = DB::table('contact_creation_requests')->where('actor_id', $actorId)
                ->where('request_key', $data['request_key'])->first();
            if ($previous) {
                if (! hash_equals($previous->payload_hash, $payloadHash)) {
                    throw ValidationException::withMessages(['request_key' => 'This request key is already in use for different details.']);
                }

                return Contact::query()->findOrFail($previous->contact_id);
            }

            if ($data['email'] !== null && DB::table('contacts')->whereRaw('LOWER(TRIM(email)) = ?', [$data['email']])->exists()) {
                throw ValidationException::withMessages(['email' => 'A contact with that email already exists.']);
            }
            $now = now();
            $contactId = DB::table('contacts')->insertGetId([
                'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'],
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('contact_creation_requests')->insert([
                'actor_id' => $actorId, 'request_key' => $data['request_key'], 'payload_hash' => $payloadHash,
                'contact_id' => $contactId, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('customer_audit_events')->insert([
                'actor_id' => $actorId, 'contact_id' => $contactId, 'event' => 'contact.created', 'created_at' => $now,
            ]);

            return Contact::query()->findOrFail($contactId);
        }, 3);
    }
}
