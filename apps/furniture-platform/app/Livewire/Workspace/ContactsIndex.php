<?php

declare(strict_types=1);

namespace App\Livewire\Workspace;

use App\Models\Contact;
use App\Support\Customers\SearchPattern;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class ContactsIndex extends Component
{
    use WithPagination;

    public $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('contacts.view');
        $query = Contact::query()->select(['id', 'name', 'email', 'phone', 'created_at']);
        if (! is_string($this->search) || mb_strlen($this->search) > 100) {
            $this->addError('search', 'Search must be text of at most 100 characters.');
            $query->whereRaw('1 = 0');
        } elseif (trim($this->search) !== '') {
            $this->resetErrorBag('search');
            $pattern = SearchPattern::contains($this->search);
            $query->where(function (Builder $query) use ($pattern): void {
                $query->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("LOWER(email) LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("LOWER(phone) LIKE ? ESCAPE '!'", [$pattern]);
            });
        } else {
            $this->resetErrorBag('search');
        }

        return view('livewire.workspace.contacts-index', [
            'contacts' => $query->orderByDesc('created_at')->orderByDesc('id')->paginate(10),
        ]);
    }
}
