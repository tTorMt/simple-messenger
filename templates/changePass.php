<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="/styles/auth.css" rel="stylesheet">
    <script src="/scripts/client.js" async></script>
    <script src="/scripts/changePass.js" defer></script>
    <title>Simple messenger</title>
</head>
<body>
<div id="app">
    <header>

    </header>
    <main>
        <h1>Change password</h1>
        <p id="error-field" hidden>Error</p>
        <form>
            <label for="userPassword">Password</label>
            <input type="password" id="userPassword" name="userPassword" required>
            <label for="password-retype" >Retype password</label>
            <input type="password" id="password-retype" required>
            <input type="hidden" id="pass-token" value="<?php echo $_GET['changePassToken']; ?>">
            <button id="auth">Change Password</button>
        </form>
        <p id="rules">The username may contain only letters (a-Z), numbers, underscores (_), or apostrophes (').
            The password must contain letters (a-Z), numbers, and special characters (!@$%&_).</p>
    </main>
</div>
</body>
</html>