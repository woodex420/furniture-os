<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ServiceCopyPreviewTest extends TestCase
{
    use RefreshDatabase;

    private const SLUG = 'space-planning-design';

    private function data(array $extra = []): array
    {
        return array_merge(['title' => '  Sample service  ', 'tagline' => '  Sample tagline  ', 'revision' => 0], $extra);
    }

    public function test_flag_defaults_off_and_owner_permissions_apply(): void
    {
        $this->get('/storefront-api/services/copy')->assertNotFound();
        $this->withoutVite()->actingAs(User::factory()->create(['is_owner' => true]))->get('/admin/website/services')->assertNotFound();
        config(['furniture.service_copy_enabled' => true]);
        $this->getJson('/storefront-api/services/copy')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonCount(7);
        $this->app['auth']->logout();
        $this->get('/admin/website/services')->assertRedirect(route('login'));
        $this->post('/admin/website/services/'.self::SLUG, $this->data())->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['is_owner' => false]));
        $this->get('/admin/website/services')->assertForbidden();
        $this->post('/admin/website/services/'.self::SLUG, $this->data())->assertForbidden();
        $this->post('/admin/website/services/'.self::SLUG.'/restore', ['revision' => 0, 'source_revision' => 0])->assertForbidden();
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner)->get('/admin/website/services')->assertOk()->assertSee('Service pages')->assertSee(self::SLUG);
        $this->get('/admin/website/services/not-a-service')->assertNotFound();
        $this->post('/admin/website/services/not-a-service', $this->data())->assertNotFound();
        $this->assertDatabaseCount('storefront_service_copy_versions', 7);
    }

    public function test_save_conflict_validation_isolation_and_restore(): void
    {
        config(['furniture.service_copy_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->withoutVite()->actingAs($owner);
        $url = '/admin/website/services/'.self::SLUG;
        $this->get($url)->assertOk()->assertSee('name="_token"', false);
        $this->post($url, $this->data(['title' => ' ']))->assertSessionHasErrors('title');
        $this->post($url, $this->data(['tagline' => str_repeat('x', 241)]))->assertSessionHasErrors('tagline');
        $this->post($url, $this->data())->assertRedirect($url);
        $this->assertDatabaseHas('storefront_service_copy', ['slug' => self::SLUG, 'title' => 'Sample service', 'tagline' => 'Sample tagline', 'revision' => 1]);
        $this->assertDatabaseHas('storefront_service_copy_versions', ['slug' => self::SLUG, 'revision' => 1, 'actor_id' => $owner->id, 'change_type' => 'edit']);
        $this->assertDatabaseHas('storefront_service_copy', ['slug' => 'custom-design', 'title' => null, 'tagline' => null, 'revision' => 0]);
        $this->post($url, $this->data(['title' => 'Stale']))->assertSessionHasErrors('revision');
        $this->post($url.'/restore', ['revision' => 1, 'source_revision' => 99])->assertSessionHasErrors('source_revision');
        $this->post($url.'/restore', ['revision' => 1, 'source_revision' => 0])->assertRedirect($url);
        $this->assertDatabaseHas('storefront_service_copy', ['slug' => self::SLUG, 'title' => null, 'tagline' => null, 'revision' => 2]);
        $this->assertDatabaseHas('storefront_service_copy_versions', ['slug' => self::SLUG, 'revision' => 2, 'actor_id' => $owner->id, 'change_type' => 'restore', 'restored_from_revision' => 0]);
        $this->post($url.'/restore', ['revision' => 2, 'source_revision' => 1])->assertRedirect($url);
        $this->assertDatabaseHas('storefront_service_copy', ['slug' => self::SLUG, 'title' => 'Sample service', 'revision' => 3]);
        $this->assertDatabaseCount('storefront_service_copy_versions', 10);
    }

    public function test_restore_cannot_use_another_services_revision(): void
    {
        config(['furniture.service_copy_enabled' => true]);
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $this->post('/admin/website/services/custom-design', $this->data())->assertRedirect('/admin/website/services/custom-design');
        $url = '/admin/website/services/'.self::SLUG;
        $this->post($url.'/restore', ['revision' => 0, 'source_revision' => 1])->assertSessionHasErrors('source_revision');
        $this->assertDatabaseHas('storefront_service_copy', ['slug' => self::SLUG, 'revision' => 0, 'title' => null]);
        $this->assertDatabaseCount('storefront_service_copy_versions', 8);
    }

    public function test_plain_text_is_escaped_in_admin_and_public_response_has_no_private_metadata(): void
    {
        config(['furniture.service_copy_enabled' => true]);
        $this->withoutVite()->actingAs(User::factory()->create(['is_owner' => true]));
        $url = '/admin/website/services/'.self::SLUG;
        $this->post($url, $this->data(['title' => '<script>alert(1)</script>']))->assertRedirect($url);
        $this->get($url)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $response = $this->getJson('/storefront-api/services/copy')->assertOk();
        $this->assertCount(0, $response->headers->getCookies());
        $row = collect($response->json())->firstWhere('slug', self::SLUG);
        $this->assertSame('<script>alert(1)</script>', $row['title']);
        $this->assertSame(['slug', 'title', 'tagline', 'revision'], array_keys($row));
    }

    public function test_revoked_owner_and_history_failure_never_save_partial_content(): void
    {
        config(['furniture.service_copy_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner);
        User::query()->whereKey($owner->id)->update(['is_owner' => false]);
        $url = '/admin/website/services/'.self::SLUG;
        $this->post($url, $this->data())->assertForbidden();
        User::query()->whereKey($owner->id)->update(['is_owner' => true]);
        $this->actingAs($owner->fresh())->withoutExceptionHandling();
        DB::listen(function (QueryExecuted $event): void {
            if (str_contains(strtolower($event->sql), 'insert into "storefront_service_copy_versions"')) {
                throw new RuntimeException('Synthetic version failure');
            }
        });
        try {
            $this->post($url, $this->data());
            $this->fail('Expected version failure');
        } catch (RuntimeException $e) {
            $this->assertSame('Synthetic version failure', $e->getMessage());
            $this->assertDatabaseHas('storefront_service_copy', ['slug' => self::SLUG, 'title' => null, 'revision' => 0]);
            $this->assertDatabaseCount('storefront_service_copy_versions', 7);
        }
    }
}
