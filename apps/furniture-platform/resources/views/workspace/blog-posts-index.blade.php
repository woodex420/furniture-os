@extends('workspace.layout')

@section('workspace-title', 'Blog')

@section('workspace-content')
<div class="content list-page">
    <div class="demo-banner"><span>BLOG PREVIEW</span> Furniture articles only. Old bundled and browser-local demo articles are not imported.</div>
    <div class="page-heading list-heading"><div><div class="eyebrow">WEBSITE STUDIO <span>/</span> BLOG</div><h1>Blog articles</h1><p>Manage articles shown in the connected storefront preview.</p></div></div>
    <section class="card customer-detail contact-create">
        <h2>Articles</h2>
        <p class="form-help">Published articles appear in the preview when saved. Drafts remain private. Only enter content you are authorized to use.</p>
        <p><a href="{{ route('admin.website.blog.create', absolute: false) }}">Add article</a></p>
        @if ($posts->isEmpty())
            <p>No Furniture articles yet.</p>
        @else
            <ul>@foreach ($posts as $item)
                <li><a href="{{ route('admin.website.blog.edit', ['slug' => $item->slug], absolute: false) }}">{{ $item->title }}</a> · /blog/{{ $item->slug }} · {{ $item->published ? 'Published' : 'Draft' }} · version {{ $item->revision }}</li>
            @endforeach</ul>
            {{ $posts->links() }}
        @endif
    </section>
</div>
@endsection
