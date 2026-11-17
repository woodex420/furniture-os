<?php

declare(strict_types=1);

namespace App\Livewire\Workspace;

use App\Models\Lead;
use App\Support\Customers\SearchPattern;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

/** Read-only until a reviewed customer lifecycle and permissions exist. */
class LeadsIndex extends Component
{
    use WithPagination;

    public $search = '';

    public $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('leads.view');
        $canViewContacts = Gate::allows('contacts.view');
        $query = Lead::query()->select(['id', 'contact_id', 'title', 'source', 'status', 'created_at']);
        if ($canViewContacts) {
            $query->with('contact:id,name');
        }
        if (! is_string($this->search) || mb_strlen($this->search) > 100) {
            $this->addError('search', 'Search must be text of at most 100 characters.');
            $query->whereRaw('1 = 0');
        } elseif (trim($this->search) !== '') {
            $this->resetErrorBag('search');
            $pattern = SearchPattern::contains($this->search);
            $query->where(function (Builder $query) use ($pattern, $canViewContacts): void {
                $query->whereRaw("LOWER(title) LIKE ? ESCAPE '!'", [$pattern]);
                if ($canViewContacts) {
                    $query->orWhereHas('contact', fn (Builder $contact) => $contact->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$pattern]));
                }
            });
        } else {
            $this->resetErrorBag('search');
        }

        if (! is_string($this->status) || ! mb_check_encoding($this->status, 'UTF-8') || mb_strlen($this->status) > 50) {
            $this->addError('status', 'Status must be text of at most 50 characters.');
            $query->whereRaw('1 = 0');
        } else {
            $this->resetErrorBag('status');
            if ($this->status !== '') {
                $query->where('status', $this->status);
            }
        }

        return view('livewire.workspace.leads-index', [
            'leads' => $query->orderByDesc('created_at')->orderByDesc('id')->paginate(10),
            // Existing data determines options; this does not define a CRM lifecycle.
            // Bound the list until statuses have an approved managed vocabulary.
            'statuses' => Lead::query()->select('status')->where('status', '!=', '')->distinct()->orderBy('status')->limit(100)->pluck('status'),
            'canViewContacts' => $canViewContacts,
        ]);
    }
}
