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

/** Limited owner-only title/source editing; not a complete lead lifecycle. */
final class EditLeadCandidate
{
    public function update(int $id, array $input): Lead
    {
        abort_unless(config('furniture.lead_editing_enabled'), 404);
        foreach (['workspace.view', 'leads.view', 'leads.edit'] as $ability) {
            Gate::authorize($ability);
        }
        $actorId = Auth::id();
        if ($actorId === null) {
            throw new LogicException('An authenticated actor is required.');
        }
        $values = [];
        foreach (['title', 'source', 'edit_revision'] as $key) {
            $value = $input[$key] ?? null;
            $values[$key] = is_string($value) ? trim($value) : $value;
        }
        if ($values['source'] === '') {
            $values['source'] = null;
        }
        $validUtf8 = static function (string $attribute, mixed $value, \Closure $fail): void {
            if (is_string($value) && ! mb_check_encoding($value, 'UTF-8')) {
                $fail('The '.$attribute.' must be valid text.');
            }
        };
        $data = Validator::make($values, [
            'title' => ['required', 'string', 'max:255', $validUtf8],
            'source' => ['nullable', 'string', 'max:100', $validUtf8],
            'edit_revision' => ['required', 'integer', 'min:0', 'max:2147483646'],
        ])->validate();
        $revision = (int) $data['edit_revision'];

        return DB::transaction(function () use ($actorId, $id, $data, $revision): Lead {
            // Follow creation's lock order and recheck the stored actor before writing.
            if (! DB::table('customer_write_guards')->where('id', 1)->lockForUpdate()->first()) {
                throw new LogicException('Customer write guard is missing.');
            }
            $actor = User::query()->lockForUpdate()->find($actorId);
            if ($actor === null) {
                throw new AuthorizationException;
            }
            foreach (['workspace.view', 'leads.view', 'leads.edit'] as $ability) {
                Gate::forUser($actor)->authorize($ability);
            }
            $lead = Lead::query()->lockForUpdate()->findOrFail($id);
            if ((int) $lead->edit_revision !== $revision) {
                throw ValidationException::withMessages(['edit_revision' => 'This lead changed since you opened it. Copy your edits, then reload the current record.']);
            }
            if ($lead->title === $data['title'] && $lead->source === $data['source']) {
                return $lead;
            }
            $now = now();
            // The compare-and-swap also prevents lost writes where row locks are unavailable.
            $updated = DB::table('leads')->where('id', $id)->where('edit_revision', $revision)->update([
                'title' => $data['title'], 'source' => $data['source'],
                'edit_revision' => $revision + 1, 'updated_at' => $now,
            ]);
            if ($updated !== 1) {
                throw ValidationException::withMessages(['edit_revision' => 'This lead changed since you opened it. Copy your edits, then reload the current record.']);
            }
            DB::table('lead_audit_events')->insert([
                'actor_id' => $actorId, 'lead_id' => $id, 'event' => 'lead.updated', 'created_at' => $now,
            ]);

            return Lead::query()->findOrFail($id);
        }, 3);
    }
}
