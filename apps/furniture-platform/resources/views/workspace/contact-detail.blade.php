@extends('workspace.layout')

@section('workspace-title', 'Contact details')

@section('workspace-content')
    <div class="content list-page">
        @if(session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
        <div class="demo-banner"><span>READ-ONLY DETAILS</span> Editing, activity and integrations are pending.</div>
        <div class="page-heading list-heading"><div><div class="eyebrow">WORKSPACE <span>/</span> CUSTOMERS <span>/</span> CONTACTS</div><h1>{{ $contact->name }}</h1><p>Contact details</p></div><span class="preview-label">READ ONLY · IN PROGRESS</span></div>
        <section class="card customer-detail" aria-labelledby="contact-details-title">
            <a class="customer-back" href="{{ route('admin.contacts', absolute: false) }}">← Back to contacts</a>
            <h2 id="contact-details-title">Contact details</h2>
            <dl>
                <div><dt>Name</dt><dd>{{ $contact->name }}</dd></div>
                <div><dt>Email</dt><dd>{{ $contact->email ?: '—' }}</dd></div>
                <div><dt>Phone</dt><dd>{{ $contact->phone ?: '—' }}</dd></div>
                <div><dt>Added</dt><dd>{{ $contact->created_at?->format('d M Y') ?? '—' }}</dd></div>
            </dl>
            <p class="workspace-pending-note">This record is read-only. Actions and history are not connected yet.</p>
        </section>
        <footer>WOODEX OS <span>Customer workflows in progress</span><span>Built for better work</span></footer>
    </div>
@endsection
