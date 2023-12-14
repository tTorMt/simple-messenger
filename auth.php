<?php

declare(strict_types=1);
if (isset($_POST['name']) && isset($_POST['pass'])) {
    session_start();
    require_once('includes/utils.php');
    require_once('includes/authentificator.php');
    require_once('includes/dbstorage.php');
    
    $authenticator = new Authenticator($_POST['name'], $_POST['pass'], new DBStorage());
    if ($authenticator->authUser())
        header('Location: /');
}
include('includes/header.php');
include('includes/footer.php');
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Простой мессенджер</title>
    <link rel="stylesheet" href="styles/base-style.css">
    <link rel="stylesheet" href="styles/auth.css">
    <link rel="icon" href="images/favicon.png">
    <script src="scripts/script.js" defer></script>
</head>

<body>
    <div id="main-container">
        <?php
        (new HeaderProducer(HeaderProducer::HEADER_AUTH))->show();
        ?>
        <main>
            <div class="content">
                <h1>Вход</h1>
                <?php
                if (isset($authenticator))
                    $authenticator->showError();
                ?>
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
        <?php
        (new FooterProducer())->show();
        ?>
    </div>
</body>

</html>