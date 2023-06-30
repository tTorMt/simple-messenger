<?php
header('Cache-Control: no-cache');
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Простой чат</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="images/favicon.png">
    <script src="script.js" defer></script>
</head>

<body>
    <div id="main-container">
        <header>
            <div class="logo">
            <a href="/"><img src="images/logo.png" alt="logo"></a>
            </div>
            <h1>Simple Chat</h1>
        </header>
        <main>
            <div class="content">
                <h1>Вход</h1>
                <form method="post" action="auth.php">
                    <label for="name">Логин: </label>
                    <input type="text" id="name" name="name">
                    <label for="pass">Пароль:</label>
                    <input type="password" id="pass" name="pass">
                    <input type="submit" value="Войти">
                </form>
                <p>Или <a href="registr.php">Зарегестрироваться</a></p>
            </div>
        </main>
        <footer>
            @ Автор приложения tTorMt. Мой email ***@***.com.
        </footer>
    </div>
</body>

</html>