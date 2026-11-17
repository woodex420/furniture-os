<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Models\Lead;
use App\Support\Customers\EditLeadCandidate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class LeadEditController
{
    public function edit(string $id): View
    {
        $this->authorizeEditing();

        return view('workspace.lead-edit', [
            'lead' => $this->findLead($id), 'leadEditErrors' => new MessageBag, 'formInput' => null,
        ]);
    }

    public function update(string $id, Request $request, EditLeadCandidate $service): RedirectResponse|Response
    {
        $this->authorizeEditing();
        $input = $request->only(['title', 'source', 'edit_revision']);
        try {
            $lead = $service->update((int) $id, $input);
        } catch (ValidationException $exception) {
            // Show the attempted values without relying on a session redirect. The stale
            // revision stays stale until the user deliberately reloads the record.
            return response()->view('workspace.lead-edit', [
                'lead' => $this->findLead($id), 'leadEditErrors' => new MessageBag($exception->errors()), 'formInput' => $input,
            ], 422);
        }

        return redirect()->to(route('admin.leads.show', $lead->id, false))->with('status', 'Lead details saved.');
    }

    private function findLead(string $id): Lead
    {
        return Lead::query()->select(['id', 'title', 'source', 'edit_revision'])->findOrFail($id);
    }

    private function authorizeEditing(): void
    {
        abort_unless(config('furniture.lead_editing_enabled'), 404);
        foreach (['workspace.view', 'leads.view', 'leads.edit'] as $ability) {
            Gate::authorize($ability);
        }
    }
}
