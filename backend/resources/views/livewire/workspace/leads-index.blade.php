<section class="card records-card" aria-labelledby="leads-list-title">
    <div class="records-title">
        <div>
            <h2 id="leads-list-title">All leads</h2>
            <p>Owner access only · {{ config('furniture.lead_editing_enabled') && auth()->user()->can('leads.edit') ? 'limited title and source editing' : 'editing not available yet' }}</p>
        </div>
        <span class="sample-chip">{{ config('furniture.lead_creation_enabled') || config('furniture.lead_editing_enabled') ? 'Testing' : 'Read only' }}</span>
    </div>
    <div class="records-tools">
        <label class="records-search">
            @include('workspace.icon', ['icon' => 'Search'])
            <span class="sr-only">{{ $canViewContacts ? 'Search leads by title or contact' : 'Search leads by title' }}</span>
            <input type="search" wire:model.live.debounce.350ms="search" maxlength="100" placeholder="Search leads...">
        </label>
        <label class="records-filter">
            <span class="sr-only">Filter leads by status</span>
            <select name="status" wire:model.live="status">
                <option value="">All statuses</option>
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                @endforeach
            </select>
        </label>
    </div>
    @error('search') <p class="customer-validation" role="alert">{{ $message }}</p> @enderror
    @error('status') <p class="customer-validation" role="alert">{{ $message }}</p> @enderror
    <p class="customer-loading" wire:loading role="status">Updating results…</p>
    <p class="customer-validation" wire:offline role="status">Connection lost. Results may be out of date; reconnect to continue.</p>
    <div class="table-wrap" wire:loading.attr="aria-busy">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Contact</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leads as $lead)
                    <tr wire:key="lead-{{ $lead->id }}">
                        <td><a class="record-name customer-record-link" href="{{ route('admin.leads.show', $lead->id, false) }}">{{ $lead->title }}</a></td>
                        <td>{{ $canViewContacts ? ($lead->contact?->name ?? '—') : 'Restricted' }}</td>
                        <td>{{ $lead->source ?: '—' }}</td>
                        <td><span class="record-status">{{ $lead->status }}</span></td>
                        <td>{{ $lead->created_at?->format('d M Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="list-empty">
                                <strong>No leads found</strong>
                                <p>{{ is_string($search) && trim($search) !== '' ? 'Try another search.' : 'No leads match the status filter.' }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $leads->links() }}
    </div>
</section>