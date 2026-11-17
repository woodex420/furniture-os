<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ContactCreationHttpTest extends TestCase
{
    use RefreshDatabase;

    private function input(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Preview Customer', 'email' => 'CUSTOMER@example.test', 'phone' => '555-0101',
            'request_key' => 'b2ab81a5-7c70-4cd2-bcb4-9162ba5662e3',
        ], $overrides);
    }

    public function test_workflow_is_disabled_by_default(): void
    {
        $this->withoutVite()->actingAs(User::factory()->create(['is_owner' => true]));
        $this->get('/admin/contacts')->assertDontSee('Add contact');
        $this->get('/admin/contacts/create')->assertNotFound();
        $this->post('/admin/contacts', $this->input())->assertNotFound();
        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_guest_non_owner_and_denied_capability_cannot_create(): void
    {
        config(['furniture.contact_creation_enabled' => true]);
        $this->get('/admin/contacts/create')->assertRedirect(route('login'));
        $this->post('/admin/contacts', $this->input())->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['is_owner' => false]));
        $this->get('/admin/contacts/create')->assertForbidden();
        $this->post('/admin/contacts', $this->input())->assertForbidden();
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        Gate::define('contacts.create', fn (): bool => false);
        $this->withoutVite()->get('/admin/contacts')->assertDontSee('Add contact');
        $this->get('/admin/contacts/create')->assertForbidden();
        $this->post('/admin/contacts', $this->input())->assertForbidden();
        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_owner_can_create_and_retry_without_duplicate_records(): void
    {
        config(['furniture.contact_creation_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->withoutVite()->actingAs($owner)->get('/admin/contacts')->assertSee('Add contact');
        $this->get('/admin/contacts/create')->assertOk()->assertSee('name="_token"', false)->assertSee('name="request_key"', false);
        $response = $this->post('/admin/contacts', $this->input());
        $this->assertSame('/admin/contacts/1', $response->headers->get('Location'));
        $this->assertDatabaseHas('contacts', ['name' => 'Preview Customer', 'email' => 'customer@example.test']);
        $this->post('/admin/contacts', $this->input())->assertRedirect('/admin/contacts/1');
        $this->assertDatabaseCount('contacts', 1);
        $this->assertDatabaseCount('customer_audit_events', 1);
        $this->get('/admin/contacts/1')->assertOk()->assertSee('Preview Customer');
    }

    public function test_validation_duplicate_and_changed_retry_fail_safely(): void
    {
        config(['furniture.contact_creation_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $this->from('/admin/contacts/create')->post('/admin/contacts', $this->input(['name' => '']))
            ->assertSessionHasErrors('name')->assertRedirect('/admin/contacts/create');
        $this->post('/admin/contacts', $this->input())->assertRedirect('/admin/contacts/1');
        $this->post('/admin/contacts', $this->input(['name' => 'Changed']))->assertSessionHasErrors('request_key');
        $this->post('/admin/contacts', $this->input(['request_key' => '17eeb145-5904-4baa-bca5-15d96d6820f1']))->assertSessionHasErrors('email');
        $this->assertDatabaseCount('contacts', 1);
        $this->assertDatabaseCount('customer_audit_events', 1);
    }

    public function test_write_endpoint_is_throttled(): void
    {
        config(['furniture.contact_creation_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        for ($i = 0; $i < 20; $i++) {
            $this->post('/admin/contacts', $this->input(['name' => '']))->assertSessionHasErrors('name');
        }
        $this->post('/admin/contacts', $this->input())->assertTooManyRequests();
        $this->assertDatabaseCount('contacts', 0);
    }
}
