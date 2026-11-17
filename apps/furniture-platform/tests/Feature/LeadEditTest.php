<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\Customers\EditLeadCandidate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Tests\TestCase;

class LeadEditTest extends TestCase
{
    use RefreshDatabase;

    private function lead(): int
    {
        return DB::table('leads')->insertGetId(['title' => 'Old title', 'source' => 'Referral', 'status' => 'new', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['title' => '  New title  ', 'source' => '  Website  ', 'edit_revision' => 0], $overrides);
    }

    public function test_edit_is_off_by_default_and_owner_only_when_enabled(): void
    {
        $id = $this->lead();
        $owner = User::factory()->create(['is_owner' => true]);
        $this->withoutVite()->actingAs($owner)->get("/admin/leads/$id")->assertOk()->assertDontSee('Edit title and source');
        $this->get("/admin/leads/$id/edit")->assertNotFound();
        $this->post("/admin/leads/$id", $this->payload())->assertNotFound();
        config(['furniture.lead_editing_enabled' => true]);
        $this->post('/logout')->assertRedirect('/login');
        $this->get("/admin/leads/$id/edit")->assertRedirect('/login');
        $this->post("/admin/leads/$id", $this->payload())->assertRedirect('/login');
        $nonOwner = User::factory()->create(['is_owner' => false]);
        $this->actingAs($nonOwner)->get("/admin/leads/$id/edit")->assertForbidden();
        $this->post("/admin/leads/$id", $this->payload())->assertForbidden();
        $this->post('/logout');
        $this->actingAs($owner)->get("/admin/leads/$id")->assertSee('Edit title and source');
        $this->get("/admin/leads/$id/edit")->assertOk()->assertSee('name="_token"', false)->assertSee('value="0"', false);
        Gate::define('leads.edit', fn (): bool => false);
        $this->get("/admin/leads/$id")->assertDontSee('Edit title and source');
        $this->get('/admin/leads/999999/edit')->assertForbidden();
        $this->post('/admin/leads/999999', $this->payload())->assertForbidden();
        $this->assertDatabaseHas('leads', ['id' => $id, 'title' => 'Old title', 'edit_revision' => 0]);
    }

    public function test_save_only_updates_title_and_source_with_actor_audit_and_noop_is_not_audited(): void
    {
        config(['furniture.lead_editing_enabled' => true]);
        $id = $this->lead();
        $owner = User::factory()->create(['is_owner' => true]);
        $this->withoutVite()->actingAs($owner)->post("/admin/leads/$id", $this->payload(['status' => 'won', 'contact_id' => 123]))->assertRedirect("/admin/leads/$id");
        $this->assertDatabaseHas('leads', ['id' => $id, 'title' => 'New title', 'source' => 'Website', 'status' => 'new', 'contact_id' => null, 'edit_revision' => 1]);
        $this->assertDatabaseHas('lead_audit_events', ['lead_id' => $id, 'actor_id' => $owner->id, 'event' => 'lead.updated']);
        $this->get("/admin/leads/$id")->assertOk()->assertSee('New title')->assertSee('Website');
        $this->post("/admin/leads/$id", $this->payload(['edit_revision' => 1]))->assertRedirect("/admin/leads/$id");
        $this->assertDatabaseCount('lead_audit_events', 1);
        $this->assertDatabaseHas('leads', ['id' => $id, 'edit_revision' => 1]);
        $this->post("/admin/leads/$id", $this->payload(['edit_revision' => 1, 'source' => '']))->assertRedirect("/admin/leads/$id");
        $this->assertDatabaseHas('leads', ['id' => $id, 'source' => null, 'edit_revision' => 2]);
        $this->assertDatabaseCount('lead_audit_events', 2);
    }

    public function test_stale_and_invalid_input_do_not_overwrite_or_create_audit(): void
    {
        config(['furniture.lead_editing_enabled' => true]);
        $id = $this->lead();
        $this->withoutVite()->actingAs(User::factory()->create(['is_owner' => true]));
        $this->post("/admin/leads/$id", $this->payload())->assertRedirect();
        $this->from("/admin/leads/$id/edit")->post("/admin/leads/$id", $this->payload(['title' => 'Stale attempt', 'source' => '']))
            ->assertUnprocessable()->assertSee('Stale attempt')->assertSee('This lead changed')->assertSee('Reload current lead')
            ->assertSee('name="edit_revision" value="0"', false)->assertSee('name="source" type="text" maxlength="100" value=""', false);
        foreach ([['title' => '  '], ['title' => ['bad']], ['source' => str_repeat('x', 101)], ['edit_revision' => -1], ['edit_revision' => 'bad'], ['edit_revision' => 2147483647]] as $invalid) {
            $this->post("/admin/leads/$id", $this->payload($invalid))->assertUnprocessable()->assertSee('Please check the information below.');
        }
        $this->post("/admin/leads/$id", $this->payload(['edit_revision' => 1, 'title' => '<script>alert(1)</script>', 'source' => str_repeat('x', 101)]))
            ->assertUnprocessable()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->post('/admin/leads/999999', $this->payload())->assertNotFound();
        $this->assertDatabaseHas('leads', ['id' => $id, 'title' => 'New title', 'edit_revision' => 1]);
        $this->assertDatabaseCount('lead_audit_events', 1);
    }

    public function test_service_rechecks_actor_and_audit_failure_rolls_back(): void
    {
        config(['furniture.lead_editing_enabled' => true]);
        $id = $this->lead();
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner);
        User::query()->whereKey($owner->id)->update(['is_owner' => false]);
        try {
            app(EditLeadCandidate::class)->update($id, $this->payload());
            $this->fail('A revoked owner was allowed to edit.');
        } catch (AuthorizationException) {
            $this->assertDatabaseHas('leads', ['id' => $id, 'title' => 'Old title', 'edit_revision' => 0]);
        }
        User::query()->whereKey($owner->id)->update(['is_owner' => true]);
        DB::listen(function (QueryExecuted $event): void {
            if (str_contains(strtolower($event->sql), 'insert into "lead_audit_events"')) {
                throw new RuntimeException('Synthetic audit failure');
            }
        });
        try {
            app(EditLeadCandidate::class)->update($id, $this->payload());
            $this->fail('Audit failure should propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic audit failure', $exception->getMessage());
            $this->assertDatabaseHas('leads', ['id' => $id, 'title' => 'Old title', 'edit_revision' => 0]);
            $this->assertDatabaseCount('lead_audit_events', 0);
        }
    }

    public function test_write_is_throttled(): void
    {
        config(['furniture.lead_editing_enabled' => true]);
        $id = $this->lead();
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        for ($i = 0; $i < 20; $i++) {
            $this->post("/admin/leads/$id", $this->payload(['title' => '']))->assertUnprocessable();
        }
        $this->post("/admin/leads/$id", $this->payload())->assertTooManyRequests();
        $this->assertDatabaseHas('leads', ['id' => $id, 'title' => 'Old title', 'edit_revision' => 0]);
    }
}
