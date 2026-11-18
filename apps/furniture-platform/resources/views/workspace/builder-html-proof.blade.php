@extends('workspace.layout')
@section('workspace-title', 'Synthetic HTML proof')
@section('workspace-content')
<div class="content list-page">
    <div class="demo-banner"><span>ISOLATED EXPERIMENT</span> Synthetic text only. This is not the storefront or the visual builder.</div>
    <div class="page-heading"><h1>Article text proof</h1></div>
    @if(session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="form-error" role="alert">The changes were not saved. @error('revision'){{ $message }} <a href="{{ route('admin.builder-html-proof.edit', absolute: false) }}">Reload editor</a>.@enderror @error('document'){{ $message }}@enderror @error('section'){{ $message }}@enderror @error('section_id'){{ $message }}@enderror @error('action'){{ $message }}@enderror</div>@endif
    <div class="card customer-detail contact-create">
        <p class="form-help">Fixed synthetic document only. Text is not HTML. <a href="{{ route('builder-html-proof.show', absolute: false) }}">Open synthetic server-rendered HTML</a>.</p>
        <p class="form-help"><strong>Save each text form first.</strong> Add, duplicate, move and remove save immediately and reload the editor, discarding unsaved text in any form. The original section cannot be removed. Maximum ten sections.</p>
        <form method="post" action="{{ route('admin.builder-html-proof.sections', absolute: false) }}">
            @csrf<input type="hidden" name="revision" value="{{ $revision }}"><input type="hidden" name="action" value="add">
            <button type="submit" @disabled(count($sections) >= 10)>Add synthetic article section</button>
        </form>
    </div>
    @foreach($sections as $section)
        @php
            $id = $section['id'];
            $suffix = $id === $anchorId ? '' : '-'.$id;
            $hasOld = old('section_id') === $id;
            $summary = $hasOld && is_string(old('summary')) ? old('summary') : $section['props']['summary'];
            $body = $hasOld && is_string(old('body')) ? old('body') : $section['props']['body'];
        @endphp
        <section class="card customer-detail contact-create" data-proof-section="{{ $id }}">
            <h2>Article section {{ $loop->iteration }}{{ $id === $anchorId ? ' · Required' : '' }}</h2>
            <form method="post" action="{{ route('admin.builder-html-proof.update', absolute: false) }}">
                @csrf
                <input type="hidden" name="revision" value="{{ $hasOld && is_scalar(old('revision')) ? old('revision') : $revision }}">
                <input type="hidden" name="section_id" value="{{ $id }}">
                <div class="form-field"><label for="proof-summary{{ $suffix }}">Summary for section {{ $loop->iteration }}</label><textarea id="proof-summary{{ $suffix }}" name="summary" required maxlength="300" @if($hasOld && $errors->has('summary')) aria-invalid="true" aria-describedby="proof-summary-error{{ $suffix }}" @endif>{{ $summary }}</textarea>@if($hasOld) @error('summary')<p class="field-error" id="proof-summary-error{{ $suffix }}">{{ $message }}</p>@enderror @endif</div>
                <div class="form-field"><label for="proof-body{{ $suffix }}">Article text for section {{ $loop->iteration }}</label><textarea id="proof-body{{ $suffix }}" name="body" required maxlength="12000" rows="8" @if($hasOld && $errors->has('body')) aria-invalid="true" aria-describedby="proof-body-error{{ $suffix }}" @endif>{{ $body }}</textarea>@if($hasOld) @error('body')<p class="field-error" id="proof-body-error{{ $suffix }}">{{ $message }}</p>@enderror @endif</div>
                <div class="form-actions"><button type="submit">Save section {{ $loop->iteration }} text</button></div>
            </form>
            <form method="post" action="{{ route('admin.builder-html-proof.sections', absolute: false) }}" aria-label="Structure controls for section {{ $loop->iteration }}">
                @csrf<input type="hidden" name="revision" value="{{ $revision }}"><input type="hidden" name="section_id" value="{{ $id }}">
                <button type="submit" name="action" value="up" @disabled($loop->first)>Move section {{ $loop->iteration }} up</button>
                <button type="submit" name="action" value="down" @disabled($loop->last)>Move section {{ $loop->iteration }} down</button>
                <button type="submit" name="action" value="duplicate" @disabled(count($sections) >= 10)>Duplicate section {{ $loop->iteration }}</button>
                @if($id !== $anchorId)<button type="submit" name="action" value="remove">Remove section {{ $loop->iteration }}</button>@endif
            </form>
        </section>
    @endforeach
</div>
@endsection
