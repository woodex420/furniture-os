<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Models\Contact;
use App\Models\Lead;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Read-only details. Record ownership and staff access are not implemented. */
final class CustomerDetailController
{
    public function lead(string $id): View
    {
        Gate::authorize('leads.view');
        $canViewContacts = Gate::allows('contacts.view');
        $query = Lead::query()->select(['id', 'contact_id', 'title', 'source', 'status', 'created_at']);
        if ($canViewContacts) {
            $query->with('contact:id,name');
        }

        return view('workspace.lead-detail', [
            'lead' => $query->findOrFail($id),
            'canViewContacts' => $canViewContacts,
        ]);
    }

    public function contact(string $id): View
    {
        Gate::authorize('contacts.view');

        return view('workspace.contact-detail', [
            'contact' => Contact::query()->select(['id', 'name', 'email', 'phone', 'created_at'])->findOrFail($id),
        ]);
    }
}
