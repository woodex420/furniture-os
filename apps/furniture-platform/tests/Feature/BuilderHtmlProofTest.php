<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Workspace\BuilderHtmlProofController as Proof;
use App\Models\User;
use App\Support\Website\BuilderDocumentRevisions;
use App\Support\Website\BuilderDocumentV1;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BuilderHtmlProofTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_URL = '/experiments/builder-html-proof';

    private const EDIT_URL = '/admin/website/builder-html-proof';

    private function seedFixture(User $owner): void
    {
        app(BuilderDocumentRevisions::class)->create((int) $owner->id, Proof::DOCUMENT_ID, [
            'schema_version' => 1,
            'sections' => [[
                'id' => Proof::SECTION_ID, 'type' => 'article_text', 'props' => [
                    'summary' => 'A synthetic article for testing the shared editorial layout.',
                    'body' => "First example paragraph.\nSecond example line with <strong>literal text</strong> & symbols.",
                ],
            ]],
        ]);
    }

    public function test_proof_is_off_by_default_restricted_by_environment_and_needs_a_fixture(): void
    {
        $owner = User::factory()->create(['is_owner' => true]);
        $this->seedFixture($owner);
        $this->get(self::PUBLIC_URL)->assertNotFound();
        $this->actingAs($owner)->get(self::EDIT_URL)->assertNotFound();
        config(['furniture.builder_html_proof_enabled' => true]);
        app()['env'] = 'production';
        $this->get(self::PUBLIC_URL)->assertNotFound();
        $this->get(self::EDIT_URL)->assertNotFound();
        // Exercise the controller guard independently of production CSRF enforcement.
        $this->withoutMiddleware(PreventRequestForgery::class)
            ->post(self::EDIT_URL, ['revision' => 1, 'summary' => 'Changed', 'body' => 'Changed'])->assertNotFound();
        $this->assertDatabaseCount('furniture_builder_document_versions', 1);
        app()['env'] = 'testing';
        DB::table('furniture_builder_document_versions')->delete();
        DB::table('furniture_builder_documents')->delete();
        $this->get(self::PUBLIC_URL)->assertNotFound();
        $this->get(self::EDIT_URL)->assertNotFound();
    }

    public function test_synthetic_server_html_matches_shared_react_fixture_without_javascript(): void
    {
        config(['furniture.builder_html_proof_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->seedFixture($owner);
        $response = $this->get(self::PUBLIC_URL)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertEmpty($response->headers->getCookies());
        $html = preg_replace('/>\s+</', '><', $response->getContent());
        $expected = preg_replace('/>\s+</', '><', trim(file_get_contents(dirname(base_path(), 2).'/shared/builder-article-render-fixture.html')));
        $this->assertStringContainsString($expected, $html);
        $response->assertSee('data-builder-revision="1"', false)->assertDontSee('<strong>literal text</strong>', false);
    }

    public function test_owner_save_and_restore_are_visible_in_next_anonymous_html_response_and_stale_writes_do_not_overwrite(): void
    {
        config(['furniture.builder_html_proof_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->seedFixture($owner);
        $this->withoutVite()->actingAs($owner)->get(self::EDIT_URL)->assertOk()->assertSee('Article text proof');
        $data = ['revision' => 1, 'summary' => 'New synthetic summary', 'body' => "New line\n<script>alert(1)</script> & Urdu فرنیچر"];
        $this->post(self::EDIT_URL, $data)->assertRedirect(self::EDIT_URL);
        auth()->logout();
        $this->get(self::PUBLIC_URL)->assertOk()->assertSee('data-builder-revision="2"', false)->assertSee('New synthetic summary')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt; &amp; Urdu فرنیچر', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->actingAs($owner)->post(self::EDIT_URL, ['revision' => 1, 'summary' => 'Stale', 'body' => 'Lost'])->assertSessionHasErrors('revision');
        $this->get(self::PUBLIC_URL)->assertSee('New synthetic summary')->assertDontSee('Stale');
        app(BuilderDocumentRevisions::class)->restore((int) $owner->id, Proof::DOCUMENT_ID, 2, 1);
        $this->get(self::PUBLIC_URL)->assertSee('data-builder-revision="3"', false)->assertSee('A synthetic article for testing the shared editorial layout.')->assertDontSee('New synthetic summary');
        $this->assertDatabaseCount('furniture_builder_document_versions', 3);
    }

    public function test_auth_validation_and_unknown_document_fail_closed(): void
    {
        config(['furniture.builder_html_proof_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->seedFixture($owner);
        $data = ['revision' => 1, 'summary' => 'new', 'body' => 'new'];
        $this->post(self::EDIT_URL, $data)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['is_owner' => false]))->post(self::EDIT_URL, $data)->assertForbidden();
        $this->actingAs($owner)->post(self::EDIT_URL, ['revision' => 1, 'summary' => str_repeat('x', 301), 'body' => 'ok'])->assertSessionHasErrors('summary');
        $this->post(self::EDIT_URL, ['revision' => 1, 'summary' => 'ok', 'body' => str_repeat('x', 12001)])->assertSessionHasErrors('body');
        $this->assertDatabaseCount('furniture_builder_document_versions', 1);
        // A valid but non-fixture document is not exposed, either.
        $otherShape = app(BuilderDocumentV1::class)->encode(['schema_version' => 1, 'sections' => []]);
        DB::table('furniture_builder_documents')->where('id', Proof::DOCUMENT_ID)->update(['document' => $otherShape]);
        $this->get(self::PUBLIC_URL)->assertStatus(503);
        $this->post(self::EDIT_URL, $data)->assertStatus(503);
        DB::table('furniture_builder_documents')->where('id', Proof::DOCUMENT_ID)->update(['document' => '{"schema_version":2,"sections":[]}']);
        $this->get(self::PUBLIC_URL)->assertStatus(503)->assertDontSee('synthetic article for testing');
        $this->get(self::EDIT_URL)->assertStatus(503);
        $this->post(self::EDIT_URL, $data)->assertStatus(503);
        $this->assertDatabaseCount('furniture_builder_document_versions', 1);
    }
}
