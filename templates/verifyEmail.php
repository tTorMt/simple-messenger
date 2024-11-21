<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="/styles/auth.css" rel="stylesheet">
    <script src="/scripts/client.js" async></script>
    <script src="/scripts/verifyEmail.js" defer></script>
    <title>Simple messenger</title>
</head>
<body>
<div id="app">
    <header>

    </header>
    <main>
        <h1>Verify email</h1>
        <p id="error-field" hidden>Error</p>
        <form>
            <input type="hidden" id="email-change-token" value="<?php echo $_GET['emailVerificationToken']; ?>">
            <button id="auth">Verify</button>
        </form>
    </main>
</div>
</body>
</html>