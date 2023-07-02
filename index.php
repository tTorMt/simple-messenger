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
                    <a href="?exit">
                        <li>Выход</li>
                    </a>
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
                <div class="chat">
                    <div class="message left">
                        <h4>Someone</h4>
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                    </div>
                    <div class="message left">
                        Lorem ipsum dolor sit amet,
                    </div>
                    <div class="message right">
                        <h4>Me</h4>
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                    </div>
                    <div class="message right">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
                    </div>
                    <div class="message left">
                        <h4>Someone</h4>
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
                    </div>
                    <div class="message left">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
                    </div>
                </div>
                <div class="send-message">
                    <input type="text" name="user-message" id="user-message" placeholder="Ваше сообщение">
                    <button id="send">-></button>
                </div>
            </div>
        </main>
        <footer>
            @ Автор приложения tTorMt. Мой email ***@***.com.
        </footer>
    </div>
</body>

</html>