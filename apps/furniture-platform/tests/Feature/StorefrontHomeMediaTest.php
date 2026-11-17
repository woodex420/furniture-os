<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontHomeMediaTest extends TestCase
{
    use RefreshDatabase;

    private function input(array $extra = []): array
    {
        return array_merge(['hero_title' => 'Furniture', 'hero_subtitle' => 'Design', 'revision' => 0], $extra);
    }

    private function image(User $owner, string $title = 'Example image'): string
    {
        $id = (string) Str::uuid();
        DB::table('furniture_media')->insert(['id' => $id, 'title' => $title, 'mime' => 'image/png', 'width' => 1, 'height' => 1, 'bytes' => 4, 'created_by' => $owner->id, 'created_at' => now(), 'updated_at' => now()]);
        Storage::disk('furniture_media')->put($id.'.png', 'test');

        return $id;
    }

    public function test_upgrade_preserves_home_and_history_and_rollback_removes_reference_only(): void
    {
        $migration = require database_path('migrations/2026_11_11_000000_add_home_hero_media_reference.php');
        $migration->down();
        DB::table('storefront_home_content')->where('id', 1)->update(['hero_title' => 'Existing', 'revision' => 5]);
        $migration->up();
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'hero_title' => 'Existing', 'revision' => 5, 'hero_media_id' => null]);
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 0, 'hero_media_id' => null]);
    }

    public function test_only_current_saved_image_is_public_and_restore_changes_access(): void
    {
        Storage::fake('furniture_media');
        config(['furniture.home_editor_enabled' => true, 'furniture.media_library_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $first = $this->image($owner, 'First');
        $second = $this->image($owner, 'Second');
        $url = fn (string $id) => '/storefront-api/home/image/'.$id;
        $this->get($url($first))->assertNotFound();
        $this->actingAs($owner)->post('/admin/website/home', $this->input(['hero_media_id' => $first]))->assertSessionHasNoErrors();
        $this->getJson('/storefront-api/home')->assertJsonPath('hero_media_id', $first);
        auth()->logout();
        $response = $this->get($url($first))->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertCount(0, $response->headers->getCookies());
        $this->get($url($second))->assertNotFound();
        $this->actingAs($owner)->post('/admin/website/home', $this->input(['revision' => 1, 'hero_media_id' => $second]))->assertSessionHasNoErrors();
        $this->get($url($first))->assertNotFound();
        $this->get($url($second))->assertOk();
        $this->post('/admin/website/home/restore', ['revision' => 2, 'source_revision' => 1])->assertSessionHasNoErrors();
        $this->get($url($first))->assertOk();
        $this->get($url($second))->assertNotFound();
        $this->post('/admin/website/home/restore', ['revision' => 3, 'source_revision' => 0])->assertSessionHasNoErrors();
        $this->get($url($first))->assertNotFound();
        $this->getJson('/storefront-api/home')->assertJsonPath('hero_media_id', null);
        $this->withoutVite()->get('/admin/website/home')->assertSee('First · uploaded image');
        config(['furniture.home_editor_enabled' => false]);
        $this->get($url($first))->assertNotFound();
    }

    public function test_old_clients_and_disabling_library_preserve_selected_image(): void
    {
        Storage::fake('furniture_media');
        config(['furniture.home_editor_enabled' => true, 'furniture.media_library_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $id = $this->image($owner);
        $this->actingAs($owner)->post('/admin/website/home', $this->input(['hero_media_id' => $id]))->assertSessionHasNoErrors();
        config(['furniture.media_library_enabled' => false]);
        $this->post('/admin/website/home', $this->input(['revision' => 1]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('storefront_home_versions', ['revision' => 2, 'hero_media_id' => $id]);
        $this->get('/storefront-api/home/image/'.$id)->assertOk();
        $this->post('/admin/website/home', $this->input(['revision' => 2, 'hero_media_id' => null]))->assertSessionHasErrors('hero_media_id');
        $this->withoutVite()->get('/admin/website/home')->assertDontSee('name="hero_media_id"', false);
        config(['furniture.media_library_enabled' => true]);
        $this->post('/admin/website/home', $this->input(['revision' => 2, 'hero_media_id' => null]))->assertSessionHasNoErrors();
        $this->get('/storefront-api/home/image/'.$id)->assertNotFound();
    }

    public function test_invalid_missing_files_conflicts_and_unauthorized_writes_fail_closed(): void
    {
        Storage::fake('furniture_media');
        config(['furniture.home_editor_enabled' => true, 'furniture.media_library_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $id = $this->image($owner);
        $this->actingAs($owner);
        foreach (['../foo', (string) Str::uuid(), ['bad']] as $bad) {
            $this->post('/admin/website/home', $this->input(['hero_media_id' => $bad]))->assertSessionHasErrors('hero_media_id');
        }
        $this->post('/admin/website/home', $this->input(['revision' => 1, 'hero_media_id' => $id]))->assertSessionHasErrors('revision');
        Storage::disk('furniture_media')->delete($id.'.png');
        $this->post('/admin/website/home', $this->input(['hero_media_id' => $id]))->assertSessionHasErrors('hero_media_id');
        $this->actingAs(User::factory()->create(['is_owner' => false]))->post('/admin/website/home', $this->input(['hero_media_id' => $id]))->assertForbidden();
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'revision' => 0, 'hero_media_id' => null]);
        $this->assertDatabaseCount('storefront_home_versions', 1);
    }

    public function test_restoring_a_missing_image_is_rejected_without_changing_current_content(): void
    {
        Storage::fake('furniture_media');
        config(['furniture.home_editor_enabled' => true, 'furniture.media_library_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $id = $this->image($owner);
        $this->actingAs($owner)->post('/admin/website/home', $this->input(['hero_media_id' => $id]))->assertSessionHasNoErrors();
        $this->post('/admin/website/home', $this->input(['revision' => 1, 'hero_media_id' => null]))->assertSessionHasNoErrors();
        Storage::disk('furniture_media')->delete($id.'.png');
        $this->post('/admin/website/home/restore', ['revision' => 2, 'source_revision' => 1])->assertSessionHasErrors('hero_media_id');
        $this->assertDatabaseHas('storefront_home_content', ['id' => 1, 'revision' => 2, 'hero_media_id' => null]);
        $this->assertDatabaseCount('storefront_home_versions', 3);
        $this->assertDatabaseCount('storefront_home_changes', 2);
    }

    public function test_history_reference_prevents_deleting_media_metadata(): void
    {
        Storage::fake('furniture_media');
        config(['furniture.home_editor_enabled' => true, 'furniture.media_library_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $id = $this->image($owner);
        $this->actingAs($owner)->post('/admin/website/home', $this->input(['hero_media_id' => $id]))->assertSessionHasNoErrors();
        $this->post('/admin/website/home', $this->input(['revision' => 1, 'hero_media_id' => null]))->assertSessionHasNoErrors();
        $this->expectException(QueryException::class);
        DB::table('furniture_media')->where('id', $id)->delete();
    }
}
