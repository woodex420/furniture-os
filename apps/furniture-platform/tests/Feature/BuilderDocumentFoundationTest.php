<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\Website\BuilderDocumentRevisions;
use App\Support\Website\BuilderDocumentV1;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class BuilderDocumentFoundationTest extends TestCase
{
    use RefreshDatabase;

    private const PAGE = 'c6ae6052-11ad-4d16-8a18-56541c735d8c';

    private const OTHER = 'c6ae6052-11ad-4d16-8a18-56541c735d8d';

    private const SECTION = 'e38c052c-f2e0-43ca-85d0-a5760caa7d03';

    private function document(string $body = 'Literal <strong>text</strong>'): array
    {
        return ['schema_version' => 1, 'sections' => [['id' => self::SECTION, 'type' => 'article_text', 'props' => ['summary' => 'خلاصہ', 'body' => $body]]]];
    }

    private function invalid(callable $callback, string $field): void
    {
        try {
            $callback();
            $this->fail('Expected validation failure.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey($field, $error->errors());
        }
    }

    public function test_empty_install_has_no_documents_and_no_public_or_admin_route(): void
    {
        $this->assertDatabaseCount('furniture_builder_documents', 0);
        $this->assertDatabaseCount('furniture_builder_document_versions', 0);
        $this->get('/storefront-api/builder/documents')->assertNotFound();
        $this->get('/admin/website/builder/documents')->assertNotFound();
    }

    public function test_strict_versioned_document_contract_preserves_text_and_rejects_unknown_or_unsafe_shapes(): void
    {
        $contract = app(BuilderDocumentV1::class);
        $doc = $this->document("Line one\nLine two <script>x</script> & furniture");
        $this->assertSame($doc, $contract->decode($contract->encode($doc)));
        $this->assertSame(['schema_version' => 1, 'sections' => []], $contract->decode($contract->encode(['schema_version' => 1, 'sections' => []])));
        $bad = [];
        $bad[] = array_merge($doc, ['future' => true]);
        $bad[] = array_merge($doc, ['schema_version' => 2]);
        $bad[] = array_merge($doc, ['schema_version' => '1']);
        $bad[] = ['schema_version' => 1, 'sections' => ['unexpected' => $doc['sections'][0]]];
        $bad[] = ['schema_version' => 1, 'sections' => [$doc['sections'][0], $doc['sections'][0]]];
        $bad[] = ['schema_version' => 1, 'sections' => array_fill(0, 11, $doc['sections'][0])];
        $badSection = $doc['sections'][0];
        $badSection['type'] = 'unknown';
        $bad[] = ['schema_version' => 1, 'sections' => [$badSection]];
        $badSection = $doc['sections'][0];
        $badSection['id'] = strtoupper(self::SECTION);
        $bad[] = ['schema_version' => 1, 'sections' => [$badSection]];
        $badSection = $doc['sections'][0];
        $badSection['props']['future'] = 'do not drop';
        $bad[] = ['schema_version' => 1, 'sections' => [$badSection]];
        $badSection = $doc['sections'][0];
        $badSection['props']['body'] = "\xFF";
        $bad[] = ['schema_version' => 1, 'sections' => [$badSection]];
        $badSection = $doc['sections'][0];
        $badSection['props']['body'] = str_repeat('x', 12001);
        $bad[] = ['schema_version' => 1, 'sections' => [$badSection]];
        $badSection = $doc['sections'][0];
        $badSection['props']['summary'] = '  ';
        $bad[] = ['schema_version' => 1, 'sections' => [$badSection]];
        $large = ['schema_version' => 1, 'sections' => []];
        for ($i = 0; $i < 6; $i++) {
            $section = $doc['sections'][0];
            $section['id'] = sprintf('e38c052c-f2e0-43ca-85d0-%012d', $i);
            $section['props']['body'] = str_repeat('x', 12000);
            $large['sections'][] = $section;
        }
        $bad[] = $large; // Each section is valid, but the encoded document exceeds the byte cap.
        foreach ($bad as $document) {
            $this->invalid(fn () => $contract->encode($document), 'document');
        }
        foreach (['{', 'null', '{"schema_version":1,"sections":{}}', '{"schema_version":1,"sections":[{"id":"e38c052c-f2e0-43ca-85d0-a5760caa7d03","type":"article_text","props":[]}]}', str_repeat('x', BuilderDocumentV1::MAX_BYTES + 1)] as $json) {
            $this->invalid(fn () => $contract->decode($json), 'document');
        }
    }

    public function test_owner_can_create_edit_restore_and_stale_or_cross_document_versions_are_rejected(): void
    {
        $service = app(BuilderDocumentRevisions::class);
        $contract = app(BuilderDocumentV1::class);
        $owner = User::factory()->create(['is_owner' => true]);
        $id = (int) $owner->id;
        $first = $this->document('First');
        $second = $this->document('Second');
        $service->create($id, self::PAGE, $first);
        $service->create($id, self::OTHER, $this->document('Other page'));
        $this->invalid(fn () => $service->create($id, self::PAGE, $first), 'id');
        $service->edit($id, self::PAGE, 1, $second);
        $this->invalid(fn () => $service->edit($id, self::PAGE, 1, $first), 'revision');
        $this->invalid(fn () => $service->restore($id, self::OTHER, 1, 2), 'source_revision');
        $this->invalid(fn () => $service->restore($id, self::PAGE, 2, 2), 'source_revision');
        $service->restore($id, self::PAGE, 2, 1);
        $row = DB::table('furniture_builder_documents')->where('id', self::PAGE)->first();
        $this->assertSame(3, (int) $row->revision);
        $this->assertSame($first, $contract->decode($row->document));
        $this->assertDatabaseHas('furniture_builder_document_versions', ['document_id' => self::PAGE, 'revision' => 3, 'actor_id' => $id, 'change_type' => 'restore', 'restored_from_revision' => 1]);
        $this->assertDatabaseCount('furniture_builder_document_versions', 4);
        $this->assertSame('Other page', $contract->decode(DB::table('furniture_builder_documents')->where('id', self::OTHER)->value('document'))['sections'][0]['props']['body']);
    }

    public function test_revoked_and_non_owner_actors_cannot_write(): void
    {
        $service = app(BuilderDocumentRevisions::class);
        $owner = User::factory()->create(['is_owner' => true]);
        $nonOwner = User::factory()->create(['is_owner' => false]);
        foreach ([(int) $nonOwner->id, 999999] as $actorId) {
            try {
                $service->create($actorId, self::PAGE, $this->document());
                $this->fail('Expected authorization failure.');
            } catch (AuthorizationException) {
                $this->assertDatabaseCount('furniture_builder_documents', 0);
            }
        }
        $service->create((int) $owner->id, self::PAGE, $this->document());
        User::query()->whereKey($owner->id)->update(['is_owner' => false]);
        try {
            $service->edit((int) $owner->id, self::PAGE, 1, $this->document('Changed'));
            $this->fail('Revoked owner must be denied.');
        } catch (AuthorizationException) {
            $this->assertDatabaseHas('furniture_builder_documents', ['id' => self::PAGE, 'revision' => 1]);
            $this->assertDatabaseCount('furniture_builder_document_versions', 1);
        }
    }

    public function test_invalid_future_or_corrupt_documents_are_never_overwritten_or_restored(): void
    {
        $service = app(BuilderDocumentRevisions::class);
        $owner = User::factory()->create(['is_owner' => true]);
        $id = (int) $owner->id;
        $service->create($id, self::PAGE, $this->document());
        $future = ['schema_version' => 2, 'sections' => []];
        $this->invalid(fn () => $service->edit($id, self::PAGE, 1, $future), 'document');
        DB::table('furniture_builder_document_versions')->where('document_id', self::PAGE)->where('revision', 1)->update(['document' => '{"schema_version":2,"sections":[]}']);
        $service->edit($id, self::PAGE, 1, $this->document('Second'));
        $this->invalid(fn () => $service->restore($id, self::PAGE, 2, 1), 'document');
        DB::table('furniture_builder_documents')->where('id', self::PAGE)->update(['document' => '{"schema_version":2,"sections":[]}']);
        $this->invalid(fn () => $service->edit($id, self::PAGE, 2, $this->document('Would overwrite')), 'document');
        $this->assertDatabaseHas('furniture_builder_documents', ['id' => self::PAGE, 'revision' => 2, 'document' => '{"schema_version":2,"sections":[]}']);
        $this->assertDatabaseCount('furniture_builder_document_versions', 2);
    }

    public function test_snapshot_failure_rolls_back_create_and_edit(): void
    {
        $service = app(BuilderDocumentRevisions::class);
        $owner = User::factory()->create(['is_owner' => true]);
        $service->create((int) $owner->id, self::PAGE, $this->document());
        DB::listen(function (QueryExecuted $event): void {
            if (str_contains(strtolower($event->sql), 'insert into "furniture_builder_document_versions"')) {
                throw new RuntimeException('Synthetic history failure');
            }
        });
        foreach ([fn () => $service->create((int) $owner->id, self::OTHER, $this->document()), fn () => $service->edit((int) $owner->id, self::PAGE, 1, $this->document('Changed'))] as $operation) {
            try {
                $operation();
                $this->fail('Expected synthetic failure.');
            } catch (RuntimeException $error) {
                $this->assertSame('Synthetic history failure', $error->getMessage());
            }
        }
        $this->assertDatabaseCount('furniture_builder_documents', 1);
        $this->assertDatabaseCount('furniture_builder_document_versions', 1);
        $this->assertDatabaseHas('furniture_builder_documents', ['id' => self::PAGE, 'revision' => 1]);
    }
}
