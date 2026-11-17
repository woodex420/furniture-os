@extends('workspace.layout')

@section('workspace-title', 'Contacts')

@section('workspace-content')
    <div class="content list-page">
        <div class="demo-banner"><span>WORKFLOW IN PROGRESS</span> @if(config('furniture.contact_creation_enabled'))Contact creation is available for testing;@else Contact creation is not enabled;@endif editing, imports and integrations are pending.</div>
        <div class="page-heading list-heading"><div><div class="eyebrow">WORKSPACE <span>/</span> CUSTOMERS</div><h1>Contacts</h1><p>View the people in your workspace.</p></div>@if(config('furniture.contact_creation_enabled')) @can('contacts.create')<a class="contact-create-button" href="{{ route('admin.contacts.create', absolute: false) }}">+ Add contact</a>@endcan @else <span class="preview-label">READ ONLY · IN PROGRESS</span> @endif</div>
        <livewire:workspace.contacts-index />
        <footer>WOODEX OS <span>Customer workflows in progress</span><span>Built for better work</span></footer>
    </div>
@endsection
