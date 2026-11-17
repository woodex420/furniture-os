<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\Customers\CreateContactCandidate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class ContactCreationCandidateTest extends TestCase
{
    use RefreshDatabase;

    private function input(array $overrides = []): array
    {
        return array_replace([
            'name' => '  Synthetic Person  ', 'email' => '  PERSON@EXAMPLE.TEST  ', 'phone' => ' 555-0100 ',
            'request_key' => 'a022da8c-2922-4f00-9d5a-13fe482f74bd',
        ], $overrides);
    }

    public function test_guest_non_owner_and_revoked_owner_cannot_call_candidate(): void
    {
        try {
            app(CreateContactCandidate::class)->create($this->input());
            $this->fail('Guest was allowed.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('contacts', 0);
        }
        $user = User::factory()->create(['is_owner' => false]);
        $this->actingAs($user);
        try {
            app(CreateContactCandidate::class)->create($this->input());
            $this->fail('Non-owner was allowed.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('contacts', 0);
        }
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner->forceFill(['is_owner' => false]));
        try {
            app(CreateContactCandidate::class)->create($this->input());
            $this->fail('Revoked owner was allowed.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('customer_audit_events', 0);
        }
    }

    public function test_candidate_requires_both_create_and_view_capabilities(): void
    {
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        Gate::define('contacts.view', fn (): bool => false);
        try {
            app(CreateContactCandidate::class)->create($this->input());
            $this->fail('The candidate must not check for duplicates without view access.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('contacts', 0);
        }
    }

    public function test_candidate_rechecks_the_persisted_owner_before_writing(): void
    {
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner);
        // Revoke through a different instance; the authenticated object is stale.
        User::query()->whereKey($owner->id)->update(['is_owner' => false]);
        try {
            app(CreateContactCandidate::class)->create($this->input());
            $this->fail('A stale owner session was allowed to write.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('contacts', 0);
            $this->assertDatabaseCount('customer_audit_events', 0);
        }
    }

    public function test_candidate_fails_closed_if_its_guard_row_is_missing(): void
    {
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        DB::table('customer_write_guards')->delete();
        try {
            app(CreateContactCandidate::class)->create($this->input());
            $this->fail('A missing guard was accepted.');
        } catch (\LogicException) {
            $this->assertDatabaseCount('contacts', 0);
            $this->assertDatabaseCount('customer_audit_events', 0);
        }
    }

    public function test_candidate_is_idempotent_and_writes_one_minimal_audit_event(): void
    {
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner);
        $service = app(CreateContactCandidate::class);
        $contact = $service->create($this->input(['untrusted_field' => 'ignored']));
        $retry = $service->create($this->input());
        $this->assertSame($contact->id, $retry->id);
        $this->assertSame('Synthetic Person', $contact->name);
        $this->assertSame('person@example.test', $contact->email);
        $this->assertSame('555-0100', $contact->phone);
        $this->assertDatabaseCount('contacts', 1);
        $this->assertDatabaseCount('contact_creation_requests', 1);
        $this->assertDatabaseCount('customer_audit_events', 1);
        $this->assertDatabaseHas('customer_audit_events', ['actor_id' => $owner->id, 'contact_id' => $contact->id, 'event' => 'contact.created']);
        $event = (array) DB::table('customer_audit_events')->first();
        $this->assertStringNotContainsString('person@example.test', json_encode($event));
        $this->assertStringNotContainsString('555-0100', json_encode($event));
    }

    public function test_reused_key_with_different_content_and_duplicate_email_fail_without_new_records(): void
    {
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $service = app(CreateContactCandidate::class);
        $service->create($this->input());
        foreach ([
            $this->input(['name' => 'Different']),
            $this->input(['request_key' => 'e05417ef-e27b-4832-bfc3-dcdd1a932a68', 'email' => 'person@example.test']),
        ] as $input) {
            try {
                $service->create($input);
                $this->fail('Conflicting request was accepted.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('contacts', 1);
                $this->assertDatabaseCount('contact_creation_requests', 1);
                $this->assertDatabaseCount('customer_audit_events', 1);
            }
        }
    }

    public function test_existing_whitespace_and_case_variant_is_not_overwritten(): void
    {
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        DB::table('contacts')->insert(['name' => 'Existing', 'email' => ' PERSON@EXAMPLE.TEST ', 'created_at' => now(), 'updated_at' => now()]);
        try {
            app(CreateContactCandidate::class)->create($this->input());
            $this->fail('Duplicate email was accepted.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('contacts', 1);
            $this->assertDatabaseHas('contacts', ['name' => 'Existing', 'email' => ' PERSON@EXAMPLE.TEST ']);
            $this->assertDatabaseCount('customer_audit_events', 0);
        }
    }

    public function test_validation_is_bounded_and_no_email_does_not_imply_deduplication(): void
    {
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $service = app(CreateContactCandidate::class);
        foreach ([['name' => '   '], ['name' => ['invalid']], ['email' => 'not-an-email'], ['phone' => str_repeat('1', 51)], ['request_key' => 'not-a-uuid'], ['name' => "\xFF"]] as $invalid) {
            try {
                $service->create($this->input($invalid));
                $this->fail('Invalid details were accepted.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('contacts', 0);
            }
        }
        $service->create($this->input(['email' => null]));
        $service->create($this->input(['email' => null, 'request_key' => 'e05417ef-e27b-4832-bfc3-dcdd1a932a68']));
        $this->assertDatabaseCount('contacts', 2);
    }

    public function test_audit_failure_rolls_back_contact_and_idempotency_record(): void
    {
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        DB::listen(function (QueryExecuted $event): void {
            if (str_contains(strtolower($event->sql), 'insert into "customer_audit_events"')) {
                throw new RuntimeException('Synthetic audit failure');
            }
        });
        try {
            app(CreateContactCandidate::class)->create($this->input());
            $this->fail('The synthetic audit failure should propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic audit failure', $exception->getMessage());
            $this->assertDatabaseCount('contacts', 0);
            $this->assertDatabaseCount('contact_creation_requests', 0);
            $this->assertDatabaseCount('customer_audit_events', 0);
        }
    }
}
