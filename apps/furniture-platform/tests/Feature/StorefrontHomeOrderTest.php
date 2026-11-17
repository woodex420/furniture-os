<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class StorefrontHomeOrderTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $extra = []): array
    {
        return array_merge(['hero_title' => 'Furniture', 'hero_subtitle' => 'Design', 'revision' => 0], $extra);
    }

    public function test_upgrade_preserves_existing_home_and_versions_with_original_order(): void
    {
        Schema::table('storefront_home_content', fn (Blueprint $table) => $table->dropColumn('hero_order'));
        Schema::table('storefront_home_versions', fn (Blueprint $table) => $table->dropColumn('hero_order'));
        DB::table('storefront_home_content')->where('id', 1)->update(['hero_title' => 'Existing', 'revision' => 7]);
        DB::table('storefront_home_versions')->where('revision', 0)->update(['hero_title' => 'Old']);
        $migration = require database_path('migrations/2026_11_07_000000_add_home_hero_text_order.php');
        $migration->up();
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'hero_title' => 'Existing', 'revision' => 7, 'hero_order' => null]);
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 0, 'hero_title' => 'Old', 'hero_order' => null]);
        config(['furniture.home_editor_enabled' => true]);
        $this->getJson('/storefront-api/home')->assertOk()->assertJsonPath('hero_order', 'title_first');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('storefront_home_content', 'hero_order'));
        $this->assertFalse(Schema::hasColumn('storefront_home_versions', 'hero_order'));
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'hero_title' => 'Existing', 'revision' => 7]);
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 0, 'hero_title' => 'Old']);
        $migration->up();
    }

    public function test_order_saves_preserves_old_clients_and_restores_with_versions(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner)->post('/admin/website/home', $this->data(['hero_order' => 'subtitle_first']))->assertSessionHasNoErrors()->assertRedirect('/admin/website/home');
        $this->getJson('/storefront-api/home')->assertJsonPath('hero_order', 'subtitle_first')->assertJsonPath('revision', 1);
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 1, 'hero_order' => 'subtitle_first', 'actor_id' => $owner->id]);
        // Older clients that do not send the new field must not reset it.
        $this->post('/admin/website/home', $this->data(['revision' => 1]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 2, 'hero_order' => 'subtitle_first']);
        $this->post('/admin/website/home/restore', ['revision' => 2, 'source_revision' => 0])->assertSessionHasNoErrors();
        $this->getJson('/storefront-api/home')->assertJsonPath('hero_order', 'title_first')->assertJsonPath('revision', 3);
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 3, 'hero_order' => 'title_first', 'restored_from_revision' => 0]);
        $this->withoutVite()->get('/admin/website/home')->assertSee('First slide text order')->assertSee('Order: Subtitle, then title');
        $this->post('/admin/website/home/restore', ['revision' => 3, 'source_revision' => 1])->assertSessionHasNoErrors();
        $this->getJson('/storefront-api/home')->assertJsonPath('hero_order', 'subtitle_first')->assertJsonPath('revision', 4);
    }

    public function test_invalid_stale_and_unauthorized_orders_do_not_mutate_anything(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner);
        foreach (['arbitrary', '', ['title_first'], 'SUBTITLE_FIRST'] as $bad) {
            $this->post('/admin/website/home', $this->data(['hero_order' => $bad]))->assertSessionHasErrors('hero_order');
        }
        $this->post('/admin/website/home', $this->data(['hero_order' => 'subtitle_first', 'revision' => 1]))->assertSessionHasErrors('revision');
        $this->actingAs(User::factory()->create(['is_owner' => false]))->post('/admin/website/home', $this->data(['hero_order' => 'subtitle_first']))->assertForbidden();
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'hero_order' => null, 'revision' => 0]);
        $this->assertDatabaseCount('storefront_home_versions', 1);
        $this->assertDatabaseCount('storefront_home_changes', 0);
    }

    public function test_version_write_failure_rolls_back_order_and_audit(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]))->withoutExceptionHandling();
        DB::listen(function (QueryExecuted $event): void {
            if (str_contains(strtolower($event->sql), 'insert into "storefront_home_versions"')) {
                throw new RuntimeException('Synthetic snapshot failure');
            }
        });
        try {
            $this->post('/admin/website/home', $this->data(['hero_order' => 'subtitle_first']));
            $this->fail('Expected snapshot failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic snapshot failure', $exception->getMessage());
            $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'hero_order' => null, 'revision' => 0]);
            $this->assertDatabaseCount('storefront_home_changes', 0);
            $this->assertDatabaseCount('storefront_home_versions', 1);
        }
    }
}
