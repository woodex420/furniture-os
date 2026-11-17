<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\Website\StorefrontCanvasUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontHomeCanvasTest extends TestCase
{
    use RefreshDatabase;

    public function test_canvas_is_opt_in_and_only_visible_to_an_authorized_owner(): void
    {
        config(['furniture.home_editor_enabled' => true, 'furniture.storefront_canvas_url' => null]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->withoutVite()->actingAs($owner)->get('/admin/website/home')->assertOk()
            ->assertSee('Canvas not configured')->assertDontSee('data-canvas-frame', false);
        config(['furniture.storefront_canvas_url' => 'https://storefront.example.test']);
        $this->get('/admin/website/home')->assertOk()->assertSee('data-canvas-frame', false)
            ->assertSee('data-storefront-origin="https://storefront.example.test"', false);
        $this->actingAs(User::factory()->create(['is_owner' => false]))->get('/admin/website/home')->assertForbidden();
        config(['furniture.home_editor_enabled' => false]);
        $this->actingAs($owner)->get('/admin/website/home')->assertNotFound();
    }

    public function test_canvas_origin_rejects_unsafe_or_ambiguous_configuration(): void
    {
        foreach (['javascript:alert(1)', 'https://user:pass@example.test', 'https://example.test/path', 'https://example.test/?query=1', 'https://example.test/#part', 'http://example.test', '//example.test', 'not a url'] as $bad) {
            config(['furniture.storefront_canvas_url' => $bad]);
            $this->assertNull(StorefrontCanvasUrl::configured(), $bad);
        }
        config(['furniture.storefront_canvas_url' => 'https://EXAMPLE.test:8443/']);
        $this->assertSame('https://example.test:8443', StorefrontCanvasUrl::configured());
        config(['furniture.storefront_canvas_url' => 'http://127.0.0.1:5191']);
        $this->assertSame('http://127.0.0.1:5191', StorefrontCanvasUrl::configured());
    }
}
