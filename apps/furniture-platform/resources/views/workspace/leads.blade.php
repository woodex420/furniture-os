@extends('workspace.layout')

@section('workspace-title', 'Leads')

@section('workspace-content')
    <div class="content list-page">
        <div class="demo-banner"><span>WORKFLOW IN PROGRESS</span> @if(config('furniture.lead_creation_enabled'))Lead creation is available for testing;@else Lead creation is not enabled;@endif editing, assignment, imports and integrations are pending.</div>
        <div class="page-heading list-heading"><div><div class="eyebrow">WORKSPACE <span>/</span> CUSTOMERS</div><h1>Leads</h1><p>View enquiries in your workspace.</p></div>@if(config('furniture.lead_creation_enabled')) @can('leads.create')<a class="contact-create-button" href="{{ route('admin.leads.create', absolute: false) }}">+ Add lead</a>@endcan @else <span class="preview-label">READ ONLY · IN PROGRESS</span> @endif</div>
        <livewire:workspace.leads-index />
        <footer>WOODEX OS <span>Customer workflows in progress</span><span>Built for better work</span></footer>
    </div>
@endsection
