@extends('workspace.layout')

@section('workspace-title', 'Storefront home')

@section('workspace-content')
    @php
        $titleValue = old('hero_title', $content->hero_title ?? 'Design For Your Workspace');
        $subtitleValue = old('hero_subtitle', $content->hero_subtitle ?? 'From concept to installation — design, manufacture, and deliver');
        $revisionValue = old('revision', $content->revision);
    @endphp
    <div class="content list-page">
        <div class="demo-banner"><span>STOREFRONT INTEGRATION</span> First homepage slide only. Full visual editing and other pages are still in progress.</div>
        <div class="page-heading list-heading"><div><div class="eyebrow">WEBSITE STUDIO <span>/</span> HOME</div><h1>Storefront home</h1><p>Edit the first slide of the actual Reimagin v1 homepage.</p></div></div>
        @if(session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
        <section class="card customer-detail contact-create" aria-labelledby="home-editor-heading">
            <h2 id="home-editor-heading">Homepage hero</h2>
            <p class="form-help">Saving makes these two fields available to the connected storefront preview immediately. Other slides, images and layout are unchanged.</p>
            @if ($errors->any())<div class="form-error" role="alert">Please check the fields below. @error('revision'){{ $message }} <a href="{{ route('admin.storefront.home', absolute: false) }}">Reload editor</a>.@enderror</div>@endif
            <form method="POST" action="{{ route('admin.storefront.home.update', absolute: false) }}">
                @csrf
                <input type="hidden" name="revision" value="{{ is_scalar($revisionValue) ? $revisionValue : '' }}">
                <div class="form-field"><label for="hero-title">First slide title</label><input id="hero-title" name="hero_title" type="text" maxlength="120" required value="{{ is_string($titleValue) ? $titleValue : '' }}" @error('hero_title') aria-invalid="true" aria-describedby="title-error" @enderror>@error('hero_title')<p class="field-error" id="title-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="hero-subtitle">First slide subtitle</label><input id="hero-subtitle" name="hero_subtitle" type="text" maxlength="240" required value="{{ is_string($subtitleValue) ? $subtitleValue : '' }}" @error('hero_subtitle') aria-invalid="true" aria-describedby="subtitle-error" @enderror>@error('hero_subtitle')<p class="field-error" id="subtitle-error">{{ $message }}</p>@enderror</div>
                <div class="form-actions"><button type="submit">Update storefront preview</button></div>
            </form>
        </section>
    </div>
@endsection
