<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class StorefrontHomeVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_upgrade_snapshots_the_current_home_without_inventing_old_versions(): void
    {
        Schema::drop('storefront_home_versions');
        DB::table('storefront_home_content')->where('id', 1)->update([
            'hero_title' => 'Existing furniture title', 'hero_subtitle' => 'Existing subtitle', 'revision' => 12,
        ]);
        $migration = require database_path('migrations/2026_11_06_000000_create_storefront_home_versions.php');
        $migration->up();
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'hero_title' => 'Existing furniture title', 'revision' => 12]);
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 12, 'hero_title' => 'Existing furniture title', 'hero_subtitle' => 'Existing subtitle', 'actor_id' => null, 'change_type' => 'baseline']);
        $this->assertDatabaseCount('storefront_home_versions', 1);
    }

    public function test_edits_and_restores_are_versioned_and_available_to_public_preview(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner)->post('/admin/website/home', ['hero_title' => 'First', 'hero_subtitle' => 'One', 'revision' => 0])->assertRedirect('/admin/website/home');
        $this->post('/admin/website/home', ['hero_title' => 'Second', 'hero_subtitle' => 'Two', 'revision' => 1])->assertRedirect('/admin/website/home');
        $this->post('/admin/website/home/restore', ['revision' => 2, 'source_revision' => 1])->assertRedirect('/admin/website/home');
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 3, 'hero_title' => 'First', 'hero_subtitle' => 'One', 'actor_id' => $owner->id, 'change_type' => 'restore', 'restored_from_revision' => 1]);
        $this->assertDatabaseCount('storefront_home_versions', 4);
        $this->assertDatabaseCount('storefront_home_changes', 3);
        $this->getJson('/storefront-api/home')->assertJsonPath('hero_title', 'First')->assertJsonPath('revision', 3);
        $this->withoutVite()->get('/admin/website/home')->assertOk()->assertSee('Recent versions')->assertSee('Restore version 1');
        // The original fallback remains representable as null and can be restored.
        $this->post('/admin/website/home/restore', ['revision' => 3, 'source_revision' => 0])->assertRedirect('/admin/website/home');
        $this->getJson('/storefront-api/home')->assertJsonPath('hero_title', null)->assertJsonPath('revision', 4);
    }

    public function test_restore_authorization_feature_flag_validation_and_conflicts(): void
    {
        $owner = User::factory()->create(['is_owner' => true]);
        $input = ['revision' => 0, 'source_revision' => 0];
        $this->post('/admin/website/home/restore', $input)->assertRedirect(route('login'));
        $this->actingAs($owner)->post('/admin/website/home/restore', $input)->assertNotFound();
        config(['furniture.home_editor_enabled' => true]);
        $this->post('/admin/website/home/restore', $input)->assertSessionHasErrors('source_revision');
        $this->post('/admin/website/home/restore', ['revision' => 0, 'source_revision' => 999])->assertSessionHasErrors('source_revision');
        $this->post('/admin/website/home/restore', ['revision' => 1, 'source_revision' => 0])->assertSessionHasErrors('revision');
        $this->post('/admin/website/home/restore', ['revision' => -1, 'source_revision' => 0])->assertSessionHasErrors('revision');
        $this->actingAs(User::factory()->create(['is_owner' => false]))->post('/admin/website/home/restore', $input)->assertForbidden();
        $this->assertDatabaseCount('storefront_home_versions', 1);
        $this->assertDatabaseCount('storefront_home_changes', 0);
    }

    public function test_history_escapes_authored_text_and_revoked_owner_cannot_restore(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner)->post('/admin/website/home', ['hero_title' => '<script>alert(1)</script>', 'hero_subtitle' => 'Text', 'revision' => 0])->assertRedirect();
        $this->withoutVite()->get('/admin/website/home')->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        User::query()->whereKey($owner->id)->update(['is_owner' => false]);
        $this->post('/admin/website/home/restore', ['revision' => 1, 'source_revision' => 0])->assertForbidden();
        $this->assertDatabaseCount('storefront_home_versions', 2);
    }

    public function test_failure_to_write_a_snapshot_rolls_back_content_and_audit(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]))->withoutExceptionHandling();
        DB::listen(function (QueryExecuted $event): void {
            if (str_contains(strtolower($event->sql), 'insert into "storefront_home_versions"')) {
                throw new RuntimeException('Synthetic version failure');
            }
        });
        try {
            $this->post('/admin/website/home', ['hero_title' => 'Test', 'hero_subtitle' => 'Test', 'revision' => 0]);
            $this->fail('Expected version failure.');
        } catch (RuntimeException $e) {
            $this->assertSame('Synthetic version failure', $e->getMessage());
            $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'hero_title' => null, 'revision' => 0]);
            $this->assertDatabaseCount('storefront_home_changes', 0);
            $this->assertDatabaseCount('storefront_home_versions', 1);
        }
    }
}
