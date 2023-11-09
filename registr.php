<?php

declare(strict_types=1);

if ((isset($_POST['name']) && isset($_POST['pass'])) || isset($_GET['isVacant'])) {
    session_start();
    require_once('includes/authentificator.php');
    require_once('includes/dbstorage.php');
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $authenticator = new Authenticator($_POST['name'], $_POST['pass'], new DBStorage());
        if ($authenticator->regUser()) {
            header('Location: auth.php');
            exit;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $authenticator = new Authenticator($_GET['isVacant'], '', new DBStorage());
        header('Content-Type: application/json');
        echo $authenticator->nameVacantJSON();
        exit;
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
    <script src="scripts/script.js" defer></script>
    <script src="scripts/reg.js" defer></script>
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
                if (isset($authenticator))
                    $authenticator->showError();
                ?>
                <form method="post" action="registr.php">
                    <label for="name"><b>Логин:</b> </label>
                    <label for="name">Логин должен содержать только латинские буквы.</label>
                    <div>
                        <input type="text" id="name" name="name" required>
                        <span class="input-error login">Логин уже занят</span>
                    </div>
                    <label for="pass"><b>Пароль:</b></label>
                    <label for="pass">Пароль должен содержать большие и маленькие латинские буквы и хотябы один из символов !@$.%&.</label>
                    <div>
                        <input type="password" id="pass" name="pass" required>
                        <span class="input-error pass">Пароль не соответствует требованиям</span>
                    </div>
                    <label for="repeat">Повторите пароль:</label>
                    <div>
                        <input type="password" id="repeat" required>
                        <span class="input-error repeat">Пароли не совпадают</span>
                    </div>
                    <input type="submit" id="submit" value="Регистрация" disabled="true">
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