<?php

declare(strict_types=1);

namespace App\Support\Customers;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use LogicException;

/** Experimental owner-only lead creation, not yet verified against hosted MySQL. */
final class CreateLeadCandidate
{
    public function create(array $input): Lead
    {
        Gate::authorize('workspace.view');
        Gate::authorize('leads.view');
        Gate::authorize('leads.create');
        $actorId = Auth::id();
        if ($actorId === null) {
            throw new LogicException('An authenticated actor is required.');
        }

        $values = [];
        foreach (['title', 'source', 'contact_id', 'request_key'] as $key) {
            $value = $input[$key] ?? null;
            $values[$key] = is_string($value) ? trim($value) : $value;
        }
        foreach (['source', 'contact_id'] as $key) {
            if ($values[$key] === '') {
                $values[$key] = null;
            }
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
            'title' => ['required', 'string', 'max:255', $validUtf8],
            'source' => ['nullable', 'string', 'max:100', $validUtf8],
            'contact_id' => ['nullable', 'integer', 'min:1'],
            'request_key' => ['required', 'string', 'uuid'],
        ])->validate();
        $data['contact_id'] = $data['contact_id'] === null ? null : (int) $data['contact_id'];
        if ($data['contact_id'] !== null) {
            Gate::authorize('contacts.view');
        }
        $payloadHash = hash('sha256', json_encode([
            'title' => $data['title'], 'source' => $data['source'], 'contact_id' => $data['contact_id'],
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actorId, $data, $payloadHash): Lead {
            // Serialize this service and contact creation; MySQL concurrency is not yet verified.
            if (! DB::table('customer_write_guards')->where('id', 1)->lockForUpdate()->first()) {
                throw new LogicException('Customer write guard is missing.');
            }
            $actor = User::query()->lockForUpdate()->find($actorId);
            if ($actor === null) {
                throw new AuthorizationException;
            }
            foreach (['workspace.view', 'leads.view', 'leads.create'] as $ability) {
                Gate::forUser($actor)->authorize($ability);
            }
            if ($data['contact_id'] !== null) {
                Gate::forUser($actor)->authorize('contacts.view');
            }
            $previous = DB::table('lead_creation_requests')->where('actor_id', $actorId)
                ->where('request_key', $data['request_key'])->first();
            if ($previous) {
                if (! hash_equals($previous->payload_hash, $payloadHash)) {
                    throw ValidationException::withMessages(['request_key' => 'This request key is already in use for different details.']);
                }

                return Lead::query()->findOrFail($previous->lead_id);
            }
            if ($data['contact_id'] !== null && ! DB::table('contacts')->where('id', $data['contact_id'])->lockForUpdate()->first(['id'])) {
                throw ValidationException::withMessages(['contact_id' => 'Select an existing contact.']);
            }
            $now = now();
            $id = DB::table('leads')->insertGetId([
                'title' => $data['title'], 'source' => $data['source'], 'contact_id' => $data['contact_id'],
                'status' => 'new', 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('lead_creation_requests')->insert([
                'actor_id' => $actorId, 'request_key' => $data['request_key'], 'payload_hash' => $payloadHash,
                'lead_id' => $id, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('lead_audit_events')->insert([
                'actor_id' => $actorId, 'lead_id' => $id, 'event' => 'lead.created', 'created_at' => $now,
            ]);

            return Lead::query()->findOrFail($id);
        }, 3);
    }
}
