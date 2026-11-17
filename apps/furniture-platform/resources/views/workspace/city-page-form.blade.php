@extends('workspace.layout')

@section('workspace-title', $page ? 'Edit city page' : 'Add city page')

@section('workspace-content')
@php($visibility = (string) old('published', $page ? (int) $page->published : 1))
<div class="content list-page">
    <div class="demo-banner"><span>CITY PAGE PREVIEW</span> Use verified service-area information only. This is not a physical showroom listing.</div>
    <div class="page-heading list-heading"><div><div class="eyebrow">WEBSITE STUDIO <span>/</span> CITY PAGES</div><h1>{{ $page ? 'Edit city page' : 'Add city page' }}</h1><p><a href="{{ route('admin.website.cities', absolute: false) }}">← All city pages</a></p></div></div>
    @if(session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
    <section class="card customer-detail contact-create">
        <h2>Service-area content</h2>
        <p class="form-help">Published pages go live in the connected preview when saved. A draft is private. The URL cannot be changed after creation. Text is plain text, not HTML.</p>
        @if ($errors->any())<div class="form-error" role="alert">Please check the fields. @error('revision'){{ $message }} @if($page)<a href="{{ route('admin.website.cities.edit', ['slug' => $page->slug], absolute: false) }}">Reload editor</a>.@endif @enderror @error('source_revision'){{ $message }}@enderror</div>@endif
        <form method="POST" action="{{ $page ? route('admin.website.cities.update', ['slug' => $page->slug], absolute: false) : route('admin.website.cities.store', absolute: false) }}">
            @csrf
            @if ($page)
                <input type="hidden" name="revision" value="{{ $page->revision }}">
                <p>URL: /locations/{{ $page->slug }}</p>
            @else
                <div class="form-field"><label for="city-slug">URL slug</label><input id="city-slug" name="slug" type="text" maxlength="80" required pattern="[a-z0-9]+(-[a-z0-9]+)*" value="{{ is_string(old('slug')) ? old('slug') : '' }}" @error('slug') aria-invalid="true" aria-describedby="city-slug-error" @enderror><p class="form-help">Lowercase letters, numbers and single hyphens only, e.g. example-city.</p>@error('slug')<p class="field-error" id="city-slug-error">{{ $message }}</p>@enderror</div>
            @endif
            <div class="form-field"><label for="city-name">City name</label><input id="city-name" name="city_name" type="text" maxlength="100" required value="{{ is_string(old('city_name', $page->city_name ?? '')) ? old('city_name', $page->city_name ?? '') : '' }}" @error('city_name') aria-invalid="true" aria-describedby="city-name-error" @enderror>@error('city_name')<p class="field-error" id="city-name-error">{{ $message }}</p>@enderror</div>
            <div class="form-field"><label for="city-title">Page title</label><input id="city-title" name="title" type="text" maxlength="120" required value="{{ is_string(old('title', $page->title ?? '')) ? old('title', $page->title ?? '') : '' }}" @error('title') aria-invalid="true" aria-describedby="city-title-error" @enderror>@error('title')<p class="field-error" id="city-title-error">{{ $message }}</p>@enderror</div>
            <div class="form-field"><label for="city-summary">Summary</label><textarea id="city-summary" name="summary" maxlength="240" required @error('summary') aria-invalid="true" aria-describedby="city-summary-error" @enderror>{{ is_string(old('summary', $page->summary ?? '')) ? old('summary', $page->summary ?? '') : '' }}</textarea>@error('summary')<p class="field-error" id="city-summary-error">{{ $message }}</p>@enderror</div>
            <div class="form-field"><label for="city-body">Page content</label><textarea id="city-body" name="body" rows="8" maxlength="4000" required @error('body') aria-invalid="true" aria-describedby="city-body-error" @enderror>{{ is_string(old('body', $page->body ?? '')) ? old('body', $page->body ?? '') : '' }}</textarea>@error('body')<p class="field-error" id="city-body-error">{{ $message }}</p>@enderror</div>
            <div class="form-field"><label for="city-published">Visibility</label><select id="city-published" name="published" required @error('published') aria-invalid="true" aria-describedby="city-published-error" @enderror><option value="1" @selected($visibility === '1')>Published in preview on save</option><option value="0" @selected($visibility === '0')>Private draft</option></select>@error('published')<p class="field-error" id="city-published-error">{{ $message }}</p>@enderror</div>
            <div class="form-actions"><button type="submit">Save city page</button></div>
        </form>
    </section>
    @if ($page)
        <section class="card customer-detail contact-create">
            <h2>Recent versions</h2>
            <p class="form-help">Restore creates a new version, including the earlier visibility. Restoring a published version makes it visible in the connected preview.</p>
            @foreach ($versions as $version)
                <article><h3>Version {{ $version->revision }}{{ (int) $version->revision === (int) $page->revision ? ' · Current' : '' }}</h3>
                    <p>{{ $version->title }} · {{ $version->published ? 'Published' : 'Draft' }}</p><p>{{ $version->summary }}</p>
                    @if ((int) $version->revision !== (int) $page->revision)
                        <form method="POST" action="{{ route('admin.website.cities.restore', ['slug' => $page->slug], absolute: false) }}">@csrf<input type="hidden" name="revision" value="{{ $page->revision }}"><input type="hidden" name="source_revision" value="{{ $version->revision }}"><button type="submit">Restore version {{ $version->revision }}</button></form>
                    @endif
                </article>
            @endforeach
        </section>
    @endif
</div>
@endsection
