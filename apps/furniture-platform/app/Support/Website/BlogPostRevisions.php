<?php

declare(strict_types=1);

namespace App\Support\Website;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Narrow experimental Furniture blog bridge, not the final content model. */
final class BlogPostRevisions
{
    public function create(int $actorId, array $data): void
    {
        try {
            DB::transaction(function () use ($actorId, $data): void {
                $this->authorizeActor($actorId);
                if (DB::table('storefront_blog_posts')->where('slug', $data['slug'])->exists()) {
                    throw ValidationException::withMessages(['slug' => 'That article URL is already in use.']);
                }
                $fields = $this->fields($data);
                DB::table('storefront_blog_posts')->insert(array_merge(['slug' => $data['slug'], 'revision' => 1, 'created_at' => now(), 'updated_at' => now()], $fields));
                $this->snapshot($actorId, $data['slug'], 1, $fields, 'create', null);
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505', '19'], true) && DB::table('storefront_blog_posts')->where('slug', $data['slug'])->exists()) {
                throw ValidationException::withMessages(['slug' => 'That article URL is already in use.']);
            }
            throw $exception;
        }
    }

    public function change(int $actorId, string $slug, int $revision, ?array $data, ?int $sourceRevision = null): void
    {
        DB::transaction(function () use ($actorId, $slug, $revision, $data, $sourceRevision): void {
            $this->authorizeActor($actorId);
            $current = DB::table('storefront_blog_posts')->where('slug', $slug)->lockForUpdate()->first();
            abort_if($current === null, 404);
            if ((int) $current->revision !== $revision || $revision >= 4294967295) {
                throw ValidationException::withMessages(['revision' => 'This article changed or reached the revision limit. Reload before saving.']);
            }
            if ($data === null) {
                $source = DB::table('storefront_blog_post_versions')->where('slug', $slug)->where('revision', $sourceRevision)->first();
                if ($source === null || (int) $source->revision === $revision) {
                    throw ValidationException::withMessages(['source_revision' => 'That version is unavailable or already current.']);
                }
                $fields = $this->fields((array) $source);
                $type = 'restore';
            } else {
                $fields = $this->fields($data);
                $type = 'edit';
            }
            $next = $revision + 1;
            DB::table('storefront_blog_posts')->where('slug', $slug)->update(array_merge($fields, ['revision' => $next, 'updated_at' => now()]));
            $this->snapshot($actorId, $slug, $next, $fields, $type, $sourceRevision);
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

    private function fields(array $data): array
    {
        return ['title' => $data['title'], 'summary' => $data['summary'], 'body' => $data['body'], 'published' => (bool) $data['published']];
    }

    private function snapshot(int $actorId, string $slug, int $revision, array $fields, string $type, ?int $source): void
    {
        DB::table('storefront_blog_post_versions')->insert(array_merge($fields, [
            'slug' => $slug, 'revision' => $revision, 'actor_id' => $actorId, 'change_type' => $type,
            'restored_from_revision' => $source, 'created_at' => now(),
        ]));
    }
}
