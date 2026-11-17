<?php

declare(strict_types=1);

namespace App\Support\Website;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** A temporary, revisioned bridge for existing Reimagin service copy, not a CMS. */
final class ServiceCopyRevisions
{
    public function change(int $actorId, string $slug, int $revision, ?array $data, ?int $sourceRevision = null): void
    {
        DB::transaction(function () use ($actorId, $slug, $revision, $data, $sourceRevision): void {
            $actor = User::query()->lockForUpdate()->find($actorId);
            if ($actor === null) {
                throw new AuthorizationException;
            }
            Gate::forUser($actor)->authorize('workspace.view');
            Gate::forUser($actor)->authorize('storefront.edit');
            $current = DB::table('storefront_service_copy')->where('slug', $slug)->lockForUpdate()->first();
            abort_if($current === null, 404);
            if ((int) $current->revision !== $revision || $revision >= 4294967295) {
                throw ValidationException::withMessages(['revision' => 'This service changed or reached the revision limit. Reload before saving.']);
            }
            if ($data === null) {
                $source = DB::table('storefront_service_copy_versions')->where('slug', $slug)->where('revision', $sourceRevision)->first();
                if ($source === null || (int) $source->revision === $revision) {
                    throw ValidationException::withMessages(['source_revision' => 'That version is unavailable or already current.']);
                }
                $title = $source->title;
                $tagline = $source->tagline;
                $type = 'restore';
            } else {
                $title = $data['title'];
                $tagline = $data['tagline'];
                $type = 'edit';
            }
            $next = $revision + 1;
            DB::table('storefront_service_copy')->where('slug', $slug)->update([
                'title' => $title, 'tagline' => $tagline, 'revision' => $next, 'updated_at' => now(),
            ]);
            DB::table('storefront_service_copy_versions')->insert([
                'slug' => $slug, 'revision' => $next, 'title' => $title, 'tagline' => $tagline,
                'actor_id' => $actorId, 'change_type' => $type, 'restored_from_revision' => $sourceRevision, 'created_at' => now(),
            ]);
        }, 3);
    }
}
