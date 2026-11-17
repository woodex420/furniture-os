@extends('workspace.layout')

@section('workspace-title', 'City pages')

@section('workspace-content')
<div class="content list-page">
    <div class="demo-banner"><span>CITY PAGE PREVIEW</span> Service areas are separate from physical showrooms. Add only locations and claims you have verified.</div>
    <div class="page-heading list-heading"><div><div class="eyebrow">WEBSITE STUDIO <span>/</span> CITY PAGES</div><h1>City pages</h1><p>Manage service-area pages in the connected storefront preview.</p></div></div>
    <section class="card customer-detail contact-create">
        <h2>Service areas</h2>
        <p class="form-help">A published page appears in the preview when saved. Draft pages remain private. There are no preloaded Furniture locations.</p>
        <p><a href="{{ route('admin.website.cities.create', absolute: false) }}">Add city page</a></p>
        @if ($pages->isEmpty())
            <p>No city pages yet.</p>
        @else
            <ul>@foreach ($pages as $item)
                <li><a href="{{ route('admin.website.cities.edit', ['slug' => $item->slug], absolute: false) }}">{{ $item->city_name }}</a> · /locations/{{ $item->slug }} · {{ $item->published ? 'Published' : 'Draft' }} · version {{ $item->revision }}</li>
            @endforeach</ul>
            {{ $pages->links() }}
        @endif
    </section>
</div>
@endsection
