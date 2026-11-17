@extends('workspace.layout')

@section('workspace-title', 'Lead details')

@section('workspace-content')
    <div class="content list-page">
        <div class="demo-banner"><span>READ-ONLY FOUNDATION</span> Editing, assignment, follow-ups and integrations are pending.</div>
        <div class="page-heading list-heading"><div><div class="eyebrow">WORKSPACE <span>/</span> CUSTOMERS <span>/</span> LEADS</div><h1>{{ $lead->title }}</h1><p>Lead details</p></div><span class="preview-label">READ ONLY · IN PROGRESS</span></div>
        <section class="card customer-detail" aria-labelledby="lead-details-title">
            <a class="customer-back" href="{{ route('admin.leads') }}">← Back to leads</a>
            <h2 id="lead-details-title">Lead details</h2>
            <dl>
                <div><dt>Title</dt><dd>{{ $lead->title }}</dd></div>
                <div><dt>Contact</dt><dd>@if(!$canViewContacts) Restricted @elseif($lead->contact) <a class="customer-record-link" href="{{ route('admin.contacts.show', $lead->contact->id) }}">{{ $lead->contact->name }}</a> @else — @endif</dd></div>
                <div><dt>Source</dt><dd>{{ $lead->source ?: '—' }}</dd></div>
                <div><dt>Status</dt><dd><span class="record-status">{{ $lead->status }}</span></dd></div>
                <div><dt>Added</dt><dd>{{ $lead->created_at?->format('d M Y') ?? '—' }}</dd></div>
            </dl>
            <p class="workspace-pending-note">This record is read-only. Actions and history are not connected yet.</p>
        </section>
        <footer>WOODEX OS <span>Customer workflows in progress</span><span>Built for better work</span></footer>
    </div>
@endsection
