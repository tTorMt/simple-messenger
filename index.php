<?php

declare(strict_types=1);

conversation();
userSearch();
exitSession();
getMessagesJSON();
sendMessage();

require_once('includes/header.php');
require_once('includes/footer.php');
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Простой мессенджер</title>
    <link rel="stylesheet" href="styles/base-style.css">
    <link rel="stylesheet" href="styles/chat.css">
    <link rel="icon" href="images/favicon.png">
    <script src="scripts/script.js" defer></script>
</head>

<body>
    <div id="main-container">
        <?php
        session_start();
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
                    case HeaderProducer::HEADER_MAIN:
                ?>
                        <div class="hello">
                            <h1>Simple Chat</h1>
                            <p>Простое приложение для чата.
                                <a href="auth.php">Войдите</a> или <a href="registr.php">зарегестрируйтесь</a> для начала использования.
                            </p>
                        </div>
                    <?php break;
                    case HeaderProducer::HEADER_CHAT:
                    ?>
                        <div class="conv-menu">
                            <button id="open-conv">Открытый чат</button>
                            <button id="choose-user">Пользователь</button>
                            <button id="choose-conv">Чаты</button>
                            <button id="new-chat">Создать группу</button>
                        </div>
                        <div class="chat-block hidden">
                            <div class="chat">

                            </div>
                            <div class="send-message">
                                <input type="text" name="user-message" id="user-message" placeholder="Ваше сообщение">
                                <button id="send">-></button>
                            </div>
                        </div>
                        <div class="user-search-block hidden">
                            <div class="contact-search">
                                <input type="text" name="contact-search" id="contact-search" placeholder="Имя">
                                <button id="search">-></button>
                            </div>
                            <div class="user-list">
                                <div class="num-found">
                                    <p>Найдено: 0</p>
                                </div>
                            </div>

                        </div>
                <?php break;
                    default:
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

<?php

require_once('includes/storagehandler.php');
function getUserListJson(string $namePart, StorageHandler $storage): string {
    if (mb_strlen($namePart) > 3) {
        $result = $storage->searchUserNames($namePart);
        $storage->closeStorage();
        return json_encode($result);
    }
    $storage->closeStorage();
    return '[]';
}

function conversation() {
    if (isset($_GET['convUserId'])) {
        session_start();
        if (isset($_SESSION['userId'])) {
            require_once('includes/dbstorage.php');
            $storage = new DBStorage();
            $convId = $storage->openConversation((int)$_SESSION['userId'], (int)$_GET['convUserId']);
            $_SESSION['convId'] = $convId;
            $storage->storeConversationId(session_id(), $convId);
            $storage->closeStorage();
            echo 'ok';
            exit;
        }
    }
}

function userSearch() {
    if (isset($_GET['namePart'])) {
        require_once('includes/dbstorage.php');
        require_once('includes/utils.php');
        $namePart = inputUtils::nameTrim($_GET['namePart']);
        if (inputUtils::nameCheck($namePart)) {
            header('Content-type: application/json');
            echo getUserListJson($namePart, new DBStorage());
        }
        exit;
    }
}

function exitSession() {
    if (isset($_GET['exit'])) {
        session_start();
        $_SESSION = array();
        require_once('includes/dbstorage.php');
        (new DBStorage())->clearSession(session_id());
        header('Location: /');
    }
}

function getMessagesJSON() {
    if (
        isset($_GET['loadMessages']) && session_start() && isset($_SESSION['userId'])
        && isset($_SESSION['convId'])
    ) {
        require_once('includes/dbstorage.php');
        $storage = new DBStorage();
        $messages = $storage->getMessages($_SESSION['convId']);
        $userName = $_SESSION['user'];
        foreach ($messages as &$message) {
            if ($message['user_name'] === $userName)
                $message['user_name'] = 'me';
        }
        header('Content-type: application/json');
        echo json_encode($messages);
        exit;
    }
}

function sendMessage() {
    if (
        isset($_POST['message']) && session_start()
        && isset($_SESSION['userId']) && isset($_SESSION['convId'])
    ) {
        require_once('includes/dbstorage.php');
        $storage = new DBStorage();
        $storage->storeMessage($_POST['message'], $_SESSION['userId'], $_SESSION['convId']);
        echo 'ok';
        exit;
    }
}
