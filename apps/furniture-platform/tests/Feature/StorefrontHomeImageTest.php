<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\Website\HeroImage;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class StorefrontHomeImageTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        return array_merge(['hero_title' => 'Furniture', 'hero_subtitle' => 'Made for you', 'revision' => 0, 'hero_image' => 'meeting'], $extra);
    }

    public function test_upgrade_keeps_existing_data_and_rollback_drops_only_new_column(): void
    {
        foreach (['storefront_home_content', 'storefront_home_versions'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('hero_image'));
        }
        DB::table('storefront_home_content')->where('id', 1)->update(['hero_title' => 'Existing', 'revision' => 7]);
        $migration = require database_path('migrations/2026_11_09_000000_add_home_hero_image.php');
        $migration->up();
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'hero_title' => 'Existing', 'revision' => 7, 'hero_image' => null]);
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 0, 'hero_image' => null]);
        config(['furniture.home_editor_enabled' => true]);
        $this->getJson('/storefront-api/home')->assertJsonPath('hero_image', 'workspace');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('storefront_home_content', 'hero_image'));
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'hero_title' => 'Existing', 'revision' => 7]);
        $migration->up();
    }

    public function test_images_save_old_clients_preserve_them_and_versions_restore(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $this->post('/admin/website/home', $this->payload())->assertSessionHasNoErrors();
        $this->getJson('/storefront-api/home')->assertJsonPath('hero_image', 'meeting');
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 1, 'hero_image' => 'meeting']);
        $this->post('/admin/website/home', ['hero_title' => 'Old client', 'hero_subtitle' => 'Safe', 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 2, 'hero_image' => 'meeting']);
        $this->post('/admin/website/home/restore', ['revision' => 2, 'source_revision' => 0])->assertSessionHasNoErrors();
        $this->getJson('/storefront-api/home')->assertJsonPath('hero_image', 'workspace');
        $this->post('/admin/website/home/restore', ['revision' => 3, 'source_revision' => 1])->assertSessionHasNoErrors();
        $this->getJson('/storefront-api/home')->assertJsonPath('hero_image', 'meeting');
        $this->withoutVite()->get('/admin/website/home')->assertSee('First slide background')->assertSee('Background: Meeting room');
    }

    public function test_only_listed_images_are_accepted_and_conflicts_and_nonowners_cannot_write(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        foreach (['https://evil.test/a.png', '../image.png', '__proto__', '', ['meeting'], 'MEETING'] as $bad) {
            $this->post('/admin/website/home', $this->payload(['hero_image' => $bad]))->assertSessionHasErrors('hero_image');
        }
        $this->post('/admin/website/home', $this->payload(['revision' => 1]))->assertSessionHasErrors('revision');
        $this->actingAs(User::factory()->create(['is_owner' => false]))->post('/admin/website/home', $this->payload())->assertForbidden();
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'revision' => 0, 'hero_image' => null]);
        $this->assertDatabaseCount('storefront_home_versions', 1);
        $this->assertDatabaseCount('storefront_home_changes', 0);
    }

    public function test_each_available_image_is_accepted(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        foreach (array_keys(HeroImage::choices()) as $revision => $key) {
            $this->post('/admin/website/home', $this->payload(['revision' => $revision, 'hero_image' => $key]))->assertSessionHasNoErrors();
            $this->getJson('/storefront-api/home')->assertJsonPath('hero_image', $key);
        }
    }

    public function test_failed_snapshot_rolls_back_image_change(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]))->withoutExceptionHandling();
        DB::listen(function (QueryExecuted $event): void {
            if (str_contains(strtolower($event->sql), 'insert into "storefront_home_versions"')) {
                throw new RuntimeException('Synthetic snapshot failure');
            }
        });
        try {
            $this->post('/admin/website/home', $this->payload());
            $this->fail('Expected snapshot failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic snapshot failure', $exception->getMessage());
            $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'revision' => 0, 'hero_image' => null]);
            $this->assertDatabaseCount('storefront_home_versions', 1);
            $this->assertDatabaseCount('storefront_home_changes', 0);
        }
    }
}
