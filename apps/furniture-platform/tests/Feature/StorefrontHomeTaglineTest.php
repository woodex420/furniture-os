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

class StorefrontHomeTaglineTest extends TestCase
{
    use RefreshDatabase;

    private function input(array $extra = []): array
    {
        return array_merge(['hero_title' => 'Furniture', 'hero_subtitle' => 'Design', 'revision' => 0, 'home_tagline' => 'Built for your workspace'], $extra);
    }

    public function test_upgrade_preserves_old_content_and_versions_and_rollback_isolated(): void
    {
        foreach (['storefront_home_content', 'storefront_home_versions'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('home_tagline'));
        }
        DB::table('storefront_home_content')->where('id', 1)->update(['hero_title' => 'Existing', 'revision' => 7]);
        $migration = require database_path('migrations/2026_11_12_000000_add_home_tagline.php');
        $migration->up();
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'hero_title' => 'Existing', 'revision' => 7, 'home_tagline' => null]);
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 0, 'home_tagline' => null]);
        config(['furniture.home_editor_enabled' => true]);
        $this->getJson('/storefront-api/home')->assertJsonPath('home_tagline', null);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('storefront_home_content', 'home_tagline'));
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'hero_title' => 'Existing', 'revision' => 7]);
        $migration->up();
    }

    public function test_edits_preserve_older_clients_and_restore_both_default_and_custom_text(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner)->post('/admin/website/home', $this->input(['home_tagline' => '  Our furniture  ']))->assertSessionHasNoErrors();
        $this->getJson('/storefront-api/home')->assertJsonPath('home_tagline', 'Our furniture')->assertJsonPath('revision', 1);
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 1, 'home_tagline' => 'Our furniture', 'actor_id' => $owner->id]);
        $this->post('/admin/website/home', ['hero_title' => 'Older client', 'hero_subtitle' => 'Text', 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 2, 'home_tagline' => 'Our furniture']);
        $this->post('/admin/website/home/restore', ['revision' => 2, 'source_revision' => 0])->assertSessionHasNoErrors();
        $this->getJson('/storefront-api/home')->assertJsonPath('home_tagline', null)->assertJsonPath('revision', 3);
        $this->post('/admin/website/home/restore', ['revision' => 3, 'source_revision' => 1])->assertSessionHasNoErrors();
        $this->getJson('/storefront-api/home')->assertJsonPath('home_tagline', 'Our furniture')->assertJsonPath('revision', 4);
        $this->withoutVite()->get('/admin/website/home')->assertSee('Homepage tagline')->assertSee('Tagline: Our furniture');
    }

    public function test_validation_conflicts_authorization_and_flag_protect_content(): void
    {
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner)->post('/admin/website/home', $this->input())->assertNotFound();
        config(['furniture.home_editor_enabled' => true]);
        foreach (['', '   ', str_repeat('a', 241), ['invalid']] as $bad) {
            $this->post('/admin/website/home', $this->input(['home_tagline' => $bad]))->assertSessionHasErrors('home_tagline');
        }
        $this->post('/admin/website/home', $this->input(['revision' => 1]))->assertSessionHasErrors('revision');
        $this->actingAs(User::factory()->create(['is_owner' => false]))->post('/admin/website/home', $this->input())->assertForbidden();
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'revision' => 0, 'home_tagline' => null]);
        $this->assertDatabaseCount('storefront_home_versions', 1);
        $this->assertDatabaseCount('storefront_home_changes', 0);
    }

    public function test_authored_tagline_is_escaped_in_history_and_editor(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $this->post('/admin/website/home', $this->input(['home_tagline' => '<script>alert(1)</script>']))->assertSessionHasNoErrors();
        $this->withoutVite()->get('/admin/website/home')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_snapshot_failure_rolls_back_tagline_and_audit(): void
    {
        config(['furniture.home_editor_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]))->withoutExceptionHandling();
        DB::listen(function (QueryExecuted $event): void {
            if (str_contains(strtolower($event->sql), 'insert into "storefront_home_versions"')) {
                throw new RuntimeException('Synthetic version failure');
            }
        });
        try {
            $this->post('/admin/website/home', $this->input());
            $this->fail('Expected snapshot failure.');
        } catch (RuntimeException $e) {
            $this->assertSame('Synthetic version failure', $e->getMessage());
            $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'home_tagline' => null, 'revision' => 0]);
            $this->assertDatabaseCount('storefront_home_versions', 1);
            $this->assertDatabaseCount('storefront_home_changes', 0);
        }
    }
}
