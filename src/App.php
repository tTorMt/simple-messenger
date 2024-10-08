<?php

declare(strict_types=1);

namespace tTorMt\SChat;

use Psr\Log\LoggerInterface;
use tTorMt\SChat\Auth\AuthHandler;
use tTorMt\SChat\Auth\AuthValidator;
use tTorMt\SChat\Logger\DefaultLogger;
use tTorMt\SChat\Messenger\ChatManager;
use tTorMt\SChat\Messenger\ChatStoreException;
use tTorMt\SChat\Messenger\NameExistsException;
use tTorMt\SChat\Messenger\NotInTheChatException;
use tTorMt\SChat\Storage\DBHandler;

class App
{
    private DBHandler $DBHandler;
    private LoggerInterface $logger;

    public function __construct(DBHandler $DBHandler)
    {
        $this->DBHandler = $DBHandler;
        $this->logger = new DefaultLogger();
    }

    /**
     * Sets a logger
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Displays site pages and routes requests to the API
     *
     * @return void
     */
    public function run(): void
    {
        $reqPath = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 1);
        if (empty($reqPath)) {
            if (isset($_SESSION['userId'])) {
                require_once __DIR__.'/../templates/messenger.php';
                return;
            }
            require_once __DIR__.'/../templates/auth.php';
            return;
        }
        $this->$reqPath();
    }

    /**
     * Returns 404 if a resource or API method doesn't exist
     *
     * @param string $name
     * @param array $arguments
     * @return void
     */
    public function __call(string $name, array $arguments)
    {
        http_response_code(404);
    }

    /**
     * API method to authorize user
     *
     * @return void
     */
    public function auth(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['userName']) &&
            isset($_POST['userPassword'])
        ) {
            $authHandler = new AuthHandler($this->DBHandler);
            try {
                if ($authHandler->authenticate($_POST['userName'], $_POST['userPassword'])) {
                    http_response_code(200);
                    return;
                }
                http_response_code(401);
                return;
            } catch (\Exception $exception) {
                http_response_code(500);
                return;
            }
        }
        http_response_code(400);
    }
    /**
     * API method to select an active chat
     *
     * @return void
     */
    public function activeChat(): void
    {
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['chatId']) &&
            isset($_SESSION['userId'])
        ) {
            $chatManager = new ChatManager($_SESSION['userId'], $this->DBHandler);
            $chatId = (int)$_POST['chatId'];
            try {
                $chatManager->setActiveChat($chatId);
                $_SESSION['activeChatId'] = $chatId;
                http_response_code(200);
                return;
            } catch (NotInTheChatException $exception) {
                http_response_code(400);
                echo json_encode(['Error' => 'HostNotInTheChat']);
                return;
            } catch (ChatStoreException $exception) {
                http_response_code(500);
                echo json_encode(['Error' => 'ChatStoreError']);
                return;
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
                http_response_code(500);
                return;
            }
        }
        http_response_code(400);
    }

    /**
     * API method to register a new user
     *
     * @return void
     */
    public function newUser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['userName']) && isset($_POST['userPassword'])) {
            $authHandler = new AuthHandler($this->DBHandler);
            try {
                $result = $authHandler->newUserAccount($_POST['userName'], $_POST['userPassword']);
            } catch (\Exception $exception) {
                $this->logger->error($exception);
                http_response_code(500);
                return;
            }
            if ($result === true) {
                http_response_code(200);
                return;
            }
            http_response_code(400);
            $errorMessage = match ($result) {
                AuthHandler::NAME_ERROR => 'NameError',
                AuthHandler::PASSWORD_ERROR => 'PasswordError',
                AuthHandler::NAME_EXISTS => 'NameExists'
            };
            echo json_encode(['Error' => $errorMessage]);
            return;
        }
        http_response_code(400);
    }

    /**
     * API method to create a new chat
     *
     * @return void
     */
    public function newChat(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['chatName']) &&
            isset($_SESSION['userId'])
        ) {
            if (!AuthValidator::nameCheck($_POST['chatName'])) {
                http_response_code(400);
                echo json_encode(['Error' => 'NameError']);
                return;
            };
            $chatManager = new ChatManager($_SESSION['userId'], $this->DBHandler);
            try {
                $chatManager->createChat($_POST['chatName']);
                return;
            } catch (NameExistsException $exception) {
                http_response_code(400);
                echo json_encode(['Error' => 'NameExists']);
                return;
            } catch (\Exception $exception) {
                $this->logger->error($exception);
                http_response_code(500);
                return;
            }
        }
        http_response_code(400);
    }

    /**
     * API method to add a user to a chat
     *
     * @return void
     */
    public function addUserToChat(): void
    {
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['userName']) &&
            isset($_POST['chatId']) &&
            isset($_SESSION['userId'])
        ) {
            $chatManager = new ChatManager($_SESSION['userId'], $this->DBHandler);
            try {
                $chatId = (int)$_POST['chatId'];
                $userData = $this->DBHandler->getUserData($_POST['userName']);
                if ($userData === false) {
                    http_response_code(400);
                    echo json_encode(['Error' => 'UserNotFound']);
                    return;
                }
                $chatManager->addUser($chatId, $userData['user_id']);
                http_response_code(200);
                return;
            } catch (NotInTheChatException $exception) {
                http_response_code(400);
                echo json_encode(['Error' => 'HostNotInTheChat']);
                return;
            } catch (\Exception $exception) {
                $this->logger->error($exception);
                http_response_code(500);
                return;
            }
        }
        http_response_code(400);
    }

    /**
     * API method to return the current user's chat list
     *
     * @return void
     */
    public function chatList(): void
    {
        if (isset($_SESSION['userId'])) {
            try {
                $chatManager = new ChatManager($_SESSION['userId'], $this->DBHandler);
                $chatList = $chatManager->getChatList();
                echo json_encode($chatList);
            } catch (\Exception $exception) {
                http_response_code(500);
                $this->logger->error($exception);
                return;
            }
            return;
        }
        http_response_code(401);
    }

    /**
     * API method to load all messages at the start of the chat
     *
     * @return void
     */
    public function loadMessages(): void
    {
        if (isset($_SESSION['userId']) && isset($_SESSION['activeChatId'])) {
            try {
                $chatManager = new ChatManager($_SESSION['userId'], $this->DBHandler);
                $messages = $chatManager->loadMessages($_SESSION['activeChatId']);
                echo json_encode($messages);
            } catch (NotInTheChatException $exception) {
                echo json_encode(['Error' => 'HostNotInTheChat']);
                http_response_code(400);
                return;
            } catch (\Exception $exception) {
                $this->logger->error($exception);
                http_response_code(500);
            }
            return;
        }
        http_response_code(400);
    }
}
