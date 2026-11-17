@extends('workspace.layout')

@section('workspace-title', 'Service page')

@section('workspace-content')
<div class="content list-page">
    <div class="demo-banner"><span>SERVICE PREVIEW</span> Only title and tagline are connected. Remaining page content and SEO metadata are still sample data.</div>
    <div class="page-heading list-heading"><div><div class="eyebrow">WEBSITE STUDIO <span>/</span> SERVICES</div><h1>{{ $service->slug }}</h1><p><a href="{{ route('admin.website.services', absolute: false) }}">← All service pages</a></p></div></div>
    @if(session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
    <section class="card customer-detail contact-create">
        <h2>Service text</h2>
        <p class="form-help">When no custom text is saved, the storefront shows its bundled sample. Enter both fields to replace it. Saving publishes to the connected preview; restore version 0 to return to the sample.</p>
        @if ($errors->any())<div class="form-error" role="alert">Please check the fields. @error('revision'){{ $message }} <a href="{{ route('admin.website.services.edit', ['slug' => $service->slug], absolute: false) }}">Reload editor</a>.@enderror @error('source_revision'){{ $message }}@enderror</div>@endif
        <form method="POST" action="{{ route('admin.website.services.update', ['slug' => $service->slug], absolute: false) }}">
            @csrf
            <input type="hidden" name="revision" value="{{ $service->revision }}">
            <div class="form-field"><label for="service-title">Title</label><input id="service-title" name="title" type="text" maxlength="120" required value="{{ is_string(old('title', $service->title)) ? old('title', $service->title) : '' }}" @error('title') aria-invalid="true" aria-describedby="service-title-error" @enderror>@error('title')<p class="field-error" id="service-title-error">{{ $message }}</p>@enderror</div>
            <div class="form-field"><label for="service-tagline">Tagline</label><input id="service-tagline" name="tagline" type="text" maxlength="240" required value="{{ is_string(old('tagline', $service->tagline)) ? old('tagline', $service->tagline) : '' }}" @error('tagline') aria-invalid="true" aria-describedby="service-tagline-error" @enderror>@error('tagline')<p class="field-error" id="service-tagline-error">{{ $message }}</p>@enderror</div>
            <div class="form-actions"><button type="submit">Update service preview</button></div>
        </form>
    </section>
    <section class="card customer-detail contact-create">
        <h2>Recent versions</h2>
        <p class="form-help">The latest 20 versions are shown. Restore creates a new version and updates the preview.</p>
        @foreach ($versions as $version)
            <article>
                <h3>Version {{ $version->revision }}{{ (int) $version->revision === (int) $service->revision ? ' · Current' : '' }}</h3>
                <p>Title: {{ $version->title ?? 'Bundled sample' }}</p><p>Tagline: {{ $version->tagline ?? 'Bundled sample' }}</p>
                @if ((int) $version->revision !== (int) $service->revision)
                    <form method="POST" action="{{ route('admin.website.services.restore', ['slug' => $service->slug], absolute: false) }}">
                        @csrf <input type="hidden" name="revision" value="{{ $service->revision }}"><input type="hidden" name="source_revision" value="{{ $version->revision }}">
                        <button type="submit">Restore version {{ $version->revision }}</button>
                    </form>
                @endif
            </article>
        @endforeach
    </section>
</div>
@endsection
