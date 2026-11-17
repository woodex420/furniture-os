<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;
use Tests\TestCase;

class WorkspaceShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_see_a_private_shell_with_honest_pending_states(): void
    {
        $owner = User::factory()->create(['name' => '<script>unsafe</script>', 'is_owner' => true]);
        $response = $this->withoutVite()->actingAs($owner)->get('/admin');

        $response->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('WOODEX')
            ->assertSee('INTEGRATION IN PROGRESS')
            ->assertSee('Business snapshot')
            ->assertSee('Leads')
            ->assertSee('Pending')
            ->assertSee('Sign out')
            ->assertSee('&lt;script&gt;unsafe&lt;/script&gt;', false)
            ->assertDontSee('<script>unsafe</script>', false)
            ->assertDontSee('2,450,000')
            ->assertDontSee('Ayesha Khan');
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
    }

    public function test_guests_and_non_owners_cannot_access_shell_or_update_endpoint(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->postJson(EndpointResolver::updatePath(), ['components' => []], ['X-Livewire' => 'true'])->assertNotFound();
        $guestResponse = $this->post('/workspace/livewire/update')->assertRedirect(route('login'));
        $this->assertTrue($guestResponse->headers->hasCacheControlDirective('no-store'));

        $this->actingAs(User::factory()->create(['is_owner' => false]));
        $this->get('/admin')->assertForbidden()->assertHeader('Cache-Control', 'no-store, private');
        $response = $this->post('/workspace/livewire/update')->assertForbidden();
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
    }

    public function test_real_livewire_http_update_is_private_and_default_endpoint_cannot_bypass_authentication(): void
    {
        $owner = User::factory()->create(['is_owner' => true]);
        $page = $this->withoutVite()->actingAs($owner)->get('/admin')->assertOk()->getContent();
        $this->assertSame(1, preg_match('/wire:snapshot="([^"]+)"/', $page, $matches));
        $snapshot = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $payload = ['components' => [['snapshot' => $snapshot, 'calls' => [['path' => '', 'method' => '$refresh', 'params' => []]], 'updates' => []]]];

        $response = $this->postJson('/workspace/livewire/update', $payload, ['X-Livewire' => 'true'])->assertOk();
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->postJson(EndpointResolver::updatePath(), $payload, ['X-Livewire' => 'true'])->assertNotFound();
        $owner->forceFill(['is_owner' => false])->save();
        $this->actingAs($owner->fresh())->postJson('/workspace/livewire/update', $payload, ['X-Livewire' => 'true'])->assertForbidden();
    }
}
