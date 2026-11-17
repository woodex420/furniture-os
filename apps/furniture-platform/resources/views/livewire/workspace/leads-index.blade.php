<section class="card records-card" aria-labelledby="leads-list-title">
    <div class="records-title"><div><h2 id="leads-list-title">All leads</h2><p>Read-only records · owner access only</p></div><span class="sample-chip">Read only</span></div>
    <div class="records-tools"><label class="records-search">@include('workspace.icon', ['icon' => 'Search'])<span class="sr-only">{{ $canViewContacts ? 'Search leads by title or contact' : 'Search leads by title' }}</span><input type="search" wire:model.live.debounce.350ms="search" maxlength="100" placeholder="Search leads..."></label></div>
    @error('search') <p class="customer-validation" role="alert">{{ $message }}</p> @enderror
    <p class="customer-loading" wire:loading role="status">Updating results…</p>
    <p class="customer-validation" wire:offline role="status">Connection lost. Results may be out of date; reconnect to continue.</p>
    <div class="table-wrap" wire:loading.attr="aria-busy">
        <table><thead><tr><th scope="col">Lead</th><th scope="col">Contact</th><th scope="col">Source</th><th scope="col">Status</th><th scope="col">Added</th></tr></thead>
            <tbody>
                @forelse ($leads as $lead)
                    <tr wire:key="lead-{{ $lead->id }}"><td><a class="record-name customer-record-link" href="{{ route('admin.leads.show', $lead->id) }}">{{ $lead->title }}</a></td><td>{{ $canViewContacts ? ($lead->contact?->name ?? '—') : 'Restricted' }}</td><td>{{ $lead->source ?: '—' }}</td><td><span class="record-status">{{ $lead->status }}</span></td><td>{{ $lead->created_at?->format('d M Y') ?? '—' }}</td></tr>
                @empty
                    <tr><td colspan="5"><div class="list-empty"><strong>No leads found</strong><p>{{ is_string($search) && trim($search) !== '' ? 'Try another search.' : 'No leads are available to view yet.' }}</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('livewire.workspace.pagination', ['records' => $leads, 'label' => 'leads'])
</section>
