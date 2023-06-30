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
                <h1>Регистрация</h1>
                <form method="post" action="auth.php">
                    <label for="name">Логин: </label>
                    <div>
                        <input type="text" id="name" name="name">
                        <span class="input-error login">Логин уже занят</span>
                    </div>
                    <label for="pass">Пароль:</label>
                    <div>
                        <input type="password" id="pass" name="pass">
                        <span class="input-error pass">Пароли не совпадают</span>
                    </div>
                    <label for="repeat">Повторите пароль:</label>
                    <input type="password" id="repeat">
                    <input type="submit" value="Регистрация">
                </form>
                <p>Или <a href="auth.php">Войти</a></p>
            </div>
        </main>
        <footer>
            @ Автор приложения tTorMt. Мой email ***@***.com.
        </footer>
    </div>
</body>

</html>