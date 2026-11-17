<?php

declare(strict_types=1);

namespace App\Livewire\Workspace;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Read-only dashboard summary; metrics remain unavailable until their sources
 * and permissions are implemented.
 */
class DashboardSummary extends Component
{
    public function render(): View
    {
        // Recheck on every Livewire request, not only when a component is mounted.
        Gate::authorize('workspace.view');

        return view('livewire.workspace.dashboard-summary');
    }
}
