<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Support\Customers\CreateContactCandidate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Experimental owner-only form; real-host deployment requires independent review. */
final class ContactCreationController
{
    public function create(): View
    {
        $this->authorizeCreation();

        return view('workspace.contact-create', ['requestKey' => (string) Str::uuid()]);
    }

    public function store(Request $request, CreateContactCandidate $service): RedirectResponse
    {
        $this->authorizeCreation();
        $contact = $service->create($request->only(['name', 'email', 'phone', 'request_key']));

        return redirect()->to(route('admin.contacts.show', $contact->id, false))
            ->with('status', 'Contact created.');
    }

    private function authorizeCreation(): void
    {
        abort_unless(config('furniture.contact_creation_enabled'), 404);
        Gate::authorize('workspace.view');
        Gate::authorize('contacts.view');
        Gate::authorize('contacts.create');
    }
}
