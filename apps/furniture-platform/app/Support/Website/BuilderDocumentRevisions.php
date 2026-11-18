<?php

declare(strict_types=1);

namespace App\Support\Website;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Internal owner-only foundation; no routes, rendering, or publication yet. */
final class BuilderDocumentRevisions
{
    public function __construct(private readonly BuilderDocumentV1 $contract) {}

    public function create(int $actorId, string $id, array $document): void
    {
        try {
            DB::transaction(function () use ($actorId, $id, $document): void {
                $this->authorizeActor($actorId);
                if (! $this->contract->validId($id)) {
                    throw ValidationException::withMessages(['id' => 'Invalid document identifier.']);
                }
                $json = $this->contract->encode($document);
                if (DB::table('furniture_builder_documents')->where('id', $id)->exists()) {
                    throw ValidationException::withMessages(['id' => 'Document already exists.']);
                }
                DB::table('furniture_builder_documents')->insert(['id' => $id, 'document' => $json, 'revision' => 1, 'created_at' => now(), 'updated_at' => now()]);
                $this->snapshot($actorId, $id, 1, $json, 'create', null);
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505', '19'], true) && DB::table('furniture_builder_documents')->where('id', $id)->exists()) {
                throw ValidationException::withMessages(['id' => 'Document already exists.']);
            }
            throw $exception;
        }
    }

    public function edit(int $actorId, string $id, int $expectedRevision, array $document): void
    {
        $this->change($actorId, $id, $expectedRevision, $document, null);
    }

    public function restore(int $actorId, string $id, int $expectedRevision, int $sourceRevision): void
    {
        $this->change($actorId, $id, $expectedRevision, null, $sourceRevision);
    }

    private function change(int $actorId, string $id, int $expectedRevision, ?array $document, ?int $sourceRevision): void
    {
        DB::transaction(function () use ($actorId, $id, $expectedRevision, $document, $sourceRevision): void {
            $this->authorizeActor($actorId);
            if (! $this->contract->validId($id)) {
                throw ValidationException::withMessages(['id' => 'Invalid document identifier.']);
            }
            $current = DB::table('furniture_builder_documents')->where('id', $id)->lockForUpdate()->first();
            abort_if($current === null, 404);
            if ($expectedRevision < 1 || $expectedRevision >= 4294967295 || (int) $current->revision !== $expectedRevision) {
                throw ValidationException::withMessages(['revision' => 'Document changed or reached the revision limit. Reload before saving.']);
            }
            // Never overwrite unknown/corrupt current data, even with a valid new document.
            $this->contract->decode($current->document);
            if ($document === null) {
                $source = DB::table('furniture_builder_document_versions')->where('document_id', $id)->where('revision', $sourceRevision)->first();
                if ($source === null || (int) $source->revision === $expectedRevision) {
                    throw ValidationException::withMessages(['source_revision' => 'That version is unavailable or already current.']);
                }
                $json = $this->contract->encode($this->contract->decode($source->document));
                $type = 'restore';
            } else {
                $json = $this->contract->encode($document);
                $type = 'edit';
            }
            $next = $expectedRevision + 1;
            $updated = DB::table('furniture_builder_documents')->where('id', $id)->where('revision', $expectedRevision)->update(['document' => $json, 'revision' => $next, 'updated_at' => now()]);
            if ($updated !== 1) {
                throw ValidationException::withMessages(['revision' => 'Document changed. Reload before saving.']);
            }
            $this->snapshot($actorId, $id, $next, $json, $type, $sourceRevision);
        }, 3);
    }

    private function authorizeActor(int $id): void
    {
        $actor = User::query()->lockForUpdate()->find($id);
        if ($actor === null) {
            throw new AuthorizationException;
        }
        Gate::forUser($actor)->authorize('workspace.view');
        Gate::forUser($actor)->authorize('storefront.edit');
    }

    private function snapshot(int $actorId, string $id, int $revision, string $json, string $type, ?int $source): void
    {
        DB::table('furniture_builder_document_versions')->insert([
            'document_id' => $id, 'revision' => $revision, 'document' => $json, 'actor_id' => $actorId,
            'change_type' => $type, 'restored_from_revision' => $source, 'created_at' => now(),
        ]);
    }
}
