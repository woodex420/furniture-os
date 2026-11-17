<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_incomplete_storefront_is_not_presented_as_a_live_shop(): void
    {
        $this->get('/')->assertStatus(503)->assertSee('storefront is not available yet');
    }

    public function test_dedicated_admin_preview_root_opt_in_redirects_to_protected_workspace(): void
    {
        config(['furniture.admin_preview_landing' => true]);
        $this->get('/')->assertRedirect('/admin')->assertHeader('Location', '/admin')->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/admin')->assertRedirect(route('login'));
    }
}
