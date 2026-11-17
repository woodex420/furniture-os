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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('leads.view');
        $query = Lead::query()->select(['id', 'contact_id', 'title', 'source', 'status', 'created_at'])
            ->with('contact:id,name');
        if (! is_string($this->search) || mb_strlen($this->search) > 100) {
            $this->addError('search', 'Search must be text of at most 100 characters.');
            $query->whereRaw('1 = 0');
        } elseif (trim($this->search) !== '') {
            $this->resetErrorBag('search');
            $pattern = SearchPattern::contains($this->search);
            $query->where(function (Builder $query) use ($pattern): void {
                $query->whereRaw("LOWER(title) LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereHas('contact', fn (Builder $contact) => $contact->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$pattern]));
            });
        } else {
            $this->resetErrorBag('search');
        }

        return view('livewire.workspace.leads-index', [
            'leads' => $query->orderByDesc('created_at')->orderByDesc('id')->paginate(10),
        ]);
    }
}
