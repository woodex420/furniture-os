<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Workspace\DashboardSummary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_pending_metrics_without_sample_business_values(): void
    {
        $owner = User::factory()->create(['is_owner' => true]);

        Livewire::actingAs($owner)->test(DashboardSummary::class)
            ->assertOk()
            ->assertSee('Business snapshot')
            ->assertSee('Total sales')
            ->assertSee('Open quotations')
            ->assertSee('Outstanding')
            ->assertSee('Active projects')
            ->assertSee('Pending')
            ->assertDontSee('2,450,000')
            ->assertDontSee('840,000');
    }

    public function test_guest_and_non_owner_cannot_render_the_component(): void
    {
        Livewire::test(DashboardSummary::class)->assertForbidden();
        $user = User::factory()->create(['is_owner' => false]);
        Livewire::actingAs($user)->test(DashboardSummary::class)->assertForbidden();
    }

    public function test_permission_is_rechecked_when_the_component_refreshes(): void
    {
        $owner = User::factory()->create(['is_owner' => true]);
        $component = Livewire::actingAs($owner)->test(DashboardSummary::class)->assertOk();

        $owner->forceFill(['is_owner' => false])->save();
        // Replace the authenticated instance, as happens at the start of a real request.
        $this->actingAs($owner->fresh());
        $component->call('$refresh')->assertForbidden();
    }
}
