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
        <?php
        (new FooterProducer())->produce();
        ?>
    </div>
</body>

</html>