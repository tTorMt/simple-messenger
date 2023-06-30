<?php
header('Cache-Control: no-cache');
if (isset($_GET['exit']))
    header('Location: /');
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
            <div class="user-info">
                <h4>Привет, User!</h4>
            </div>
            <div class="login">
                <ul>
                    <a href="auth.php">
                        <li>Войти</li>
                    </a>
                    <a href="registr.php">
                        <li>Регистрация</li>
                    </a>
                    <a href="?exit"><li>Выход</li></a>
                </ul>
            </div>
        </header>
        <main>
            <div class="content">
                <div class="hello">
                    <h1>Simple Chat</h1>
                    <p>Простое приложение для чата.
                        Войдите или зарегестрируйтесь для начала использования</p>
                </div>
            </div>
        </main>
        <footer>
            @ Автор приложения tTorMt. Мой email ***@***.com.
        </footer>
    </div>
</body>

</html>