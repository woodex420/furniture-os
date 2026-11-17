@extends('workspace.layout')

@section('workspace-title', 'Account security')

@section('workspace-content')
    <div class="content list-page">
        @if(session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
        <div class="page-heading list-heading"><div><div class="eyebrow">WORKSPACE <span>/</span> ACCOUNT</div><h1>Change password</h1><p>Protect your Furniture owner account.</p></div></div>
        <section class="card customer-detail contact-create" aria-labelledby="password-heading">
            <a class="customer-back" href="{{ route('admin', absolute: false) }}">← Back to dashboard</a>
            <h2 id="password-heading">Owner password</h2>
            <p class="form-help">Use a unique password of at least 12 characters and no more than 72 bytes. Other sessions that have used the updated application are signed out on their next request.</p>
            @if ($errors->any())<div class="form-error" role="alert">Please check the information below.</div>@endif
            <form method="POST" action="{{ route('admin.owner.password.update', absolute: false) }}">
                @csrf
                <div class="form-field"><label for="current-password">Current password</label><input id="current-password" name="current_password" type="password" autocomplete="current-password" required @error('current_password') aria-invalid="true" aria-describedby="current-password-error" @enderror>@error('current_password')<p class="field-error" id="current-password-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="new-password">New password</label><input id="new-password" name="password" type="password" autocomplete="new-password" minlength="12" required @error('password') aria-invalid="true" aria-describedby="new-password-error" @enderror>@error('password')<p class="field-error" id="new-password-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="password-confirmation">Confirm new password</label><input id="password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required></div>
                <div class="form-actions"><button type="submit">Change password</button><a href="{{ route('admin', absolute: false) }}">Cancel</a></div>
            </form>
        </section>
    </div>
@endsection
