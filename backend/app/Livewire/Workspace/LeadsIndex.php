<?php

namespace App\Livewire\Workspace;

use Livewire\Component;
use Illuminate\Support\Facades\Gate;
use App\Models\Lead;

class LeadsIndex extends Component
{
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

    public function render(): \Illuminate\View\View
    {
        Gate::authorize('leads.view');

        $query = Lead::query();

        if (! empty($this->search)) {
            $query->where('title', 'like', '%' . $this->search . '%');
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
            'statuses' => Lead::query()->select('status')->where('status', '!=', '')->distinct()->orderBy('status')->limit(100)->pluck('status'),
            'canViewContacts' => auth()->user()?->can('leads.view-contacts') ?? false,
        ]);
    }
}