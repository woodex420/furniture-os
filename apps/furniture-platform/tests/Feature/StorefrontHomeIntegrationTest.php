<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Tests\TestCase;

class StorefrontHomeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function input(array $overrides = []): array
    {
        return array_merge(['hero_title' => '  Sample headline  ', 'hero_subtitle' => '  Sample description  ', 'revision' => 0], $overrides);
    }

    public function test_feature_is_off_by_default(): void
    {
        $this->get('/storefront-api/home')->assertNotFound();
        $this->withoutVite()->actingAs(User::factory()->create(['is_owner' => true]))->get('/admin/website/home')->assertNotFound();
        $this->post('/admin/website/home', $this->input())->assertNotFound();
        $this->assertDatabaseCount('storefront_home_changes', 0);
    }

    public function test_only_an_authorized_owner_can_edit_and_public_reads_are_no_store(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $public = $this->getJson('/storefront-api/home')->assertOk()->assertJsonPath('hero_title', null)->assertJsonPath('revision', 0)->assertHeader('Cache-Control', 'no-store, private');
        $this->assertCount(0, $public->headers->getCookies());
        $this->get('/admin/website/home')->assertRedirect(route('login'));
        $this->post('/admin/website/home', $this->input())->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['is_owner' => false]));
        $this->get('/admin/website/home')->assertForbidden();
        $this->post('/admin/website/home', $this->input())->assertForbidden();
        $owner = User::factory()->create(['is_owner' => true]);
        $this->withoutVite()->actingAs($owner)->get('/admin/website/home')->assertOk()->assertSee('name="_token"', false);
        $response = $this->post('/admin/website/home', $this->input());
        $this->assertSame('/admin/website/home', $response->headers->get('Location'));
        $this->getJson('/storefront-api/home')->assertOk()->assertJsonPath('hero_title', 'Sample headline')->assertJsonPath('hero_subtitle', 'Sample description')->assertJsonPath('revision', 1);
        $this->assertDatabaseHas('storefront_home_changes', ['actor_id' => $owner->id, 'revision' => 1]);
        Gate::define('storefront.edit', fn (): bool => false);
        $this->post('/admin/website/home', $this->input(['revision' => 1]))->assertForbidden();
        $this->assertDatabaseCount('storefront_home_changes', 1);
    }

    public function test_validation_and_revision_conflicts_do_not_overwrite(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $this->post('/admin/website/home', $this->input(['hero_title' => ' ']))->assertSessionHasErrors('hero_title');
        $this->post('/admin/website/home', $this->input(['hero_subtitle' => str_repeat('x', 241)]))->assertSessionHasErrors('hero_subtitle');
        $this->post('/admin/website/home', $this->input())->assertRedirect('/admin/website/home');
        $this->post('/admin/website/home', $this->input(['hero_title' => 'Stale write']))->assertSessionHasErrors('revision');
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'hero_title' => 'Sample headline', 'revision' => 1]);
        $this->assertDatabaseCount('storefront_home_changes', 1);
    }

    public function test_revoked_owner_is_rechecked_inside_the_transaction(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner);
        User::query()->whereKey($owner->id)->update(['is_owner' => false]);
        $this->post('/admin/website/home', $this->input())->assertForbidden();
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'hero_title' => null, 'revision' => 0]);
        $this->assertDatabaseCount('storefront_home_changes', 0);
    }

    public function test_audit_failure_rolls_back_the_change(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $this->withoutExceptionHandling();
        DB::listen(function (QueryExecuted $event): void {
            if (str_contains(strtolower($event->sql), 'insert into "storefront_home_changes"')) {
                throw new RuntimeException('Synthetic audit failure');
            }
        });
        try {
            $this->post('/admin/website/home', $this->input());
            $this->fail('Expected audit failure.');
        } catch (RuntimeException $e) {
            $this->assertSame('Synthetic audit failure', $e->getMessage());
            $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'hero_title' => null, 'revision' => 0]);
            $this->assertDatabaseCount('storefront_home_changes', 0);
        }
    }
}
