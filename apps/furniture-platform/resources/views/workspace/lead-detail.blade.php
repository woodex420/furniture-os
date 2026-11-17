@extends('workspace.layout')

@section('workspace-title', 'Lead details')

@section('workspace-content')
    <div class="content list-page">
        @if(session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
        @php($canEditLead = config('furniture.lead_editing_enabled') && auth()->user()->can('leads.edit'))
        <div class="demo-banner"><span>{{ $canEditLead ? 'DEVELOPMENT WORKFLOW' : 'READ-ONLY FOUNDATION' }}</span> {{ $canEditLead ? 'Only title and source editing are available; assignment, follow-ups and integrations are pending.' : 'Editing, assignment, follow-ups and integrations are pending.' }}</div>
        <div class="page-heading list-heading"><div><div class="eyebrow">WORKSPACE <span>/</span> CUSTOMERS <span>/</span> LEADS</div><h1>{{ $lead->title }}</h1><p>Lead details</p></div><span class="preview-label">{{ $canEditLead ? 'TESTING · IN PROGRESS' : 'READ ONLY · IN PROGRESS' }}</span></div>
        <section class="card customer-detail" aria-labelledby="lead-details-title">
            <a class="customer-back" href="{{ route('admin.leads', absolute: false) }}">← Back to leads</a>
            <h2 id="lead-details-title">Lead details</h2>
            <dl>
                <div><dt>Title</dt><dd>{{ $lead->title }}</dd></div>
                <div><dt>Contact</dt><dd>@if(!$canViewContacts) Restricted @elseif($lead->contact) <a class="customer-record-link" href="{{ route('admin.contacts.show', $lead->contact->id, false) }}">{{ $lead->contact->name }}</a> @else — @endif</dd></div>
                <div><dt>Source</dt><dd>{{ $lead->source ?: '—' }}</dd></div>
                <div><dt>Status</dt><dd><span class="record-status">{{ $lead->status }}</span></dd></div>
                <div><dt>Added</dt><dd>{{ $lead->created_at?->format('d M Y') ?? '—' }}</dd></div>
            </dl>
            @if($canEditLead)<div class="form-actions"><a href="{{ route('admin.leads.edit', $lead->id, false) }}">Edit title and source</a></div>@endif
            <p class="workspace-pending-note">{{ $canEditLead ? 'Status, contact assignment and history are not editable here.' : 'This record is read-only. Actions and history are not connected yet.' }}</p>
        </section>
        <footer>WOODEX OS <span>Customer workflows in progress</span><span>Built for better work</span></footer>
    </div>
@endsection
