<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in · WOODEX OS</title>
</head>
<body>
    <main>
        <h1>WOODEX OS</h1>
        <p>Development foundation: the workspace is not ready for use.</p>
        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            @if ($errors->any())
                <div role="alert">Sign-in failed. Check your details and try again.</div>
            @endif
            <p><label for="email">Email</label><br>
                <input id="email" name="email" type="email" autocomplete="username" required maxlength="255" value="{{ old('email') }}">
            </p>
            <p><label for="password">Password</label><br>
                <input id="password" name="password" type="password" autocomplete="current-password" required maxlength="1024">
            </p>
            <button type="submit">Sign in</button>
        </form>
    </main>
</body>
</html>
