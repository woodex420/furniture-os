<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\Customers\CreateLeadCandidate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Tests\TestCase;

class LeadCreationTest extends TestCase
{
    use RefreshDatabase;

    private function input(array $overrides = []): array
    {
        return array_merge([
            'title' => '  Dining table enquiry  ', 'source' => '  Referral  ', 'contact_id' => null,
            'request_key' => 'd91a4f71-b960-4761-9dd0-8c5799f3852d',
        ], $overrides);
    }

    private function contact(): int
    {
        return DB::table('contacts')->insertGetId(['name' => 'Example Contact', 'email' => null, 'phone' => null, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_disabled_by_default_and_denies_unauthorized_requests(): void
    {
        $this->withoutVite()->actingAs(User::factory()->create(['is_owner' => true]));
        $this->get('/admin/leads')->assertDontSee('Add lead');
        $this->get('/admin/leads/create')->assertNotFound();
        $this->post('/admin/leads', $this->input())->assertNotFound();
        config(['furniture.lead_creation_enabled' => true]);
        auth()->logout();
        $this->get('/admin/leads/create')->assertRedirect(route('login'));
        $this->post('/admin/leads', $this->input())->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['is_owner' => false]));
        $this->get('/admin/leads/create')->assertForbidden();
        $this->post('/admin/leads', $this->input())->assertForbidden();
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        Gate::define('leads.create', fn (): bool => false);
        $this->get('/admin/leads')->assertDontSee('Add lead');
        $this->post('/admin/leads', $this->input())->assertForbidden();
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_create_with_contact_retry_and_conflicting_retry(): void
    {
        config(['furniture.lead_creation_enabled' => true]);
        $contact = $this->contact();
        $owner = User::factory()->create(['is_owner' => true]);
        $this->withoutVite()->actingAs($owner)->get('/admin/leads')->assertSee('Add lead');
        $this->get('/admin/leads/create')->assertOk()->assertSee('Example Contact')->assertSee('name="_token"', false);
        $input = $this->input(['contact_id' => (string) $contact]);
        $response = $this->post('/admin/leads', $input);
        $this->assertSame('/admin/leads/1', $response->headers->get('Location'));
        $this->assertDatabaseHas('leads', ['title' => 'Dining table enquiry', 'source' => 'Referral', 'status' => 'new', 'contact_id' => $contact]);
        $this->post('/admin/leads', $input)->assertRedirect('/admin/leads/1');
        $this->post('/admin/leads', $this->input(['title' => 'Different', 'contact_id' => $contact]))->assertSessionHasErrors('request_key');
        $this->assertDatabaseCount('leads', 1);
        $this->assertDatabaseCount('lead_creation_requests', 1);
        $this->assertDatabaseHas('lead_audit_events', ['actor_id' => $owner->id, 'lead_id' => 1, 'event' => 'lead.created']);
        $this->get('/admin/leads/1')->assertOk()->assertSee('Dining table enquiry')->assertSee('Example Contact');
    }

    public function test_validation_contact_visibility_and_optional_contact(): void
    {
        config(['furniture.lead_creation_enabled' => true]);
        $contact = $this->contact();
        $this->withoutVite()->actingAs(User::factory()->create(['is_owner' => true]));
        foreach ([['title' => '  '], ['title' => ['bad']], ['source' => str_repeat('x', 101)], ['request_key' => 'bad'], ['contact_id' => 9999], ['contact_id' => -1]] as $invalid) {
            $this->from('/admin/leads/create')->post('/admin/leads', $this->input($invalid))->assertSessionHasErrors();
        }
        Gate::define('contacts.view', fn (): bool => false);
        $this->get('/admin/leads/create')->assertOk()->assertDontSee('Example Contact')->assertDontSee('name="contact_id"', false);
        $this->post('/admin/leads', $this->input(['contact_id' => $contact]))->assertForbidden();
        $this->post('/admin/leads', $this->input(['source' => '', 'contact_id' => '']))->assertRedirect('/admin/leads/1');
        $this->assertDatabaseHas('leads', ['title' => 'Dining table enquiry', 'source' => null, 'contact_id' => null]);
        $this->assertDatabaseCount('leads', 1);
    }

    public function test_service_rechecks_revoked_owner_and_rolls_back_on_audit_failure(): void
    {
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner);
        User::query()->whereKey($owner->id)->update(['is_owner' => false]);
        try {
            app(CreateLeadCandidate::class)->create($this->input());
            $this->fail('Stale owner was allowed to write.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('leads', 0);
        }
        User::query()->whereKey($owner->id)->update(['is_owner' => true]);
        DB::listen(function (QueryExecuted $event): void {
            if (str_contains(strtolower($event->sql), 'insert into "lead_audit_events"')) {
                throw new RuntimeException('Synthetic audit failure');
            }
        });
        try {
            app(CreateLeadCandidate::class)->create($this->input());
            $this->fail('Audit failure should propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic audit failure', $exception->getMessage());
            $this->assertDatabaseCount('leads', 0);
            $this->assertDatabaseCount('lead_creation_requests', 0);
            $this->assertDatabaseCount('lead_audit_events', 0);
        }
    }

    public function test_new_request_keys_allow_duplicate_titles_and_missing_guard_fails_closed(): void
    {
        config(['furniture.lead_creation_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $this->post('/admin/leads', $this->input())->assertRedirect('/admin/leads/1');
        $this->post('/admin/leads', $this->input(['request_key' => '7a77d3e4-a826-45de-8c50-351237b6c559']))->assertRedirect('/admin/leads/2');
        $this->assertDatabaseCount('leads', 2);
        DB::table('customer_write_guards')->delete();
        try {
            app(CreateLeadCandidate::class)->create($this->input(['request_key' => '5433f34a-55f6-4528-ac5c-56458ab143e3']));
            $this->fail('A missing guard was accepted.');
        } catch (\LogicException) {
            $this->assertDatabaseCount('leads', 2);
            $this->assertDatabaseCount('lead_audit_events', 2);
        }
    }

    public function test_write_endpoint_is_throttled(): void
    {
        config(['furniture.lead_creation_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        for ($i = 0; $i < 20; $i++) {
            $this->post('/admin/leads', $this->input(['title' => '']))->assertSessionHasErrors('title');
        }
        $this->post('/admin/leads', $this->input())->assertTooManyRequests();
        $this->assertDatabaseCount('leads', 0);
    }
}
