<?php
header('Cache-Control: no-cache');
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
        (new HeaderProducer())->produce();
        ?>
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
        <?php
        (new FooterProducer())->produce();
        ?>
    </div>
</body>

</html>