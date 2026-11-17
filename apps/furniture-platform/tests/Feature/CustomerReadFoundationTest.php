<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Workspace\ContactsIndex;
use App\Livewire\Workspace\LeadsIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerReadFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->create(['is_owner' => true]);
    }

    private function contact(string $name, ?string $email = null, ?string $phone = null): int
    {
        return DB::table('contacts')->insertGetId(['name' => $name, 'email' => $email, 'phone' => $phone, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function lead(string $title, ?int $contact = null): void
    {
        DB::table('leads')->insert(['title' => $title, 'contact_id' => $contact, 'source' => null, 'status' => 'new', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_owner_can_read_pages_and_see_empty_states_without_example_records(): void
    {
        $this->withoutVite()->actingAs($this->owner());
        foreach (['leads' => 'No leads found', 'contacts' => 'No contacts found'] as $path => $empty) {
            $response = $this->get('/admin/'.$path)->assertOk()->assertSee($empty)
                ->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertDontSee('Amina Malik');
            $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
            $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        }
        $this->get('/admin')->assertSee('href="'.route('admin.leads', absolute: false).'"', false)
            ->assertSee('href="'.route('admin.contacts', absolute: false).'"', false);
    }

    public function test_guest_and_non_owner_are_denied_both_pages_and_components(): void
    {
        foreach (['leads', 'contacts'] as $path) {
            $this->get('/admin/'.$path)->assertRedirect(route('login'));
        }
        foreach ([LeadsIndex::class, ContactsIndex::class] as $component) {
            Livewire::test($component)->assertForbidden();
        }
        $user = User::factory()->create(['is_owner' => false]);
        $this->actingAs($user);
        foreach (['leads', 'contacts'] as $path) {
            $this->get('/admin/'.$path)->assertForbidden()->assertHeader('Cache-Control', 'no-store, private');
        }
        foreach ([LeadsIndex::class, ContactsIndex::class] as $component) {
            Livewire::actingAs($user)->test($component)->assertForbidden();
        }
        $this->assertFalse(Gate::forUser($user)->allows('leads.view'));
        $this->assertFalse(Gate::forUser($user)->allows('leads.create'));
        $this->assertTrue(Gate::forUser($this->owner())->allows('leads.create'));
    }

    public function test_separate_capability_is_enforced_on_navigation_route_and_component(): void
    {
        $owner = $this->owner();
        Gate::define('leads.view', fn (): bool => false);
        $this->withoutVite()->actingAs($owner)->get('/admin')->assertOk()->assertDontSee('href="'.route('admin.leads', absolute: false).'"', false);
        $this->get('/admin/leads')->assertForbidden();
        Livewire::actingAs($owner)->test(LeadsIndex::class)->assertForbidden();
        $this->get('/admin/contacts')->assertOk();
    }

    public function test_search_is_bounded_literal_and_escaped_with_nullable_contact_data(): void
    {
        $contact = $this->contact('<script>Row</script>', 'row@example.test', null);
        $this->contact('One hundred %', 'percent@example.test', '555_10');
        $this->contact('Unrelated', null, null);
        $this->lead('Lounge enquiry', $contact);
        $this->lead('Only 100% timber');
        $this->lead('Other enquiry');
        $owner = $this->owner();

        Livewire::actingAs($owner)->test(LeadsIndex::class)->assertSee('Lounge enquiry')->assertSee('&lt;script&gt;Row&lt;/script&gt;', false)
            ->assertDontSee('<script>Row</script>', false)->set('search', 'ROW')->assertSee('Lounge enquiry')->assertDontSee('Other enquiry')
            ->set('search', '%')->assertSee('Only 100% timber')->assertDontSee('Other enquiry')
            ->set('search', "' OR 1=1 --")->assertDontSee('Other enquiry')->assertSee('No leads found');
        Livewire::actingAs($owner)->test(ContactsIndex::class)->set('search', '_')->assertSee('One hundred %')->assertDontSee('Unrelated')
            ->set('search', 'ROW@EXAMPLE')->assertSee('&lt;script&gt;Row&lt;/script&gt;', false)->assertDontSee('Unrelated')
            ->set('search', str_repeat('a', 101))->assertHasErrors(['search'])->assertDontSee('Unrelated')
            ->set('search', ['unexpected'])->assertHasErrors(['search'])->assertDontSee('Unrelated')
            ->set('search', 'Unrelated')->assertHasNoErrors(['search'])->assertSee('Unrelated');
        Livewire::actingAs($owner)->test(LeadsIndex::class)->set('search', ['unexpected'])->assertHasErrors(['search'])->assertDontSee('Other enquiry')
            ->set('search', 'Other')->assertHasNoErrors(['search'])->assertSee('Other enquiry');
    }

    public function test_pagination_is_bounded_and_search_resets_to_first_page(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->contact('Person '.$i);
            $this->lead('Enquiry '.$i);
        }
        $owner = $this->owner();
        Livewire::actingAs($owner)->test(ContactsIndex::class)->assertSee('Person 12')->assertDontSee('Person 1<', false)
            ->call('nextPage')->assertSee('Person 1')->assertDontSee('Person 12')
            ->set('search', 'Person 12')->assertSee('Person 12')->assertDontSee('Person 1<', false);
        Livewire::actingAs($owner)->test(LeadsIndex::class)->assertSee('Enquiry 12')->assertDontSee('Enquiry 1<', false)
            ->call('nextPage')->assertSee('Enquiry 1')->assertDontSee('Enquiry 12')
            ->set('search', 'Enquiry 12')->assertSee('Enquiry 12');
    }

    public function test_no_default_business_records_and_unimplemented_writes_are_unavailable(): void
    {
        $this->seed();
        $this->assertDatabaseCount('contacts', 0);
        $this->assertDatabaseCount('leads', 0);
        $this->actingAs($this->owner());
        $this->post('/admin/leads', ['name' => 'Unexpected'])->assertNotFound();
        $this->post('/admin/contacts', ['name' => 'Unexpected'])->assertNotFound();
        foreach (['leads', 'contacts'] as $path) {
            $this->patch('/admin/'.$path, ['name' => 'Unexpected'])->assertStatus(405);
            $this->delete('/admin/'.$path)->assertStatus(405);
        }
        $this->assertDatabaseCount('contacts', 0);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_real_livewire_updates_recheck_the_specific_capability(): void
    {
        $owner = $this->owner();
        foreach (['leads' => 'leads.view', 'contacts' => 'contacts.view'] as $path => $ability) {
            $page = $this->withoutVite()->actingAs($owner)->get('/admin/'.$path)->assertOk()->getContent();
            $this->assertSame(1, preg_match('/wire:snapshot="([^"]+)"/', $page, $matches));
            $snapshot = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $payload = ['components' => [['snapshot' => $snapshot, 'calls' => [['path' => '', 'method' => '$refresh', 'params' => []]], 'updates' => []]]];
            $response = $this->postJson('/workspace/livewire/update', $payload, ['X-Livewire' => 'true'])->assertOk();
            $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
            Gate::define($ability, fn (): bool => false);
            $response = $this->postJson('/workspace/livewire/update', $payload, ['X-Livewire' => 'true'])->assertForbidden();
            $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        }
    }

    public function test_revoked_owner_cannot_refresh_either_livewire_list(): void
    {
        foreach ([LeadsIndex::class, ContactsIndex::class] as $class) {
            $owner = $this->owner();
            $component = Livewire::actingAs($owner)->test($class)->assertOk();
            $owner->forceFill(['is_owner' => false])->save();
            $this->actingAs($owner->fresh());
            $component->call('$refresh')->assertForbidden();
        }
    }
}
