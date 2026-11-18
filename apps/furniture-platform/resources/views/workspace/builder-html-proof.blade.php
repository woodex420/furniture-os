@extends('workspace.layout')
@section('workspace-title', 'Synthetic HTML proof')
@section('workspace-content')
<div class="content list-page">
    <div class="demo-banner"><span>ISOLATED EXPERIMENT</span> Synthetic text only. This is not the storefront or the visual builder.</div>
    <div class="page-heading"><h1>Article text proof</h1></div>
    @if(session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="form-error" role="alert">The changes were not saved. @error('revision'){{ $message }} <a href="{{ route('admin.builder-html-proof.edit', absolute: false) }}">Reload editor</a>.@enderror @error('document'){{ $message }}@enderror</div>@endif
    <section class="card customer-detail contact-create">
        <p class="form-help">Editing the fixed, pre-created test document only. Text is not HTML. <a href="{{ route('builder-html-proof.show', absolute: false) }}">Open synthetic server-rendered HTML</a>.</p>
        <form method="post" action="{{ route('admin.builder-html-proof.update', absolute: false) }}">
            @csrf
            <input type="hidden" name="revision" value="{{ is_scalar(old('revision')) ? old('revision') : $revision }}">
            <div class="form-field"><label for="proof-summary">Summary</label><textarea id="proof-summary" name="summary" required maxlength="300" @error('summary') aria-invalid="true" aria-describedby="proof-summary-error" @enderror>{{ is_string(old('summary', $props['summary'])) ? old('summary', $props['summary']) : '' }}</textarea>@error('summary')<p class="field-error" id="proof-summary-error">{{ $message }}</p>@enderror</div>
            <div class="form-field"><label for="proof-body">Article text</label><textarea id="proof-body" name="body" required maxlength="12000" rows="12" @error('body') aria-invalid="true" aria-describedby="proof-body-error" @enderror>{{ is_string(old('body', $props['body'])) ? old('body', $props['body']) : '' }}</textarea>@error('body')<p class="field-error" id="proof-body-error">{{ $message }}</p>@enderror</div>
            <div class="form-actions"><button type="submit">Save synthetic text</button></div>
        </form>
    </section>
</div>
@endsection
