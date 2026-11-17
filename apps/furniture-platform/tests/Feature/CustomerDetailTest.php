<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Workspace\LeadsIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerDetailTest extends TestCase
{
    use RefreshDatabase;

    private function records(): array
    {
        $contact = DB::table('contacts')->insertGetId(['name' => '<script>Private Contact</script>', 'email' => 'private@example.test', 'phone' => null, 'created_at' => now(), 'updated_at' => now()]);
        $lead = DB::table('leads')->insertGetId(['title' => '<b>Example lead</b>', 'contact_id' => $contact, 'status' => 'new', 'created_at' => now(), 'updated_at' => now()]);

        return [$lead, $contact];
    }

    public function test_owner_can_open_read_only_details_with_escaped_values_and_private_headers(): void
    {
        [$lead, $contact] = $this->records();
        $owner = User::factory()->create(['is_owner' => true]);
        $this->withoutVite()->actingAs($owner);
        $leadPage = $this->get('/admin/leads/'.$lead)->assertOk()->assertSee('READ-ONLY FOUNDATION')
            ->assertSee('&lt;b&gt;Example lead&lt;/b&gt;', false)->assertDontSee('<b>Example lead</b>', false)
            ->assertSee('&lt;script&gt;Private Contact&lt;/script&gt;', false)->assertSee(route('admin.contacts.show', $contact), false);
        $this->assertTrue($leadPage->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($leadPage->headers->hasCacheControlDirective('private'));
        $this->get('/admin/contacts/'.$contact)->assertOk()->assertSee('private@example.test')
            ->assertSee('&lt;script&gt;Private Contact&lt;/script&gt;', false)->assertDontSee('<script>Private Contact</script>', false);
        $this->get('/admin/leads/99999')->assertNotFound();
        $this->get('/admin/contacts/99999')->assertNotFound();
        $this->patch('/admin/leads/'.$lead)->assertStatus(405);
        $this->delete('/admin/contacts/'.$contact)->assertStatus(405);
        $this->assertDatabaseCount('leads', 1);
        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_unauthorized_users_cannot_distinguish_existing_and_missing_numeric_ids(): void
    {
        [$lead, $contact] = $this->records();
        foreach (["/admin/leads/$lead", '/admin/leads/99999', "/admin/contacts/$contact", '/admin/contacts/99999'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
        $this->actingAs(User::factory()->create(['is_owner' => false]));
        foreach (["/admin/leads/$lead", '/admin/leads/99999', "/admin/contacts/$contact", '/admin/contacts/99999'] as $path) {
            $this->get($path)->assertForbidden()->assertHeader('Cache-Control', 'no-store, private');
        }
    }

    public function test_lead_access_does_not_reveal_or_search_contacts_without_their_permission(): void
    {
        [$lead, $contact] = $this->records();
        $owner = User::factory()->create(['is_owner' => true]);
        Gate::define('contacts.view', fn (): bool => false);
        $this->withoutVite()->actingAs($owner)->get('/admin/leads/'.$lead)->assertOk()->assertSee('Restricted')
            ->assertDontSee('Private Contact')->assertDontSee(route('admin.contacts.show', $contact), false);
        $this->get('/admin/contacts/'.$contact)->assertForbidden();
        Livewire::actingAs($owner)->test(LeadsIndex::class)->assertSee('Restricted')->assertDontSee('Private Contact')
            ->set('search', 'Private Contact')->assertSee('No leads found')->assertDontSee('Example lead');
    }
}
