@extends('workspace.layout')

@section('workspace-title', $post ? 'Edit article' : 'Add article')

@section('workspace-content')
@php($visibility = (string) old('published', $post ? (int) $post->published : 1))
<div class="content list-page">
    <div class="demo-banner"><span>BLOG PREVIEW</span> Plain-text articles only. Images, formatting, authors and SEO controls are not connected yet.</div>
    <div class="page-heading list-heading"><div><div class="eyebrow">WEBSITE STUDIO <span>/</span> BLOG</div><h1>{{ $post ? 'Edit article' : 'Add article' }}</h1><p><a href="{{ route('admin.website.blog', absolute: false) }}">← All articles</a></p></div></div>
    @if(session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
    <section class="card customer-detail contact-create">
        <h2>Article content</h2>
        <p class="form-help">Published articles go live in the connected preview when saved. A draft is private. The URL cannot be changed after creation. Text is plain text, not HTML.</p>
        @if ($errors->any())<div class="form-error" role="alert">Please check the fields. @error('revision'){{ $message }} @if($post)<a href="{{ route('admin.website.blog.edit', ['slug' => $post->slug], absolute: false) }}">Reload editor</a>.@endif @enderror @error('source_revision'){{ $message }}@enderror</div>@endif
        <form method="POST" action="{{ $post ? route('admin.website.blog.update', ['slug' => $post->slug], absolute: false) : route('admin.website.blog.store', absolute: false) }}">
            @csrf
            @if ($post)
                <input type="hidden" name="revision" value="{{ is_scalar(old('revision')) ? old('revision') : $post->revision }}"><p>URL: /blog/{{ $post->slug }}</p>
            @else
                <div class="form-field"><label for="blog-slug">URL slug</label><input id="blog-slug" name="slug" type="text" maxlength="80" required pattern="[a-z0-9]+(-[a-z0-9]+)*" value="{{ is_string(old('slug')) ? old('slug') : '' }}" @error('slug') aria-invalid="true" aria-describedby="blog-slug-error" @enderror><p class="form-help">Lowercase letters, numbers and single hyphens only. To preserve an old link, use its exact slug after verifying the article content.</p>@error('slug')<p class="field-error" id="blog-slug-error">{{ $message }}</p>@enderror</div>
            @endif
            <div class="form-field"><label for="blog-title">Title</label><input id="blog-title" name="title" type="text" maxlength="160" required value="{{ is_string(old('title', $post->title ?? '')) ? old('title', $post->title ?? '') : '' }}" @error('title') aria-invalid="true" aria-describedby="blog-title-error" @enderror>@error('title')<p class="field-error" id="blog-title-error">{{ $message }}</p>@enderror</div>
            <div class="form-field"><label for="blog-summary">Summary</label><textarea id="blog-summary" name="summary" maxlength="300" required @error('summary') aria-invalid="true" aria-describedby="blog-summary-error" @enderror>{{ is_string(old('summary', $post->summary ?? '')) ? old('summary', $post->summary ?? '') : '' }}</textarea>@error('summary')<p class="field-error" id="blog-summary-error">{{ $message }}</p>@enderror</div>
            <div class="form-field"><label for="blog-body">Article content</label><textarea id="blog-body" name="body" rows="12" maxlength="12000" required @error('body') aria-invalid="true" aria-describedby="blog-body-error" @enderror>{{ is_string(old('body', $post->body ?? '')) ? old('body', $post->body ?? '') : '' }}</textarea>@error('body')<p class="field-error" id="blog-body-error">{{ $message }}</p>@enderror</div>
            <div class="form-field"><label for="blog-published">Visibility</label><select id="blog-published" name="published" required @error('published') aria-invalid="true" aria-describedby="blog-published-error" @enderror><option value="1" @selected($visibility === '1')>Published in preview on save</option><option value="0" @selected($visibility === '0')>Private draft</option></select>@error('published')<p class="field-error" id="blog-published-error">{{ $message }}</p>@enderror</div>
            <div class="form-actions"><button type="submit">Save article</button></div>
        </form>
    </section>
    @if ($post)
        <section class="card customer-detail contact-create">
            <h2>Recent versions</h2>
            <p class="form-help">Restoring creates a new version, including the earlier visibility. Restoring a published version makes it visible in the preview.</p>
            @foreach ($versions as $version)
                <article><h3>Version {{ $version->revision }}{{ (int) $version->revision === (int) $post->revision ? ' · Current' : '' }}</h3>
                    <p>{{ $version->title }} · {{ $version->published ? 'Published' : 'Draft' }}</p><p>{{ $version->summary }}</p>
                    @if ((int) $version->revision !== (int) $post->revision)
                        <form method="POST" action="{{ route('admin.website.blog.restore', ['slug' => $post->slug], absolute: false) }}">@csrf<input type="hidden" name="revision" value="{{ $post->revision }}"><input type="hidden" name="source_revision" value="{{ $version->revision }}"><button type="submit">Restore version {{ $version->revision }}</button></form>
                    @endif
                </article>
            @endforeach
        </section>
    @endif
</div>
@endsection
