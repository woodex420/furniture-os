<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\Website\BuilderDocumentRevisions;
use App\Support\Website\BuilderDocumentV1;
use App\Support\Website\BuilderSectionOperations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BuilderSectionOperationsTest extends TestCase
{
    use RefreshDatabase;

    private function id(int $n): string
    {
        return sprintf('06516020-34a2-4615-9b8f-%012d', $n);
    }

    private function expectInvalid(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Invalid operation must fail.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }

    public function test_add_duplicate_move_remove_are_ordered_and_do_not_mutate_input(): void
    {
        $ops = app(BuilderSectionOperations::class);
        $empty = ['schema_version' => 1, 'sections' => []];
        $one = $ops->addArticle($empty, $this->id(1), 'Summary', "Line one\nLine two <script> & فرنیچر");
        $two = $ops->duplicate($one, $this->id(1), $this->id(2));
        $three = $ops->addArticle($two, $this->id(3), 'Another', 'Another body');
        $moved = $ops->move($three, $this->id(3), -1);
        $this->assertSame([$this->id(1), $this->id(3), $this->id(2)], array_column($moved['sections'], 'id'));
        $this->assertSame($three, $ops->move($moved, $this->id(3), 1));
        $this->assertSame($one['sections'][0]['props'], $two['sections'][1]['props']);
        $this->assertSame([], $empty['sections']);
        $this->assertCount(1, $one['sections']);
        $this->assertSame([$this->id(3), $this->id(2)], array_column($ops->remove($moved, $this->id(1))['sections'], 'id'));
        $this->assertSame($empty, $ops->remove($one, $this->id(1)));
    }

    public function test_operations_reject_unknown_invalid_duplicate_and_out_of_bounds_without_mutating_input(): void
    {
        $ops = app(BuilderSectionOperations::class);
        $one = $ops->addArticle(['schema_version' => 1, 'sections' => []], $this->id(1), 'Summary', 'Body');
        $original = $one;
        foreach ([
            fn () => $ops->duplicate($one, $this->id(1), $this->id(1)),
            fn () => $ops->duplicate($one, $this->id(2), $this->id(3)),
            fn () => $ops->duplicate($one, $this->id(1), 'bad-id'),
            fn () => $ops->addArticle($one, $this->id(1), 'Summary', 'Body'),
            fn () => $ops->addArticle($one, $this->id(2), '  ', 'Body'),
            fn () => $ops->remove($one, $this->id(2)),
            fn () => $ops->move($one, $this->id(1), 0),
            fn () => $ops->move($one, $this->id(1), -1),
            fn () => $ops->move($one, $this->id(1), 1),
            fn () => $ops->remove(['schema_version' => 2, 'sections' => []], $this->id(1)),
        ] as $operation) {
            $this->expectInvalid($operation);
            $this->assertSame($original, $one);
        }
        $full = $one;
        for ($i = 2; $i <= 10; $i++) {
            $full = $ops->addArticle($full, $this->id($i), 'Summary', 'Body');
        }
        $this->expectInvalid(fn () => $ops->addArticle($full, $this->id(11), 'Summary', 'Body'));
        $this->expectInvalid(fn () => $ops->duplicate($full, $this->id(1), $this->id(11)));
        $this->assertCount(10, $full['sections']);
        $large = ['schema_version' => 1, 'sections' => []];
        for ($i = 1; $i <= 5; $i++) {
            $large = $ops->addArticle($large, $this->id($i), 'Summary', str_repeat('x', 12000));
        }
        $this->expectInvalid(fn () => $ops->duplicate($large, $this->id(1), $this->id(6)));
        $this->assertCount(5, $large['sections']);
    }

    public function test_operation_results_can_be_revisioned_and_restored_but_do_not_save_themselves(): void
    {
        $ops = app(BuilderSectionOperations::class);
        $service = app(BuilderDocumentRevisions::class);
        $owner = User::factory()->create(['is_owner' => true]);
        $initial = $ops->addArticle(['schema_version' => 1, 'sections' => []], $this->id(1), 'Summary', 'Body');
        $service->create((int) $owner->id, $this->id(99), $initial);
        $changed = $ops->duplicate($initial, $this->id(1), $this->id(2));
        $this->assertDatabaseHas('furniture_builder_documents', ['id' => $this->id(99), 'revision' => 1]);
        $service->edit((int) $owner->id, $this->id(99), 1, $changed);
        $this->expectInvalid(fn () => $service->edit((int) $owner->id, $this->id(99), 1, $initial));
        $service->restore((int) $owner->id, $this->id(99), 2, 1);
        $row = DB::table('furniture_builder_documents')->where('id', $this->id(99))->first();
        $this->assertSame(3, (int) $row->revision);
        $this->assertSame($initial, app(BuilderDocumentV1::class)->decode($row->document));
        $this->assertDatabaseCount('furniture_builder_document_versions', 3);
    }
}
