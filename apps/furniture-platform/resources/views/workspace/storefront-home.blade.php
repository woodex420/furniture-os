@extends('workspace.layout')

@section('workspace-title', 'Storefront home')

@section('workspace-content')
    @php
        $titleValue = old('hero_title', $content->hero_title ?? 'Design For Your Workspace');
        $subtitleValue = old('hero_subtitle', $content->hero_subtitle ?? 'From concept to installation — design, manufacture, and deliver');
        $revisionValue = old('revision', $content->revision);
        $orderValue = old('hero_order', $content->hero_order);
        $ctaLabelValue = old('hero_cta_label', $content->hero_cta_label ?? 'Shop Now');
        $ctaPathValue = old('hero_cta_path', $content->hero_cta_path ?? '/shop?category=workstations');
        $imageValue = old('hero_image', $content->hero_image);
        $mediaValue = old('hero_media_id', $content->hero_media_id);
        $taglineValue = old('home_tagline', $content->home_tagline ?? 'Unmatched Capabilities. Fresh Products that Matter. Trusted Partnership.');
    @endphp
    <div class="content list-page">
        <div class="demo-banner"><span>STOREFRONT INTEGRATION</span> First homepage slide and tagline only. Full visual editing and other pages are still in progress.</div>
        <div class="page-heading list-heading"><div><div class="eyebrow">WEBSITE STUDIO <span>/</span> HOME</div><h1>Storefront home</h1><p>Edit the first slide and tagline of the actual Reimagin v1 homepage.</p></div></div>
        @if(session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
        <div class="home-editor-grid">
        <section class="card home-canvas" aria-labelledby="home-canvas-heading">
            <h2 id="home-canvas-heading">Storefront canvas <span class="home-canvas-badge">Preview</span></h2>
            <p>Click the first slide’s title, subtitle or button to select it. Scroll inside the canvas to select the tagline beneath it. Use the canvas arrows to reorder the hero text, or change the background using the fields below. Changes are only saved when you update the storefront.</p>
            @if ($canvasUrl)
                <div class="home-canvas-frame" data-home-canvas data-storefront-origin="{{ $canvasUrl }}">
                    <div class="home-canvas-status" role="status" data-canvas-status>Loading storefront canvas…</div>
                    <div class="home-canvas-controls" role="group" aria-label="Canvas viewport width" data-canvas-controls hidden>
                        <span>Viewport:</span>
                        <button type="button" data-canvas-width="fit" aria-pressed="true">Fit</button>
                        <button type="button" data-canvas-width="375" aria-pressed="false">Mobile · 375px</button>
                        <button type="button" data-canvas-width="768" aria-pressed="false">Tablet · 768px</button>
                        <button type="button" data-canvas-width="1280" aria-pressed="false">Desktop · 1280px</button>
                    </div>
                    <div class="home-canvas-scroll" tabindex="0" role="region" aria-label="Scrollable storefront canvas">
                        <iframe title="Reimagin homepage visual preview" data-canvas-frame sandbox="allow-scripts allow-same-origin" referrerpolicy="no-referrer" loading="lazy"></iframe>
                    </div>
                </div>
                <noscript><p>Enable JavaScript to use the canvas. You can still save the fields below.</p></noscript>
            @else
                <p class="home-canvas-unavailable">Canvas not configured. Text editing and version history remain available below.</p>
            @endif
        </section>
        <section class="card customer-detail contact-create" aria-labelledby="home-editor-heading">
            <h2 id="home-editor-heading">Homepage content</h2>
            <p class="form-help">Save the first slide and tagline to the connected storefront preview. Other slides and page sections are unchanged.</p>
            @if ($errors->any())<div class="form-error" role="alert">Please check the fields below. @error('revision'){{ $message }} <a href="{{ route('admin.storefront.home', absolute: false) }}">Reload editor</a>.@enderror @error('source_revision'){{ $message }}@enderror @error('hero_media_id'){{ $message }}@enderror</div>@endif
            <form method="POST" action="{{ route('admin.storefront.home.update', absolute: false) }}">
                @csrf
                <input type="hidden" name="revision" value="{{ is_scalar($revisionValue) ? $revisionValue : '' }}">
                <div class="form-field"><label for="hero-title">First slide title</label><input id="hero-title" name="hero_title" type="text" maxlength="120" required value="{{ is_string($titleValue) ? $titleValue : '' }}" @error('hero_title') aria-invalid="true" aria-describedby="title-error" @enderror>@error('hero_title')<p class="field-error" id="title-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="hero-subtitle">First slide subtitle</label><input id="hero-subtitle" name="hero_subtitle" type="text" maxlength="240" required value="{{ is_string($subtitleValue) ? $subtitleValue : '' }}" @error('hero_subtitle') aria-invalid="true" aria-describedby="subtitle-error" @enderror>@error('hero_subtitle')<p class="field-error" id="subtitle-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="hero-order">First slide text order</label><select id="hero-order" name="hero_order" @error('hero_order') aria-invalid="true" aria-describedby="order-error" @enderror><option value="title_first" @selected($orderValue === 'title_first')>Title, then subtitle</option><option value="subtitle_first" @selected($orderValue === 'subtitle_first')>Subtitle, then title</option></select>@error('hero_order')<p class="field-error" id="order-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="hero-cta-label">First slide button text</label><input id="hero-cta-label" name="hero_cta_label" type="text" maxlength="80" required value="{{ is_string($ctaLabelValue) ? $ctaLabelValue : '' }}" @error('hero_cta_label') aria-invalid="true" aria-describedby="cta-label-error" @enderror>@error('hero_cta_label')<p class="field-error" id="cta-label-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="hero-cta-path">First slide button destination</label><input id="hero-cta-path" name="hero_cta_path" type="text" maxlength="240" required value="{{ is_string($ctaPathValue) ? $ctaPathValue : '' }}" aria-describedby="cta-path-help @error('hero_cta_path') cta-path-error @enderror" @error('hero_cta_path') aria-invalid="true" @enderror><p id="cta-path-help" class="form-help">Internal paths only, for example /contact or /shop?category=workstations. No spaces, encoded characters or external links.</p>@error('hero_cta_path')<p class="field-error" id="cta-path-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="hero-image">First slide background</label><select id="hero-image" name="hero_image" @error('hero_image') aria-invalid="true" aria-describedby="image-error" @enderror>@foreach ($imageChoices as $key => $label)<option value="{{ $key }}" @selected($imageValue === $key)>{{ $label }}</option>@endforeach</select><p class="form-help">Used when no uploaded image is selected below.</p>@error('hero_image')<p class="field-error" id="image-error">{{ $message }}</p>@enderror</div>
                @if (config('furniture.media_library_enabled'))
                    <div class="form-field"><label for="hero-media">Uploaded background</label><select id="hero-media" name="hero_media_id" @error('hero_media_id') aria-invalid="true" aria-describedby="hero-media-error" @enderror><option value="" @selected($mediaValue === null || $mediaValue === '')>Use bundled background</option>@foreach ($mediaChoices as $item)<option value="{{ $item->id }}" @selected($mediaValue === $item->id)>{{ $item->title }} · {{ $item->id }}</option>@endforeach</select><p class="form-help">The latest 100 uploads are listed, plus the current selection. <a href="{{ route('admin.website.media', absolute: false) }}">Upload an image</a>. Selecting an image here is a draft; saving or restoring a version that uses it makes that image publicly accessible on the connected storefront preview.</p>@error('hero_media_id')<p class="field-error" id="hero-media-error">{{ $message }}</p>@enderror</div>
                @elseif ($content->hero_media_id)
                    <p class="form-help">An uploaded background is selected. Media selection is disabled; saving other fields preserves it.</p>
                @endif
                <div class="form-field"><label for="home-tagline">Homepage tagline</label><input id="home-tagline" name="home_tagline" type="text" maxlength="240" required value="{{ is_string($taglineValue) ? $taglineValue : '' }}" @error('home_tagline') aria-invalid="true" aria-describedby="tagline-error" @enderror><p class="form-help">The text immediately below the hero.</p>@error('home_tagline')<p class="field-error" id="tagline-error">{{ $message }}</p>@enderror</div>
                <div class="form-actions"><button type="submit">Update storefront preview</button></div>
            </form>
        </section>
        </div>
        <section class="card customer-detail contact-create" aria-labelledby="home-history-heading">
            <h2 id="home-history-heading">Recent versions</h2>
            <p class="form-help">The latest 20 versions of the first slide and tagline are shown. Restoring creates a new version and updates the preview immediately.</p>
            @if ($versions->isEmpty())
                <p>No versions available.</p>
            @else
                <ol>
                    @foreach ($versions as $version)
                        <li style="padding: 12px 0; border-bottom: 1px solid #e2e8e4;">
                            <strong>Version {{ $version->revision }}</strong>
                            @if ((int) $version->revision === (int) $content->revision) <span>· Current</span> @endif
                            <span>· {{ $version->change_type }} · {{ $version->created_at }} UTC</span>
                            <p>{{ $version->hero_title ?? 'Default title' }} — {{ $version->hero_subtitle ?? 'Default subtitle' }}</p>
                            <p>Order: {{ $version->hero_order === 'subtitle_first' ? 'Subtitle, then title' : 'Title, then subtitle' }}</p>
                            <p>Button: {{ $version->hero_cta_label ?? 'Shop Now' }} → {{ $version->hero_cta_path ?? '/shop?category=workstations' }}</p>
                            <p>Background: {{ $version->hero_media_id ? ($version->media_title ?? 'Unavailable upload') . ' · uploaded image' : $imageChoices[$version->hero_image] . ' · bundled image' }}</p>
                            <p>Tagline: {{ $version->home_tagline ?? 'Unmatched Capabilities. Fresh Products that Matter. Trusted Partnership.' }}</p>
                            @if ((int) $version->revision !== (int) $content->revision)
                                <form method="POST" action="{{ route('admin.storefront.home.restore', absolute: false) }}">
                                    @csrf
                                    <input type="hidden" name="revision" value="{{ $content->revision }}">
                                    <input type="hidden" name="source_revision" value="{{ $version->revision }}">
                                    <div class="form-actions"><button type="submit">Restore version {{ $version->revision }}</button></div>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
@endsection
