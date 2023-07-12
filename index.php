<?php
declare(strict_types=1);
session_start();
if (isset($_GET['exit'])) {
    $_SESSION = array();
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
    <title>Простой чат</title>
    <link rel="stylesheet" href="styles/base-style.css">
    <link rel="stylesheet" href="styles/chat.css">
    <link rel="icon" href="images/favicon.png">
    <script src="script.js" defer></script>
</head>

<body>
    <div id="main-container">
        <?php
        if (isset($_SESSION['user'])) {
            $chatState = HeaderProducer::HEADER_CHAT;
            $userName = strip_tags($_SESSION['user']);
        } else {
            $chatState = HeaderProducer::HEADER_MAIN;
        }
        (new HeaderProducer($chatState, $userName ?? null))->show();
        ?>
        <main>
            <div class="content">
                <?php
                switch ($chatState) {
                    case HeaderProducer::HEADER_MAIN :
                ?>
                <div class="hello">
                    <h1>Simple Chat</h1>
                    <p>Простое приложение для чата.
                        <a href="auth.php">Войдите</a> или <a href="registr.php">зарегестрируйтесь</a> для начала использования.</p>
                </div>
                <?php break; 
                    case HeaderProducer::HEADER_CHAT :
                ?>
                <div class="chat">
                    <div class="message left">
                        <h4>Someone</h4>
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                    </div>
                    <div class="message left">
                        Lorem ipsum dolor sit amet,
                    </div>
                    <div class="message right">
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
                <?php break; 
                default :
                echo "Приложение не доступно. Попробуйте позже";
                throw new RuntimeException('Illigal chat state');
                exit;
                }
                ?>
            </div>
        </main>
        <?php
        (new FooterProducer())->show();
        ?>
    </div>
</body>

</html>