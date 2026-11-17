<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class BlogPostPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $extra = []): array
    {
        return array_merge(['slug' => 'example-article', 'title' => 'Example article', 'summary' => 'Sample summary', 'body' => 'Sample body', 'published' => '0'], $extra);
    }

    public function test_feature_is_off_by_default_and_no_demo_articles_are_imported(): void
    {
        $this->assertDatabaseCount('storefront_blog_posts', 0);
        $this->get('/storefront-api/blog')->assertNotFound();
        $this->get('/storefront-api/blog/ergonomic-office-guide-2025')->assertNotFound();
        $this->withoutVite()->actingAs(User::factory()->create(['is_owner' => true]))->get('/admin/website/blog')->assertNotFound();
    }

    public function test_owner_access_validation_and_duplicate_slug(): void
    {
        config(['furniture.blog_posts_enabled' => true]);
        $this->get('/admin/website/blog')->assertRedirect(route('login'));
        $this->post('/admin/website/blog', $this->data())->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['is_owner' => false]));
        $this->get('/admin/website/blog')->assertForbidden();
        $this->post('/admin/website/blog', $this->data())->assertForbidden();
        $this->post('/admin/website/blog/example-article/restore', ['revision' => 1, 'source_revision' => 1])->assertForbidden();
        $this->withoutVite()->actingAs(User::factory()->create(['is_owner' => true]));
        $this->get('/admin/website/blog')->assertOk()->assertSee('No Furniture articles yet.');
        $this->get('/admin/website/blog/create')->assertOk()->assertSee('name="_token"', false);
        foreach (['../example', 'A', '-abc', 'a--b', 'a'.str_repeat('b', 80)] as $slug) {
            $this->post('/admin/website/blog', $this->data(['slug' => $slug]))->assertSessionHasErrors('slug');
        }
        $this->post('/admin/website/blog', $this->data(['published' => 'public']))->assertSessionHasErrors('published');
        $this->post('/admin/website/blog', $this->data(['body' => str_repeat('a', 12001)]))->assertSessionHasErrors('body');
        $this->post('/admin/website/blog', $this->data(['summary' => ' ']))->assertSessionHasErrors('summary');
        $this->post('/admin/website/blog', $this->data(['title' => "Invalid \xFF"]))->assertSessionHasErrors('title');
        $this->post('/admin/website/blog', $this->data())->assertRedirect('/admin/website/blog/example-article/edit');
        $this->post('/admin/website/blog', $this->data())->assertSessionHasErrors('slug');
        $this->assertDatabaseCount('storefront_blog_posts', 1);
        $this->assertDatabaseCount('storefront_blog_post_versions', 1);
    }

    public function test_drafts_publish_stale_conflict_and_restore_visibility(): void
    {
        config(['furniture.blog_posts_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->withoutVite()->actingAs($owner);
        $this->post('/admin/website/blog', $this->data())->assertRedirect('/admin/website/blog/example-article/edit');
        $this->getJson('/storefront-api/blog')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/storefront-api/blog/example-article')->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
        $url = '/admin/website/blog/example-article';
        $this->post($url, $this->data(['revision' => 1, 'published' => '1', 'title' => 'Published title']))->assertRedirect($url.'/edit');
        $public = $this->getJson('/storefront-api/blog/example-article')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('title', 'Published title');
        $this->assertCount(0, $public->headers->getCookies());
        $this->assertSame(['slug', 'title', 'summary', 'body'], array_keys($public->json()));
        $this->getJson('/storefront-api/blog')->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'example-article')->assertJsonMissing(['body' => 'Sample body']);
        $this->from($url.'/edit')->post($url, $this->data(['revision' => 1, 'title' => 'Stale']))->assertSessionHasErrors('revision');
        $this->get($url.'/edit')->assertSee('name="revision" value="1"', false)->assertSee('value="Stale"', false);
        $this->post($url, $this->data(['revision' => 1, 'title' => 'Stale']))->assertSessionHasErrors('revision');
        $this->assertDatabaseHas('storefront_blog_posts', ['slug' => 'example-article', 'title' => 'Published title', 'revision' => 2]);
        $this->post($url.'/restore', ['revision' => 2, 'source_revision' => 1])->assertRedirect($url.'/edit');
        $this->assertDatabaseHas('storefront_blog_posts', ['slug' => 'example-article', 'revision' => 3, 'published' => false, 'title' => 'Example article']);
        $this->getJson('/storefront-api/blog/example-article')->assertNotFound();
        $this->post($url.'/restore', ['revision' => 3, 'source_revision' => 2])->assertRedirect($url.'/edit');
        $this->getJson('/storefront-api/blog/example-article')->assertOk()->assertJsonPath('title', 'Published title');
        $this->assertDatabaseHas('storefront_blog_post_versions', ['slug' => 'example-article', 'revision' => 4, 'actor_id' => $owner->id, 'change_type' => 'restore', 'restored_from_revision' => 2]);
    }

    public function test_history_is_isolated_and_plain_text_is_escaped(): void
    {
        config(['furniture.blog_posts_enabled' => true]);
        $this->withoutVite()->actingAs(User::factory()->create(['is_owner' => true]));
        $this->post('/admin/website/blog', $this->data(['title' => '<script>alert(1)</script>', 'published' => '1']));
        $this->post('/admin/website/blog', $this->data(['slug' => 'other-article']));
        $this->post('/admin/website/blog/other-article', $this->data(['revision' => 1]));
        $this->post('/admin/website/blog/example-article/restore', ['revision' => 1, 'source_revision' => 2])->assertSessionHasErrors('source_revision');
        $this->get('/admin/website/blog/example-article/edit')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->getJson('/storefront-api/blog/example-article')->assertJsonPath('title', '<script>alert(1)</script>');
        $this->assertDatabaseHas('storefront_blog_posts', ['slug' => 'example-article', 'revision' => 1]);
    }

    public function test_public_list_is_paginated_and_legacy_slug_requires_an_explicit_furniture_post(): void
    {
        config(['furniture.blog_posts_enabled' => true]);
        $legacy = 'ergonomic-office-guide-2025';
        $this->getJson('/storefront-api/blog/'.$legacy)->assertNotFound();
        $this->actingAs(User::factory()->create(['is_owner' => true]));
        $this->post('/admin/website/blog', $this->data(['slug' => $legacy, 'published' => '1']))->assertRedirect('/admin/website/blog/'.$legacy.'/edit');
        $this->getJson('/storefront-api/blog/'.$legacy)->assertOk()->assertJsonPath('title', 'Example article');
        for ($i = 0; $i < 21; $i++) {
            DB::table('storefront_blog_posts')->insert(['slug' => 'post-'.$i, 'title' => 'Title', 'summary' => 'Summary', 'body' => 'Private body', 'published' => $i < 20, 'revision' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->getJson('/storefront-api/blog?page=1')->assertOk()->assertJsonCount(12, 'data')->assertJsonPath('last_page', 2)->assertJsonMissing(['body' => 'Private body']);
        $this->getJson('/storefront-api/blog?page=2')->assertOk()->assertJsonCount(9, 'data');
        $this->getJson('/storefront-api/blog?page=0')->assertUnprocessable();
        $this->getJson('/storefront-api/blog/post-20')->assertNotFound();
        $this->withoutVite()->get('/admin/website/blog')->assertOk()->assertSee('/admin/website/blog?page=2', false)->assertDontSee('http://localhost/admin/website/blog', false);
    }

    public function test_revoked_owner_and_failed_snapshot_do_not_leave_partial_data(): void
    {
        config(['furniture.blog_posts_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->actingAs($owner);
        User::query()->whereKey($owner->id)->update(['is_owner' => false]);
        $this->post('/admin/website/blog', $this->data())->assertForbidden();
        User::query()->whereKey($owner->id)->update(['is_owner' => true]);
        $this->actingAs($owner->fresh())->withoutExceptionHandling();
        DB::listen(function (QueryExecuted $event): void {
            if (str_contains(strtolower($event->sql), 'insert into "storefront_blog_post_versions"')) {
                throw new RuntimeException('Synthetic snapshot failure');
            }
        });
        try {
            $this->post('/admin/website/blog', $this->data());
            $this->fail('Expected snapshot failure');
        } catch (RuntimeException $e) {
            $this->assertSame('Synthetic snapshot failure', $e->getMessage());
            $this->assertDatabaseCount('storefront_blog_posts', 0);
            $this->assertDatabaseCount('storefront_blog_post_versions', 0);
        }
    }
}
