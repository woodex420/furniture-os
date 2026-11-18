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
        $this->post(self::EDIT_URL.'/sections', ['revision' => 1, 'action' => 'add'])->assertNotFound();
        config(['furniture.builder_html_proof_enabled' => true]);
        app()['env'] = 'production';
        $this->get(self::PUBLIC_URL)->assertNotFound();
        $this->get(self::EDIT_URL)->assertNotFound();
        // Exercise the controller guard independently of production CSRF enforcement.
        $this->withoutMiddleware(PreventRequestForgery::class)
            ->post(self::EDIT_URL, ['revision' => 1, 'section_id' => Proof::SECTION_ID, 'summary' => 'Changed', 'body' => 'Changed'])->assertNotFound();
        $this->post(self::EDIT_URL.'/sections', ['revision' => 1, 'action' => 'add'])->assertNotFound();
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
        $data = ['revision' => 1, 'section_id' => Proof::SECTION_ID, 'summary' => 'New synthetic summary', 'body' => "New line\n<script>alert(1)</script> & Urdu فرنیچر"];
        $this->post(self::EDIT_URL, $data)->assertRedirect(self::EDIT_URL);
        auth()->logout();
        $this->get(self::PUBLIC_URL)->assertOk()->assertSee('data-builder-revision="2"', false)->assertSee('New synthetic summary')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt; &amp; Urdu فرنیچر', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->actingAs($owner)->post(self::EDIT_URL, ['revision' => 1, 'section_id' => Proof::SECTION_ID, 'summary' => 'Stale', 'body' => 'Lost'])->assertSessionHasErrors('revision');
        $this->get(self::PUBLIC_URL)->assertSee('New synthetic summary')->assertDontSee('Stale');
        app(BuilderDocumentRevisions::class)->restore((int) $owner->id, Proof::DOCUMENT_ID, 2, 1);
        $this->get(self::PUBLIC_URL)->assertSee('data-builder-revision="3"', false)->assertSee('A synthetic article for testing the shared editorial layout.')->assertDontSee('New synthetic summary');
        $this->assertDatabaseCount('furniture_builder_document_versions', 3);
    }

    public function test_section_operations_save_ordered_html_and_protect_required_section_and_revisions(): void
    {
        config(['furniture.builder_html_proof_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->seedFixture($owner);
        $url = self::EDIT_URL.'/sections';
        $this->post($url, ['revision' => 1, 'action' => 'add'])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['is_owner' => false]))->post($url, ['revision' => 1, 'action' => 'add'])->assertForbidden();
        $this->actingAs($owner)->call('POST', $url, [], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'], str_repeat('x', 4097))->assertStatus(413);
        $this->post($url, ['revision' => 1, 'action' => 'add'])->assertRedirect(self::EDIT_URL);
        $contract = app(BuilderDocumentV1::class);
        $current = fn () => $contract->decode(DB::table('furniture_builder_documents')->where('id', Proof::DOCUMENT_ID)->value('document'));
        $newId = $current()['sections'][1]['id'];
        $this->assertTrue($contract->validId($newId));
        $this->get(self::PUBLIC_URL)->assertSeeInOrder(['data-section-id="'.Proof::SECTION_ID.'"', 'data-section-id="'.$newId.'"'], false);
        $this->post(self::EDIT_URL, ['revision' => 2, 'section_id' => $newId, 'summary' => 'Second unique summary', 'body' => 'Second synthetic body'])->assertRedirect(self::EDIT_URL);
        $this->post($url, ['revision' => 3, 'action' => 'up', 'section_id' => $newId])->assertRedirect(self::EDIT_URL);
        $this->get(self::PUBLIC_URL)->assertSeeInOrder(['data-section-id="'.$newId.'"', 'Second unique summary', 'data-section-id="'.Proof::SECTION_ID.'"'], false);
        $this->post($url, ['revision' => 4, 'action' => 'duplicate', 'section_id' => $newId])->assertRedirect(self::EDIT_URL);
        $copyId = $current()['sections'][1]['id'];
        $this->assertNotSame($newId, $copyId);
        $this->assertSame('Second unique summary', $current()['sections'][1]['props']['summary']);
        $this->post($url, ['revision' => 5, 'action' => 'remove', 'section_id' => Proof::SECTION_ID])->assertSessionHasErrors('section');
        $this->post($url, ['revision' => 4, 'action' => 'remove', 'section_id' => $copyId])->assertSessionHasErrors('revision');
        $this->post($url, ['revision' => 5, 'action' => 'invalid', 'section_id' => $copyId])->assertSessionHasErrors('action');
        $this->post($url, ['revision' => 5, 'action' => 'remove', 'section_id' => $copyId])->assertRedirect(self::EDIT_URL);
        $this->get(self::PUBLIC_URL)->assertDontSee('data-section-id="'.$copyId.'"', false);
        $this->assertDatabaseCount('furniture_builder_document_versions', 6);
        app(BuilderDocumentRevisions::class)->restore((int) $owner->id, Proof::DOCUMENT_ID, 6, 5);
        $this->get(self::PUBLIC_URL)->assertSee('data-section-id="'.$copyId.'"', false);
        $this->assertDatabaseCount('furniture_builder_document_versions', 7);
    }

    public function test_auth_validation_and_unknown_document_fail_closed(): void
    {
        config(['furniture.builder_html_proof_enabled' => true]);
        $owner = User::factory()->create(['is_owner' => true]);
        $this->seedFixture($owner);
        $data = ['revision' => 1, 'section_id' => Proof::SECTION_ID, 'summary' => 'new', 'body' => 'new'];
        $this->post(self::EDIT_URL, $data)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['is_owner' => false]))->post(self::EDIT_URL, $data)->assertForbidden();
        $this->actingAs($owner)->post(self::EDIT_URL, ['revision' => 1, 'section_id' => Proof::SECTION_ID, 'summary' => str_repeat('x', 301), 'body' => 'ok'])->assertSessionHasErrors('summary');
        $this->post(self::EDIT_URL, ['revision' => 1, 'section_id' => Proof::SECTION_ID, 'summary' => 'ok', 'body' => str_repeat('x', 12001)])->assertSessionHasErrors('body');
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
