<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use LogicException;

/** First, limited connection between Furniture admin and the real homepage component. */
final class StorefrontHomeController
{
    public function publicContent(): JsonResponse
    {
        abort_unless(config('furniture.home_editor_enabled'), 404);
        $content = DB::table('storefront_home_content')->where('id', 1)->first(['hero_title', 'hero_subtitle', 'revision']);
        abort_if($content === null, 503);

        return response()->json($content)->header('Cache-Control', 'no-store');
    }

    public function edit(): View
    {
        $this->authorizeEditing();
        $content = DB::table('storefront_home_content')->where('id', 1)->first();
        abort_if($content === null, 503);

        return view('workspace.storefront-home', ['content' => $content]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeEditing();
        $request->merge([
            'hero_title' => is_string($request->input('hero_title')) ? trim($request->input('hero_title')) : $request->input('hero_title'),
            'hero_subtitle' => is_string($request->input('hero_subtitle')) ? trim($request->input('hero_subtitle')) : $request->input('hero_subtitle'),
        ]);
        $data = $request->validate([
            'hero_title' => ['required', 'string', 'max:120'],
            'hero_subtitle' => ['required', 'string', 'max:240'],
            'revision' => ['required', 'integer', 'min:0', 'max:4294967294'],
        ]);
        $actorId = Auth::id();
        DB::transaction(function () use ($actorId, $data): void {
            $actor = User::query()->lockForUpdate()->find($actorId);
            if ($actor === null) {
                throw new AuthorizationException;
            }
            Gate::forUser($actor)->authorize('workspace.view');
            Gate::forUser($actor)->authorize('storefront.edit');
            $row = DB::table('storefront_home_content')->where('id', 1)->lockForUpdate()->first();
            if ($row === null) {
                throw new LogicException('Storefront home row is missing.');
            }
            if ((int) $row->revision !== (int) $data['revision']) {
                throw ValidationException::withMessages(['revision' => 'This page changed since you opened it. Reload before saving your changes.']);
            }
            $nextRevision = (int) $row->revision + 1;
            DB::table('storefront_home_content')->where('id', 1)->update([
                'hero_title' => $data['hero_title'], 'hero_subtitle' => $data['hero_subtitle'],
                'revision' => $nextRevision, 'updated_at' => now(),
            ]);
            DB::table('storefront_home_changes')->insert(['actor_id' => $actorId, 'revision' => $nextRevision, 'created_at' => now()]);
        }, 3);

        return redirect()->to(route('admin.storefront.home', absolute: false))->with('status', 'Home slide updated. The storefront preview will refresh.');
    }

    private function authorizeEditing(): void
    {
        abort_unless(config('furniture.home_editor_enabled'), 404);
        Gate::authorize('workspace.view');
        Gate::authorize('storefront.edit');
    }
}
