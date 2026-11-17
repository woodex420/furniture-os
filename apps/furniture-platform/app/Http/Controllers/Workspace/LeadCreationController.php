<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Models\Contact;
use App\Support\Customers\CreateLeadCandidate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Experimental owner-only form; a hosted release needs independent review. */
final class LeadCreationController
{
    public function create(): View
    {
        $this->authorizeCreation();
        $canViewContacts = Gate::allows('contacts.view');

        return view('workspace.lead-create', [
            'requestKey' => (string) Str::uuid(),
            'canViewContacts' => $canViewContacts,
            'contacts' => $canViewContacts ? Contact::query()->select(['id', 'name'])->orderByDesc('id')->limit(100)->get() : collect(),
        ]);
    }

    public function store(Request $request, CreateLeadCandidate $service): RedirectResponse
    {
        $this->authorizeCreation();
        $lead = $service->create($request->only(['title', 'source', 'contact_id', 'request_key']));

        return redirect()->to(route('admin.leads.show', $lead->id, false))->with('status', 'Lead created.');
    }

    private function authorizeCreation(): void
    {
        abort_unless(config('furniture.lead_creation_enabled'), 404);
        Gate::authorize('workspace.view');
        Gate::authorize('leads.view');
        Gate::authorize('leads.create');
    }
}
