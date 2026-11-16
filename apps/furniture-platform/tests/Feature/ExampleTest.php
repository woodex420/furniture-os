<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_incomplete_storefront_is_not_presented_as_a_live_shop(): void
    {
        $this->get('/')->assertStatus(503)->assertSee('storefront is not available yet');
    }
}
