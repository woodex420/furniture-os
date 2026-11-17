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
                @if (config('furniture.admin_preview_landing'))
                    <div class="auth-notice" aria-label="Preview sign-in help"><span aria-hidden="true"></span><div><strong>Synthetic preview sign-in</strong><p>Use the preview-only account. If sign-in does not continue inside Arena, open this page in a new tab and sign in there.</p><p><a href="{{ route('login', absolute: false) }}" target="_blank" rel="noopener noreferrer">Open sign-in in a new tab</a></p><p id="preview-cookie-warning" role="status" hidden>Cookies appear blocked in this frame. Open sign-in in a new tab and try again.</p><p id="preview-session-status" role="status">Checking preview session…</p></div></div>
                @endif
                <form method="POST" action="{{ route('login.store', absolute: false) }}">
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
    @if (config('furniture.admin_preview_landing'))
        <script>
            // Diagnostic only: never bypass authentication, sessions or CSRF. Cookie
            // policies can vary by browser and the containing site, so this is not proof
            // that the session works. Direct-tab sign-in remains available regardless.
            (() => {
                if (!window.isSecureContext) return;
                const name = '__Host-furniture_preview_probe';
                try {
                    document.cookie = name + '=1; Path=/; Secure; SameSite=None; Partitioned';
                    const present = document.cookie.split(';').some((part) => part.trim() === name + '=1');
                    document.cookie = name + '=; Path=/; Secure; SameSite=None; Partitioned; Max-Age=0';
                    if (!present) document.getElementById('preview-cookie-warning').hidden = false;
                } catch (_) {
                    document.getElementById('preview-cookie-warning').hidden = false;
                }
            })();
            // Check the actual Laravel session round-trip, not just JavaScript cookies.
            // The form token is sent only to the same-origin diagnostic and is never echoed.
            (() => {
                const status = document.getElementById('preview-session-status');
                const token = document.querySelector('input[name="_token"]')?.value;
                const controller = new AbortController();
                const timeout = setTimeout(() => controller.abort(), 8000);
                if (!token) {
                    status.textContent = 'Preview session check unavailable. Open sign-in in a new tab.';
                    clearTimeout(timeout);
                    return;
                }
                fetch('/login/preview-session', { credentials: 'same-origin', cache: 'no-store', signal: controller.signal, headers: { 'X-Preview-Session-Token': token } })
                    .then((response) => { if (!response.ok) throw new Error('Session check failed'); return response.json(); })
                    .then((result) => {
                        status.textContent = result.available === true
                            ? 'Preview session is available. Enter the preview account and select Sign in.'
                            : 'Preview session was not retained. Open sign-in in a new tab and try again.';
                    })
                    .catch(() => { status.textContent = 'Could not check the preview session. Open sign-in in a new tab and try again.'; })
                    .finally(() => clearTimeout(timeout));
            })();
        </script>
    @endif
</body>
</html>
