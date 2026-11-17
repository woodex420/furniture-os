<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Support\Website\ServiceCopyRevisions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class ServiceCopyController
{
    public function publicContent(): JsonResponse
    {
        abort_unless(config('furniture.service_copy_enabled'), 404);

        return response()->json(DB::table('storefront_service_copy')->orderBy('slug')->get(['slug', 'title', 'tagline', 'revision']))->header('Cache-Control', 'no-store');
    }

    public function index(): View
    {
        $this->authorizeEditing();

        return view('workspace.service-copy-index', ['services' => DB::table('storefront_service_copy')->orderBy('slug')->get()]);
    }

    public function edit(string $slug): View
    {
        $this->authorizeEditing();
        $service = DB::table('storefront_service_copy')->where('slug', $slug)->first();
        abort_if($service === null, 404);

        return view('workspace.service-copy-edit', [
            'service' => $service,
            'versions' => DB::table('storefront_service_copy_versions')->where('slug', $slug)->orderByDesc('revision')->limit(20)->get(),
        ]);
    }

    public function update(Request $request, string $slug, ServiceCopyRevisions $revisions): RedirectResponse
    {
        $this->authorizeEditing();
        foreach (['title', 'tagline'] as $field) {
            if (is_string($request->input($field))) {
                $request->merge([$field => trim($request->input($field))]);
            }
        }
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'tagline' => ['required', 'string', 'max:240'],
            'revision' => ['required', 'integer', 'min:0', 'max:4294967294'],
        ]);
        $revisions->change((int) Auth::id(), $slug, (int) $data['revision'], $data);

        return redirect()->to(route('admin.website.services.edit', ['slug' => $slug], absolute: false))->with('status', 'Service text updated. The storefront preview will refresh.');
    }

    public function restore(Request $request, string $slug, ServiceCopyRevisions $revisions): RedirectResponse
    {
        $this->authorizeEditing();
        $data = $request->validate([
            'revision' => ['required', 'integer', 'min:0', 'max:4294967294'],
            'source_revision' => ['required', 'integer', 'min:0', 'max:4294967294'],
        ]);
        $revisions->change((int) Auth::id(), $slug, (int) $data['revision'], null, (int) $data['source_revision']);

        return redirect()->to(route('admin.website.services.edit', ['slug' => $slug], absolute: false))->with('status', 'Service version restored. The storefront preview will refresh.');
    }

    private function authorizeEditing(): void
    {
        abort_unless(config('furniture.service_copy_enabled'), 404);
        Gate::authorize('workspace.view');
        Gate::authorize('storefront.edit');
    }
}
