@extends('workspace.layout')

@section('workspace-title', 'Service pages')

@section('workspace-content')
<div class="content list-page">
    <div class="demo-banner"><span>SERVICE PREVIEW</span> Edit only the title and tagline of existing Reimagin service pages. Other content is still sample data.</div>
    <div class="page-heading list-heading"><div><div class="eyebrow">WEBSITE STUDIO <span>/</span> SERVICES</div><h1>Service pages</h1><p>Choose an existing service to edit its public preview text.</p></div></div>
    <section class="card customer-detail contact-create">
        <h2>Existing service routes</h2>
        <p class="form-help">Saving updates the connected preview. These routes and their remaining content have not been verified for production. Creating or deleting services is not supported yet.</p>
        <ul>
            @foreach ($services as $service)
                <li><a href="{{ route('admin.website.services.edit', ['slug' => $service->slug], absolute: false) }}">{{ $service->slug }}</a> · {{ $service->title === null ? 'Using sample text' : 'Custom text saved' }} · version {{ $service->revision }}</li>
            @endforeach
        </ul>
    </section>
</div>
@endsection
