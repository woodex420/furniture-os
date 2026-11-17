@extends('workspace.layout')

@section('workspace-title', 'Leads')

@section('workspace-content')
    <div class="content list-page">
        <div class="demo-banner"><span>READ-ONLY FOUNDATION</span> Lead creation, editing, assignment, imports and integrations are pending.</div>
        <div class="page-heading list-heading"><div><div class="eyebrow">WORKSPACE <span>/</span> CUSTOMERS</div><h1>Leads</h1><p>View enquiries in your workspace.</p></div><span class="preview-label">READ ONLY · IN PROGRESS</span></div>
        <livewire:workspace.leads-index />
        <footer>WOODEX OS <span>Customer workflows in progress</span><span>Built for better work</span></footer>
    </div>
@endsection
