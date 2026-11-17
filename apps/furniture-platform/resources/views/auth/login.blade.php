<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in · WOODEX OS</title>
    @vite('resources/css/auth.css')
</head>
<body>
    <a class="skip-link" href="#sign-in">Skip to sign in</a>
    <div class="auth-shell">
        <aside class="auth-intro" aria-label="About WOODEX OS">
            <div class="auth-brand"><span class="brand-mark" aria-hidden="true">W</span><div><strong>WOODEX <span>OS</span></strong><small>THE OPERATING WORKSPACE</small></div></div>
            <div class="intro-content">
                <div class="intro-kicker"><span></span> YOUR WORKSPACE, IN ONE PLACE</div>
                <h1>Made for the way <em>you work.</em></h1>
                <p>Bring the details that matter together in one focused workspace.</p>
                <div class="intro-card"><span class="intro-card-icon" aria-hidden="true">W</span><div><strong>Woodex Furniture</strong><small>Operating workspace</small></div><span class="intro-status">IN PROGRESS</span></div>
            </div>
            <p class="intro-footer">WOODEX OS <span>Built for better work</span></p>
        </aside>
        <main class="auth-main" id="sign-in">
            <div class="auth-mobile-brand"><span class="brand-mark" aria-hidden="true">W</span><strong>WOODEX <span>OS</span></strong></div>
            <div class="auth-card">
                <div class="auth-kicker"><span></span> THE OPERATING WORKSPACE</div>
                <h2>Welcome back<span>.</span></h2>
                <p class="auth-description">Sign in to your WOODEX OS workspace.</p>
                <div class="auth-notice"><span aria-hidden="true"></span><div><strong>Workspace in progress</strong><p>This development foundation is not ready for business use.</p></div></div>
                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    @if ($errors->any())
                        <div class="auth-error" id="sign-in-error" role="alert">Sign-in failed. Check your details and try again.</div>
                    @endif
                    <div class="form-field"><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="username" required maxlength="255" placeholder="you@example.com" value="{{ is_string(old('email')) ? old('email') : '' }}" @if($errors->any()) aria-describedby="sign-in-error" @endif></div>
                    <div class="form-field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required maxlength="1024" placeholder="Enter your password" @if($errors->any()) aria-describedby="sign-in-error" @endif></div>
                    <button class="sign-in-button" type="submit">Sign in <span aria-hidden="true">→</span></button>
                </form>
                <p class="auth-help">Account access and recovery are being developed.</p>
            </div>
            <p class="auth-main-footer">WOODEX OS <span>·</span> THE OPERATING WORKSPACE</p>
        </main>
    </div>
</body>
</html>
