<?php

declare(strict_types=1);
if (isset($_POST['name']) && isset($_POST['pass'])) {
    include('includes/utils.php');
    $name = inputUtils::nameTrim($_POST['name']);
    $pass = $_POST['pass'];
    session_start();
    //To Do implement database storage. Session storage now for testing.
    if (!isset($_SESSION[$name])) {
        if (inputUtils::nameCheck($name) && inputUtils::passCheck($pass)) {
            $_SESSION[$name] =  password_hash($pass, PASSWORD_DEFAULT);
            header('Location: auth.php');
        } else {
            $regErr = "<p class=\"input-error\">Логин и/или пароль не соответствуют требованиям. Повторите ввод.</p>";
        }
    } else {
        $regErr = "<p class=\"input-error\">Такой логин занят. Повторите ввод.</p>";
    }
}
include('includes/header.php');
include('includes/footer.php');
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Простой чат</title>
    <link rel="stylesheet" href="styles/base-style.css">
    <link rel="stylesheet" href="styles/auth.css">
    <link rel="icon" href="images/favicon.png">
    <script src="script.js" defer></script>
</head>

<body>
    <div id="main-container">
        <?php
        (new HeaderProducer(HeaderProducer::HEADER_AUTH))->show();
        ?>
        <main>
            <div class="content">
                <h1>Регистрация</h1>
                <?php
                if (isset($regErr))
                    echo $regErr;
                ?>
                <form method="post" action="registr.php">
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
        <?php
        (new FooterProducer())->show();
        ?>
    </div>
</body>
</html>