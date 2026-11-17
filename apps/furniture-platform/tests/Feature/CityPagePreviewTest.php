<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class CityPagePreviewTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $extra = []): array
    {
        return array_merge(['slug' => 'example-city', 'city_name' => 'Example City', 'title' => 'Example service', 'summary' => 'Sample summary', 'body' => 'Sample body', 'published' => '0'], $extra);
    }

    public function test_flag_is_off_by_default_and_no_locations_are_seeded(): void
    {
        $this->assertDatabaseCount('storefront_city_pages', 0);
        $this->get('/storefront-api/cities')->assertNotFound();
        $this->get('/storefront-api/cities/example-city')->assertNotFound();
        $this->withoutVite()->actingAs(User::factory()->create(['is_owner' => true]))->get('/admin/website/cities')->assertNotFound();
    }

    public function test_owner_access_validation_and_duplicate_slug(): void
    {
        config(['furniture.city_pages_enabled' => true]);
        $this->get('/admin/website/cities')->assertRedirect(route('login'));
        $this->post('/admin/website/cities', $this->data())->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['is_owner' => false]));
        $this->get('/admin/website/cities')->assertForbidden();
        $this->post('/admin/website/cities', $this->data())->assertForbidden();
        $this->withoutVite()->actingAs(User::factory()->create(['is_owner' => true]));
        $this->get('/admin/website/cities')->assertOk()->assertSee('No city pages yet.');
        $this->get('/admin/website/cities/create')->assertOk()->assertSee('name="_token"', false);
        foreach (['../example', 'A', '-abc', 'a--b', 'a'.str_repeat('b', 80)] as $slug) {
            $this->post('/admin/website/cities', $this->data(['slug' => $slug]))->assertSessionHasErrors('slug');
        }
        $this->post('/admin/website/cities', $this->data(['published' => 'public']))->assertSessionHasErrors('published');
        $this->post('/admin/website/cities', $this->data(['body' => str_repeat('a', 4001)]))->assertSessionHasErrors('body');
        $this->post('/admin/website/cities', $this->data(['summary' => ' ']))->assertSessionHasErrors('summary');
        $this->post('/admin/website/cities', $this->data())->assertRedirect('/admin/website/cities/example-city/edit');
        $this->post('/admin/website/cities', $this->data())->assertSessionHasErrors('slug');
        $this->assertDatabaseCount('storefront_city_pages', 1);
        $this->assertDatabaseCount('storefront_city_page_versions', 1);
    }

    public function test_drafts_publish_stale_conflict_and_restore_visibility(): void
    {
        config(['furniture.city_pages_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->withoutVite()->actingAs($owner);
        $this->post('/admin/website/cities', $this->data())->assertRedirect('/admin/website/cities/example-city/edit');
        $this->getJson('/storefront-api/cities')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/storefront-api/cities/example-city')->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
        $url = '/admin/website/cities/example-city';
        $this->post($url, $this->data(['revision' => 1, 'published' => '1', 'title' => 'Published title']))->assertRedirect($url.'/edit');
        $public = $this->getJson('/storefront-api/cities/example-city')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('title', 'Published title');
        $this->assertCount(0, $public->headers->getCookies());
        $this->assertSame(['slug', 'city_name', 'title', 'summary', 'body'], array_keys($public->json()));
        $this->getJson('/storefront-api/cities')->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'example-city')->assertJsonMissing(['body' => 'Sample body']);
        $this->post($url, $this->data(['revision' => 1, 'title' => 'Stale']))->assertSessionHasErrors('revision');
        $this->post($url.'/restore', ['revision' => 2, 'source_revision' => 1])->assertRedirect($url.'/edit');
        $this->assertDatabaseHas('storefront_city_pages', ['slug' => 'example-city', 'revision' => 3, 'published' => false, 'title' => 'Example service']);
        $this->getJson('/storefront-api/cities/example-city')->assertNotFound();
        $this->post($url.'/restore', ['revision' => 3, 'source_revision' => 2])->assertRedirect($url.'/edit');
        $this->getJson('/storefront-api/cities/example-city')->assertOk()->assertJsonPath('title', 'Published title');
        $this->assertDatabaseHas('storefront_city_page_versions', ['slug' => 'example-city', 'revision' => 4, 'actor_id' => $owner->id, 'change_type' => 'restore', 'restored_from_revision' => 2]);
    }

    public function test_per_city_history_is_isolated_and_plain_text_is_escaped(): void
    {
        config(['furniture.city_pages_enabled' => true]);
        $this->withoutVite()->actingAs(User::factory()->create(['is_owner' => true]));
        $this->post('/admin/website/cities', $this->data(['title' => '<script>alert(1)</script>', 'published' => '1']));
        $this->post('/admin/website/cities', $this->data(['slug' => 'other-city']));
        $this->post('/admin/website/cities/other-city', $this->data(['revision' => 1]));
        $this->post('/admin/website/cities/example-city/restore', ['revision' => 1, 'source_revision' => 2])->assertSessionHasErrors('source_revision');
        $this->get('/admin/website/cities/example-city/edit')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->getJson('/storefront-api/cities/example-city')->assertJsonPath('title', '<script>alert(1)</script>');
        $this->assertDatabaseHas('storefront_city_pages', ['slug' => 'example-city', 'revision' => 1]);
    }

    public function test_public_list_is_paginated_and_never_returns_drafts(): void
    {
        config(['furniture.city_pages_enabled' => true]);
        for ($i = 0; $i < 22; $i++) {
            DB::table('storefront_city_pages')->insert(['slug' => 'city-'.$i, 'city_name' => 'City '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'title' => 'Title', 'summary' => 'Summary', 'body' => 'Private body', 'published' => $i < 21, 'revision' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->getJson('/storefront-api/cities?page=1')->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('last_page', 2)->assertJsonMissing(['body' => 'Private body']);
        $this->getJson('/storefront-api/cities?page=2')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'city-20');
        $this->getJson('/storefront-api/cities?page=0')->assertUnprocessable();
        $this->getJson('/storefront-api/cities/city-21')->assertNotFound();
        $this->withoutVite()->actingAs(User::factory()->create(['is_owner' => true]))
            ->get('/admin/website/cities')->assertOk()->assertSee('/admin/website/cities?page=2', false)->assertDontSee('http://localhost/admin/website/cities', false);
    }

    public function test_revoked_owner_and_failed_snapshot_do_not_leave_partial_data(): void
    {
        config(['furniture.city_pages_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner);
        User::query()->whereKey($owner->id)->update(['is_owner' => false]);
        $this->post('/admin/website/cities', $this->data())->assertForbidden();
        User::query()->whereKey($owner->id)->update(['is_owner' => true]);
        $this->actingAs($owner->fresh())->withoutExceptionHandling();
        DB::listen(function (QueryExecuted $event): void {
            if (str_contains(strtolower($event->sql), 'insert into "storefront_city_page_versions"')) {
                throw new RuntimeException('Synthetic snapshot failure');
            }
        });
        try {
            $this->post('/admin/website/cities', $this->data());
            $this->fail('Expected snapshot failure');
        } catch (RuntimeException $e) {
            $this->assertSame('Synthetic snapshot failure', $e->getMessage());
            $this->assertDatabaseCount('storefront_city_pages', 0);
            $this->assertDatabaseCount('storefront_city_page_versions', 0);
        }
    }
}
