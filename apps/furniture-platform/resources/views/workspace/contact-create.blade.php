@extends('workspace.layout')

@section('workspace-title', 'Add contact')

@section('workspace-content')
    @php
        $oldKey = old('request_key');
        $formKey = is_string($oldKey) && \Illuminate\Support\Str::isUuid($oldKey) ? $oldKey : $requestKey;
    @endphp
    <div class="content list-page">
        <div class="demo-banner"><span>DEVELOPMENT WORKFLOW</span> Contact creation is experimental; editing and imports are not available yet.</div>
        <div class="page-heading list-heading"><div><div class="eyebrow">WORKSPACE <span>/</span> CUSTOMERS <span>/</span> CONTACTS</div><h1>Add contact</h1><p>Save a person to your Furniture workspace.</p></div></div>
        <section class="card customer-detail contact-create" aria-labelledby="contact-form-heading">
            <a class="customer-back" href="{{ route('admin.contacts', absolute: false) }}">← Back to contacts</a>
            <h2 id="contact-form-heading">Contact information</h2>
            <p class="form-help">Name is required. An email can only belong to one contact. Contacts without an email are not checked for duplicates.</p>
            @if ($errors->any())
                <div class="form-error" role="alert">Please check the information below. If a request key was already used for different details, <a href="{{ route('admin.contacts.create', absolute: false) }}">start a new form</a>.</div>
            @endif
            <form method="POST" action="{{ route('admin.contacts.store', absolute: false) }}">
                @csrf
                <input type="hidden" name="request_key" value="{{ $formKey }}">
                @foreach (['name' => ['Full name', 'text', 255], 'email' => ['Email address', 'email', 255], 'phone' => ['Phone number', 'tel', 50]] as $field => [$label, $type, $max])
                    @php($oldValue = old($field))
                    <div class="form-field">
                        <label for="contact-{{ $field }}">{{ $label }} @if ($field === 'name')<span aria-hidden="true">*</span>@else<small>Optional</small>@endif</label>
                        <input id="contact-{{ $field }}" name="{{ $field }}" type="{{ $type }}" maxlength="{{ $max }}" value="{{ is_string($oldValue) ? $oldValue : '' }}" @if ($field === 'name') required @endif @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                        @error($field)<p class="field-error" id="{{ $field }}-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach
                @error('request_key')<p class="field-error">{{ $message }}</p>@enderror
                <div class="form-actions"><button type="submit">Save contact</button><a href="{{ route('admin.contacts', absolute: false) }}">Cancel</a></div>
            </form>
        </section>
    </div>
@endsection
