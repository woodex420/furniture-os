@extends('workspace.layout')

@section('workspace-title', 'Media library')

@section('workspace-content')
<div class="content list-page">
    <div class="demo-banner"><span>EXPERIMENTAL PRIVATE MEDIA</span> Images here are private and are not yet connected to the storefront.</div>
    <div class="page-heading list-heading"><div><div class="eyebrow">WEBSITE STUDIO <span>/</span> MEDIA</div><h1>Media library</h1><p>Upload a Furniture image for private review.</p></div></div>
    @if(session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
    <section class="card customer-detail contact-create" aria-labelledby="upload-heading">
        <h2 id="upload-heading">Add image</h2>
        <p class="form-help">PNG or JPEG up to 2 MB, 3000 pixels per side and 4 megapixels. Server limits may be lower. JPEG needs EXIF support and must be exported upright. Animated images are not supported. Metadata is removed. Uploads do not publish or change the website.</p>
        @if ($errors->any())<div class="form-error" role="alert">Please check the fields below.</div>@endif
        <form method="POST" action="{{ route('admin.website.media.store', absolute: false) }}" enctype="multipart/form-data">
            @csrf
            <div class="form-field"><label for="media-title">Title</label><input id="media-title" name="title" type="text" maxlength="120" required value="{{ is_string(old('title')) ? old('title') : '' }}" @error('title') aria-invalid="true" aria-describedby="media-title-error" @enderror>@error('title')<p class="field-error" id="media-title-error">{{ $message }}</p>@enderror</div>
            <div class="form-field"><label for="media-image">Image file</label><input id="media-image" name="image" type="file" accept="image/png,image/jpeg" required @error('image') aria-invalid="true" aria-describedby="media-image-error" @enderror>@error('image')<p class="field-error" id="media-image-error">{{ $message }}</p>@enderror</div>
            <div class="form-actions"><button type="submit">Add private image</button></div>
        </form>
    </section>
    <section class="card customer-detail contact-create media-library" aria-labelledby="media-list-heading">
        <h2 id="media-list-heading">Private images</h2>
        <p class="form-help">Only the owner can view these files. Editing, deletion, publishing and selecting an upload in the canvas are not available yet.</p>
        @if ($media->isEmpty())
            <p>No images uploaded.</p>
        @else
            <ul class="media-grid">
                @foreach ($media as $item)
                    <li class="media-card"><img src="{{ route('admin.website.media.file', ['id' => $item->id], absolute: false) }}" alt=""><strong>{{ $item->title }}</strong><span>{{ $item->width }} × {{ $item->height }} · {{ strtoupper($item->mime === 'image/png' ? 'PNG' : 'JPEG') }} · {{ number_format($item->bytes / 1024, 1) }} KB</span></li>
                @endforeach
            </ul>
            {{ $media->links() }}
        @endif
    </section>
</div>
@endsection
