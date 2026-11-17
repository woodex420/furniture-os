<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProxyBrowserUrlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_pages_use_same_origin_paths_with_an_internal_request_host(): void
    {
        $this->withoutVite();
        $this->get('http://internal.example/login')->assertOk()
            ->assertSee('action="/login"', false);
        $guest = $this->get('http://internal.example/admin');
        $this->assertSame('/login', $guest->headers->get('Location'));

        $owner = User::factory()->create(['is_owner' => true]);
        $page = $this->actingAs($owner)->get('http://internal.example/admin/leads')->assertOk();
        $page->assertSee('href="/admin/contacts"', false)
            ->assertSee('action="/logout"', false)
            ->assertSee('data-update-uri="/workspace/livewire/update"', false)
            ->assertSee('src="/livewire-', false)
            ->assertDontSee('http://internal.example/', false);
        $logout = $this->post('http://internal.example/logout');
        $this->assertSame('/login', $logout->headers->get('Location'));
    }
}
