<section class="card records-card" aria-labelledby="contacts-list-title">
    <div class="records-title"><div><h2 id="contacts-list-title">All contacts</h2><p>Read-only records · owner access only</p></div><span class="sample-chip">Read only</span></div>
    <div class="records-tools"><label class="records-search">@include('workspace.icon', ['icon' => 'Search'])<span class="sr-only">Search contacts by name, email or phone</span><input type="search" wire:model.live.debounce.350ms="search" maxlength="100" placeholder="Search contacts..."></label></div>
    @error('search') <p class="customer-validation" role="alert">{{ $message }}</p> @enderror
    <p class="customer-loading" wire:loading role="status">Updating results…</p>
    <p class="customer-validation" wire:offline role="status">Connection lost. Results may be out of date; reconnect to continue.</p>
    <div class="table-wrap" wire:loading.attr="aria-busy">
        <table><thead><tr><th scope="col">Contact</th><th scope="col">Email</th><th scope="col">Phone</th><th scope="col">Added</th></tr></thead>
            <tbody>
                @forelse ($contacts as $contact)
                    <tr wire:key="contact-{{ $contact->id }}"><td><span class="record-name">{{ $contact->name }}</span></td><td>{{ $contact->email ?: '—' }}</td><td>{{ $contact->phone ?: '—' }}</td><td>{{ $contact->created_at?->format('d M Y') ?? '—' }}</td></tr>
                @empty
                    <tr><td colspan="4"><div class="list-empty"><strong>No contacts found</strong><p>{{ is_string($search) && trim($search) !== '' ? 'Try another search.' : 'No contacts are available to view yet.' }}</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('livewire.workspace.pagination', ['records' => $contacts, 'label' => 'contacts'])
</section>
