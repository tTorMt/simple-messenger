<?php

declare(strict_types=1);

namespace tTorMt\SChat;

use finfo;
use Psr\Log\LoggerInterface;
use tTorMt\SChat\Auth\AuthHandler;
use tTorMt\SChat\Auth\AuthValidator;
use tTorMt\SChat\Logger\DefaultLogger;
use tTorMt\SChat\Messenger\ChatManager;
use tTorMt\SChat\Messenger\ChatStoreException;
use tTorMt\SChat\Messenger\NameExistsException;
use tTorMt\SChat\Messenger\NotInTheChatException;
use tTorMt\SChat\Messenger\SessionDataException;
use tTorMt\SChat\Storage\DBHandler;
use tTorMt\SChat\Storage\DirectoryCouldNotBeCreatedException;
use tTorMt\SChat\Storage\FileStoreException;
use tTorMt\SChat\Storage\ImageStoreException;
use tTorMt\SChat\Storage\StorageHandler;
use tTorMt\SChat\Storage\WrongImageTypeException;

class App
{
    private DBHandler $DBHandler;
    private LoggerInterface $logger;
    private const array ALLOWED_IMAGE_TYPES = ['image/png', 'image/jpeg', 'image/gif'];

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
            try {
                $chatManager = new ChatManager(session_id(), $this->DBHandler);
                $chatId = (int)$_POST['chatId'];
                $chatManager->setActiveChat($chatId);
                http_response_code(200);
                return;
            } catch (SessionDataException $exception) {
                http_response_code(401);
                echo json_encode(['Error' => 'Unauthorized']);
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
            try {
                $chatManager = new ChatManager(session_id(), $this->DBHandler);
                $chatManager->createChat($_POST['chatName']);
                return;
            } catch (SessionDataException $exception) {
                http_response_code(401);
                echo json_encode(['Error' => 'Unauthorized']);
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
            isset($_SESSION['userId'])
        ) {
            try {
                $chatManager = new ChatManager(session_id(), $this->DBHandler);
                $chatId = $this->DBHandler->getActiveChat(session_id());
                if ($chatId === false) {
                    throw new NotInTheChatException();
                }
                $userData = $this->DBHandler->getUserData($_POST['userName']);
                if ($userData === false) {
                    http_response_code(400);
                    echo json_encode(['Error' => 'UserNotFound']);
                    return;
                }
                $chatManager->addUser($chatId, $userData['user_id']);
                http_response_code(200);
                return;
            } catch (SessionDataException $exception) {
                http_response_code(401);
                echo json_encode(['Error' => 'Unauthorized']);
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
                $chatManager = new ChatManager(session_id(), $this->DBHandler);
                $chatList = $chatManager->getChatList();
                echo json_encode($chatList);
            } catch (SessionDataException $exception) {
                http_response_code(401);
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
        if (isset($_SESSION['userId'])) {
            try {
                $chatManager = new ChatManager(session_id(), $this->DBHandler);
                $messages = $chatManager->loadMessages();
                echo json_encode($messages);
            } catch (SessionDataException $exception) {
                http_response_code(401);
                return;
            } catch (\Exception $exception) {
                $this->logger->error($exception);
                http_response_code(500);
            }
            return;
        }
        http_response_code(400);
    }

    /**
     * API method to upload file to server
     *
     * @return void
     */
    public function uploadFile(): void
    {
        if (isset($_SESSION['userId']) && isset($_FILES['file'])) {
            if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['Error' => 'UploadError '.$_FILES['file']['error']]);
                return;
            }
            try {
                $storageHandler = new StorageHandler();
                $fileType = $_FILES['file']['type'];
                $filePath = $_FILES['file']['tmp_name'];
                $fileName = $_FILES['file']['name'];
                if (in_array($fileType, self::ALLOWED_IMAGE_TYPES)) {
                    $savedPath = $storageHandler->storeImage($filePath);
                    $this->DBHandler->storeMessage(session_id(), $savedPath, true);
                    http_response_code(200);
                    return;
                }
                $savedPath = $storageHandler->storeFile($filePath, $fileName);
                $this->DBHandler->storeMessage(session_id(), $savedPath, true);
                http_response_code(200);
            } catch (ImageStoreException|DirectoryCouldNotBeCreatedException $exception) {
                $this->logger->error($exception);
                http_response_code(500);
                echo json_encode(['Error' => 'ImageStoreError']);
                return;
            } catch (FileStoreException $exception) {
                $this->logger->error($exception);
                http_response_code(500);
                echo json_encode(['Error' => 'FileStoreError']);
                return;
            } catch (WrongImageTypeException $exception) {
                http_response_code(400);
                echo json_encode(['Error' => 'WrongImageType']);
                return;
            } catch (\Exception $exception) {
                $this->logger->error($exception);
                echo json_encode(['Error' => 'UnknownError']);
                http_response_code(500);
            }
            return;
        }
        http_response_code(400);
    }

    /**
     * API method to get files from messages
     *
     * @return void
     */
    public function getFile(): void
    {
        if (isset($_SESSION['userId']) && isset($_GET['messageId'])) {
            $filePath = $this->DBHandler->getFilePath(session_id(), (int)$_GET['messageId']);
            $storageDir = __DIR__.'/../storage/';
            if ($filePath === false || !file_exists($filePath = $storageDir.$filePath)) {
                echo json_encode(['Error' => 'FileNotFound']);
                http_response_code(404);
                return;
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $fileType = $finfo->file($filePath);
            if (in_array($fileType, self::ALLOWED_IMAGE_TYPES)) {
                header('Content-Type: '.$fileType);
                header('Content-Length: '.filesize($filePath));
                readfile($filePath);
                return;
            }
            $fileName = explode('$', $filePath)[1];
            header('Content-Disposition: attachment; filename='.$fileName);
            header('Content-Type: '.$fileType);
            header('Content-Length: '.filesize($filePath));
            ob_clean();
            flush();
            readfile($filePath);
            return;
        }
        http_response_code(400);
    }
}
