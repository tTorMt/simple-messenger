<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="/styles/auth.css" rel="stylesheet">
    <script src="/scripts/client.js" async></script>
    <script src="/scripts/auth.js" defer></script>
    <title>Simple messenger</title>
</head>
<body>
<div id="app">
    <header>

    </header>
    <main>
        <h1>Sign In</h1>
        <p id="error-field" hidden>Error</p>
        <form>
            <label for="userName">User name</label>
            <input type="text" id="userName" name="userName" required>
            <label for="userPassword">Password</label>
            <input type="password" id="userPassword" name="userPassword" required>
            <label for="password-retype" hidden>Retype password</label>
            <input type="password" id="password-retype" hidden>
            <button id="auth">Sign in</button>
            <a href="" id="sign">Sign up</a>
        </form>
        <p id="rules" hidden>The username may contain only letters (a-Z), numbers, underscores (_), or apostrophes (').
            The password must contain letters (a-Z), numbers, and special characters (!@$%&_).</p>
    </main>
</div>
</body>
</html>