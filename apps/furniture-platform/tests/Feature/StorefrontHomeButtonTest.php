<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StorefrontHomeButtonTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        return array_merge(['hero_title' => 'Furniture', 'hero_subtitle' => 'Made for you', 'revision' => 0, 'hero_cta_label' => 'Contact us', 'hero_cta_path' => '/contact'], $extra);
    }

    public function test_upgrade_and_rollback_preserve_existing_text_and_versions(): void
    {
        foreach (['storefront_home_content', 'storefront_home_versions'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn(['hero_cta_label', 'hero_cta_path']));
        }
        DB::table('storefront_home_content')->where('id', 1)->update(['hero_title' => 'Existing', 'revision' => 7]);
        $migration = require database_path('migrations/2026_11_08_000000_add_home_hero_button.php');
        $migration->up();
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'hero_title' => 'Existing', 'revision' => 7, 'hero_cta_label' => null, 'hero_cta_path' => null]);
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 0, 'hero_cta_label' => null, 'hero_cta_path' => null]);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('storefront_home_content', 'hero_cta_label'));
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'hero_title' => 'Existing', 'revision' => 7]);
        $migration->up();
    }

    public function test_button_edits_legacy_saves_and_both_restores(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner)->post('/admin/website/home', $this->payload())->assertSessionHasNoErrors();
        $this->getJson('/storefront-api/home')->assertJsonPath('hero_cta_label', 'Contact us')->assertJsonPath('hero_cta_path', '/contact');
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 1, 'hero_cta_label' => 'Contact us', 'hero_cta_path' => '/contact']);
        $this->post('/admin/website/home', ['hero_title' => 'Legacy', 'hero_subtitle' => 'Client', 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('storefront_home_content', ['revision' => 2, 'hero_cta_label' => 'Contact us', 'hero_cta_path' => '/contact']);
        $this->post('/admin/website/home/restore', ['revision' => 2, 'source_revision' => 0])->assertSessionHasNoErrors();
        $this->getJson('/storefront-api/home')->assertJsonPath('hero_cta_label', null)->assertJsonPath('hero_cta_path', null);
        $this->post('/admin/website/home/restore', ['revision' => 3, 'source_revision' => 1])->assertSessionHasNoErrors();
        $this->getJson('/storefront-api/home')->assertJsonPath('hero_cta_label', 'Contact us')->assertJsonPath('hero_cta_path', '/contact');
        $this->withoutVite()->get('/admin/website/home')->assertSee('First slide button text')->assertSee('Button: Contact us');
    }

    public function test_unsafe_paths_invalid_text_stale_edits_and_nonowners_are_rejected(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        foreach (['//evil.test', 'https://evil.test', 'javascript:alert(1)', '/\\evil.test', '/%2f%2fevil.test', "/contact\nx", '/contact me', str_repeat('/a', 121), '', ['bad']] as $bad) {
            $this->post('/admin/website/home', $this->payload(['hero_cta_path' => $bad]))->assertSessionHasErrors('hero_cta_path');
        }
        foreach (['', str_repeat('a', 81), ['bad']] as $bad) {
            $this->post('/admin/website/home', $this->payload(['hero_cta_label' => $bad]))->assertSessionHasErrors('hero_cta_label');
        }
        $this->post('/admin/website/home', $this->payload(['revision' => 9]))->assertSessionHasErrors('revision');
        $this->actingAs(User::factory()->create(['is_owner' => false]))->post('/admin/website/home', $this->payload())->assertForbidden();
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'revision' => 0, 'hero_cta_label' => null, 'hero_cta_path' => null]);
        $this->assertDatabaseCount('storefront_home_versions', 1);
        $this->assertDatabaseCount('storefront_home_changes', 0);
    }

    public function test_safe_paths_and_escaped_history(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $this->post('/admin/website/home', $this->payload(['hero_cta_label' => '<img src=x>', 'hero_cta_path' => '/shop?category=workstations']))->assertSessionHasNoErrors();
        $this->withoutVite()->get('/admin/website/home')->assertSee('&lt;img src=x&gt;', false)->assertDontSee('<img src=x>', false);
        $this->post('/admin/website/home', $this->payload(['revision' => 1, 'hero_cta_path' => '/']))->assertSessionHasNoErrors();
    }
}
