<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class StorefrontBundleTest extends TestCase
{
    private string $index;

    protected function setUp(): void
    {
        parent::setUp();
        $this->index = tempnam(sys_get_temp_dir(), 'furniture-bundle-');
        file_put_contents($this->index, '<!doctype html><title>Synthetic storefront bundle</title>');
        config([
            'furniture.admin_preview_landing' => false,
            'furniture.storefront_bundle_index' => $this->index,
            'furniture.storefront_bundle_enabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->index);
        parent::tearDown();
    }

    public function test_shell_is_disabled_by_default(): void
    {
        $this->get('/')->assertStatus(503)->assertDontSee('Synthetic storefront bundle');
        $this->get('/shop')->assertNotFound();
    }

    public function test_enabled_shell_serves_known_routes_with_staging_headers(): void
    {
        config(['furniture.storefront_bundle_enabled' => true]);
        foreach (['/', '/shop', '/shop/example', '/series/example', '/projects/example', '/services', '/services/design', '/locations/lahore', '/blog/example', '/contact', '/checkout'] as $path) {
            $this->get($path)->assertOk()->assertSee('Synthetic storefront bundle')
                ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
                ->assertHeader('Cache-Control', 'no-store, private');
        }
    }

    public function test_shell_does_not_capture_admin_api_or_unknown_routes(): void
    {
        config(['furniture.storefront_bundle_enabled' => true]);
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/storefront-api/home')->assertDontSee('Synthetic storefront bundle');
        $this->get('/unknown')->assertNotFound();
        $this->get('/shop/example/extra')->assertNotFound();
        $this->post('/shop')->assertStatus(405);
    }

    public function test_missing_bundle_fails_closed(): void
    {
        unlink($this->index);
        config(['furniture.storefront_bundle_enabled' => true]);
        $this->get('/')->assertStatus(503)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/shop')->assertStatus(503);
    }

    public function test_admin_preview_landing_takes_priority_when_explicitly_enabled(): void
    {
        config(['furniture.storefront_bundle_enabled' => true, 'furniture.admin_preview_landing' => true]);
        $this->get('/')->assertRedirect('/admin');
    }
}
