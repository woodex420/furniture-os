@extends('workspace.layout')

@section('workspace-title', 'Edit lead')

@section('workspace-content')
    @php
        $formTitle = is_array($formInput) ? (is_string($formInput['title'] ?? null) ? $formInput['title'] : '') : $lead->title;
        $formSource = is_array($formInput) ? (is_string($formInput['source'] ?? null) ? $formInput['source'] : '') : ($lead->source ?? '');
        $submittedRevision = $formInput['edit_revision'] ?? null;
        $revision = is_array($formInput) ? (is_scalar($submittedRevision) && ctype_digit((string) $submittedRevision) ? (string) $submittedRevision : '') : (string) $lead->edit_revision;
    @endphp
    <div class="content list-page">
        <div class="demo-banner"><span>DEVELOPMENT WORKFLOW</span> Only the title and source can be edited. Assignment and follow-ups remain pending.</div>
        <div class="page-heading list-heading"><div><div class="eyebrow">WORKSPACE <span>/</span> CUSTOMERS <span>/</span> LEADS</div><h1>Edit lead</h1><p>Update lead information.</p></div></div>
        <section class="card customer-detail contact-create" aria-labelledby="lead-edit-heading">
            <a class="customer-back" href="{{ route('admin.leads.show', $lead->id, false) }}">← Back to lead</a>
            <h2 id="lead-edit-heading">Lead information</h2>
            @if ($leadEditErrors->any())<div class="form-error" role="alert">Please check the information below.</div>@endif
            @if ($leadEditErrors->has('edit_revision'))<p class="field-error" role="alert">{{ $leadEditErrors->first('edit_revision') }} <a href="{{ route('admin.leads.edit', $lead->id, false) }}">Reload current lead</a></p>@endif
            <form method="POST" action="{{ route('admin.leads.update', $lead->id, false) }}">
                @csrf
                <input type="hidden" name="edit_revision" value="{{ $revision }}">
                <div class="form-field"><label for="lead-title">Lead title <span aria-hidden="true">*</span></label><input id="lead-title" name="title" type="text" maxlength="255" required value="{{ $formTitle }}" @if($leadEditErrors->has('title')) aria-invalid="true" aria-describedby="title-error" @endif>@if($leadEditErrors->has('title'))<p class="field-error" id="title-error">{{ $leadEditErrors->first('title') }}</p>@endif</div>
                <div class="form-field"><label for="lead-source">Source <small>Optional</small></label><input id="lead-source" name="source" type="text" maxlength="100" value="{{ $formSource }}" @if($leadEditErrors->has('source')) aria-invalid="true" aria-describedby="source-error" @endif>@if($leadEditErrors->has('source'))<p class="field-error" id="source-error">{{ $leadEditErrors->first('source') }}</p>@endif</div>
                <div class="form-actions"><button type="submit">Save changes</button><a href="{{ route('admin.leads.show', $lead->id, false) }}">Cancel</a></div>
            </form>
        </section>
    </div>
@endsection
