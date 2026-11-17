@extends('workspace.layout')

@section('workspace-title', 'Add lead')

@section('workspace-content')
    @php
        $oldKey = old('request_key');
        $formKey = is_string($oldKey) && \Illuminate\Support\Str::isUuid($oldKey) ? $oldKey : $requestKey;
        $oldTitle = old('title');
        $oldSource = old('source');
        $oldContact = old('contact_id');
    @endphp
    <div class="content list-page">
        <div class="demo-banner"><span>DEVELOPMENT WORKFLOW</span> Lead creation is experimental; assignment and editing are not available yet.</div>
        <div class="page-heading list-heading"><div><div class="eyebrow">WORKSPACE <span>/</span> CUSTOMERS <span>/</span> LEADS</div><h1>Add lead</h1><p>Record a new enquiry in your Furniture workspace.</p></div></div>
        <section class="card customer-detail contact-create" aria-labelledby="lead-form-heading">
            <a class="customer-back" href="{{ route('admin.leads', absolute: false) }}">← Back to leads</a>
            <h2 id="lead-form-heading">Lead information</h2>
            <p class="form-help">A title is required. New leads start with the status “new”. Duplicate leads are not automatically merged.</p>
            @if ($errors->any())
                <div class="form-error" role="alert">Please check the information below. If a request key was already used for different details, <a href="{{ route('admin.leads.create', absolute: false) }}">start a new form</a>.</div>
            @endif
            <form method="POST" action="{{ route('admin.leads.store', absolute: false) }}">
                @csrf
                <input type="hidden" name="request_key" value="{{ $formKey }}">
                <div class="form-field"><label for="lead-title">Lead title <span aria-hidden="true">*</span></label><input id="lead-title" name="title" type="text" maxlength="255" required value="{{ is_string($oldTitle) ? $oldTitle : '' }}" @error('title') aria-invalid="true" aria-describedby="title-error" @enderror>@error('title')<p class="field-error" id="title-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="lead-source">Source <small>Optional</small></label><input id="lead-source" name="source" type="text" maxlength="100" value="{{ is_string($oldSource) ? $oldSource : '' }}" placeholder="For example, website or referral" @error('source') aria-invalid="true" aria-describedby="source-error" @enderror>@error('source')<p class="field-error" id="source-error">{{ $message }}</p>@enderror</div>
                @if ($canViewContacts)
                    <div class="form-field"><label for="lead-contact">Contact <small>Optional</small></label><select id="lead-contact" name="contact_id" aria-describedby="contact-help @error('contact_id') contact_id-error @enderror" @error('contact_id') aria-invalid="true" @enderror><option value="">No contact</option>@foreach($contacts as $contact)<option value="{{ $contact->id }}" @selected(is_scalar($oldContact) && (string) $oldContact === (string) $contact->id)>{{ $contact->name }}</option>@endforeach</select><p class="form-help" id="contact-help">Showing up to 100 most recently added contacts. Contact search is not available in this form yet.</p>@error('contact_id')<p class="field-error" id="contact_id-error">{{ $message }}</p>@enderror</div>
                @endif
                @error('request_key')<p class="field-error">{{ $message }}</p>@enderror
                <div class="form-actions"><button type="submit">Save lead</button><a href="{{ route('admin.leads', absolute: false) }}">Cancel</a></div>
            </form>
        </section>
    </div>
@endsection
