<?php

declare(strict_types=1);

namespace App\Support\Website;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use LogicException;

/** Transactional, append-only history for the limited connected home hero. */
final class StorefrontHomeRevisions
{
    /** @param array{hero_title: string, hero_subtitle: string, revision: int|string, hero_order?: string, hero_cta_label?: string, hero_cta_path?: string, hero_image?: string} $data */
    public function edit(int $actorId, array $data): void
    {
        $this->change($actorId, (int) $data['revision'], $data, null);
    }

    public function restore(int $actorId, int $expectedRevision, int $sourceRevision): void
    {
        $this->change($actorId, $expectedRevision, null, $sourceRevision);
    }

    private function change(int $actorId, int $expectedRevision, ?array $data, ?int $sourceRevision): void
    {
        DB::transaction(function () use ($actorId, $expectedRevision, $data, $sourceRevision): void {
            // Recheck permissions using the current database record after acquiring its lock.
            $actor = User::query()->lockForUpdate()->find($actorId);
            if ($actor === null) {
                throw new AuthorizationException;
            }
            Gate::forUser($actor)->authorize('workspace.view');
            Gate::forUser($actor)->authorize('storefront.edit');
            $home = DB::table('storefront_home_content')->where('id', 1)->lockForUpdate()->first();
            if ($home === null) {
                throw new LogicException('Storefront home row is missing.');
            }
            HeroTextOrder::resolve($home->hero_order);
            HeroImage::resolve($home->hero_image);
            if ((int) $home->revision !== $expectedRevision) {
                throw ValidationException::withMessages(['revision' => 'This page changed since you opened it. Reload before saving your changes.']);
            }
            if ($expectedRevision >= 4294967295) {
                throw ValidationException::withMessages(['revision' => 'The revision limit has been reached.']);
            }
            if ($data === null) {
                $version = DB::table('storefront_home_versions')->where('revision', $sourceRevision)->first();
                if ($version === null) {
                    throw ValidationException::withMessages(['source_revision' => 'That version is no longer available.']);
                }
                if ((int) $version->revision === $expectedRevision) {
                    throw ValidationException::withMessages(['source_revision' => 'This version is already current.']);
                }
                $title = $version->hero_title;
                $subtitle = $version->hero_subtitle;
                $order = HeroTextOrder::resolve($version->hero_order);
                $ctaLabel = $version->hero_cta_label;
                $ctaPath = $version->hero_cta_path;
                $image = HeroImage::resolve($version->hero_image);
                $type = 'restore';
            } else {
                $title = $data['hero_title'];
                $subtitle = $data['hero_subtitle'];
                // Older clients must not reset fields they do not know about.
                $order = HeroTextOrder::resolve($data['hero_order'] ?? $home->hero_order);
                $ctaLabel = $data['hero_cta_label'] ?? $home->hero_cta_label;
                $ctaPath = $data['hero_cta_path'] ?? $home->hero_cta_path;
                $image = HeroImage::resolve($data['hero_image'] ?? $home->hero_image);
                $type = 'edit';
            }
            $next = $expectedRevision + 1;
            DB::table('storefront_home_content')->where('id', 1)->update([
                'hero_title' => $title, 'hero_subtitle' => $subtitle, 'hero_order' => $order,
                'hero_cta_label' => $ctaLabel, 'hero_cta_path' => $ctaPath, 'hero_image' => $image,
                'revision' => $next, 'updated_at' => now(),
            ]);
            DB::table('storefront_home_changes')->insert(['actor_id' => $actorId, 'revision' => $next, 'created_at' => now()]);
            DB::table('storefront_home_versions')->insert([
                'revision' => $next, 'hero_title' => $title, 'hero_subtitle' => $subtitle, 'hero_order' => $order,
                'hero_cta_label' => $ctaLabel, 'hero_cta_path' => $ctaPath, 'hero_image' => $image,
                'actor_id' => $actorId, 'change_type' => $type, 'restored_from_revision' => $sourceRevision, 'created_at' => now(),
            ]);
        }, 3);
    }
}
