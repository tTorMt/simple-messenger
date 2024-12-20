<?php

namespace tTorMt\SChat\Tests;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use tTorMt\SChat\App;
use tTorMt\SChat\Logger\DefaultLogger;
use tTorMt\SChat\Messenger\ChatManager;
use tTorMt\SChat\Messenger\SessionDataException;
use tTorMt\SChat\Storage\DBHandler;
use tTorMt\SChat\Storage\DirectoryCouldNotBeCreatedException;
use tTorMt\SChat\Storage\MySqlHandler;
use tTorMt\SChat\Storage\StorageHandler;

class AppTest extends TestCase
{
    private static App $app;
    private static DBHandler $handler;
    private static int $firstUserID;
    private static int $secondUserID;
    private static int $chatID;
    private const string COOKIE = 'cookie';
    private const string CHAT_NAME = 'testChatName';
    private const string USER_PASS = 'TestPass12!!';
    private const string USER_PASS_CHANGED = 'TestPass22!!';
    private const string USER_NAME_ONE = 'TestNameOne';
    private const string USER_EMAIL_ONE = 'user_one@email.com';
    private const string USER_NAME_TWO = 'TestNameTwo';
    private const string USER_EMAIL_TWO = 'user_two@email.com';
    private const string CHANGE_PASS_TOKEN = 'pass_token_32___________________';
    private const string EMAIL_PASS_TOKEN = 'email_token_32__________________';

    public static function setUpBeforeClass(): void
    {
        session_id(self::COOKIE);
    }

    /**
     * @throws DirectoryCouldNotBeCreatedException
     */
    public static function tearDownAfterClass(): void
    {

        $storageHandler = new StorageHandler();
        $activePath = $storageHandler->getSavePath();
        $pathToRemove = realpath($activePath.'/..');
        $storageHandler->removeDir($pathToRemove);
        self::$handler->clearTokens(self::USER_NAME_ONE);
        self::$handler->deleteMessagesFromChat(self::$chatID);
        self::$handler->deleteSession(self::$firstUserID);
        self::$handler->deleteUserFromChat(self::$firstUserID, self::$chatID);
        self::$handler->deleteUserFromChat(self::$secondUserID, self::$chatID);
        self::$handler->deleteChat(self::$chatID);
        self::$handler->deleteUser(self::$firstUserID);
        self::$handler->deleteUser(self::$secondUserID);
    }

    public function testAppConstruct(): void
    {
        self::$handler = new MySqlHandler();
        $app = new App(self::$handler);
        $this->assertInstanceOf(App::class, $app);
        $app->setLogger(new DefaultLogger());
        self::$app = $app;
    }

    public function testRun(): void
    {
        $_SERVER['REQUEST_URI'] = '/';
        $output = file_get_contents(__DIR__.'/../templates/auth.php');
        $this->expectOutputString($output);
        self::$app->run();
    }

    public function testMethodDoesntExists(): void
    {
        $_SERVER['REQUEST_URI'] = '/methodDoesntExists';
        $this->expectOutputString('');
        self::$app->run();
        $this->assertSame(404, http_response_code());
    }

    /**
     * @throws Exception
     */
    public function testNewUser(): void
    {
        $this->expectOutputString('');
        $_SERVER['REQUEST_METHOD'] = 'GET';
        self::$app->newUser();
        $this->assertSame(400, http_response_code());

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['userName'] = self::USER_NAME_ONE;
        $_POST['userPassword'] = self::USER_PASS;
        $_POST['userEmail'] = self::USER_EMAIL_ONE;
        self::$app->newUser();
        $userId = self::$handler->getUserData(self::USER_NAME_ONE);
        $this->assertNotFalse($userId);
        self::$firstUserID = $userId['user_id'];
        $this->assertSame(200, http_response_code());

        $_POST['userName'] = self::USER_NAME_TWO;
        $_POST['userEmail'] = self::USER_EMAIL_TWO;
        self::$app->newUser();
        $userId = self::$handler->getUserData(self::USER_NAME_TWO);
        $this->assertNotFalse($userId);
        self::$secondUserID = $userId['user_id'];
        $this->assertSame(200, http_response_code());

        $DBStub = $this->createStub(DBHandler::class);
        $DBStub->method('newUser')->willThrowException(new \Exception());
        $app = new App($DBStub);
        $app->setLogger($this->createStub(LoggerInterface::class));
        $app->newUser();
        $this->assertSame(500, http_response_code());

        $DBStub = $this->createStub(DBHandler::class);
        $DBStub->method('newUser')->willReturn(-1);
        $DBStub->method('addEmailVerificationToken')->willReturn(false);
        $DBStub->method('getUserData')->willReturn(['user_id' => -1]);
        $app = new App($DBStub);
        $app->setLogger($this->createStub(LoggerInterface::class));
        $app->newUser();
        $this->assertSame(500, http_response_code());
    }

    public function testEmailVerification(): void
    {
        unset($_GET['token']);
        self::$app->emailVerification();
        $this->assertSame(400, http_response_code());

        $_GET['token'] = 'token does not exist';
        self::$app->emailVerification();
        $this->assertSame(404, http_response_code());

        self::$handler->addEmailVerificationToken(self::USER_EMAIL_ONE, self::EMAIL_PASS_TOKEN);
        $_GET['token'] = self::EMAIL_PASS_TOKEN;
        self::$app->emailVerification();
        $this->assertSame(200, http_response_code());
    }

    #[Depends('testNewUser')]
    public function testChangePasswordError(): void
    {
        $this->expectOutputString(json_encode(['Error' => 'PasswordError']));
        $_POST['token'] = self::CHANGE_PASS_TOKEN;
        $_POST['newPassword'] = 'bad_pass';
        self::$handler->addPasswordToken(self::USER_EMAIL_ONE, self::CHANGE_PASS_TOKEN);
        self::$app->changePassword();
        $this->assertSame(http_response_code(), 400);
    }

    #[Depends('testNewUser')]
    public function testChangePassword(): void
    {
        unset($_POST['token']);
        self::$app->changePassword();
        $this->assertSame(http_response_code(), 401);

        $_POST['token'] = self::CHANGE_PASS_TOKEN;
        $_POST['newPassword'] = self::USER_PASS_CHANGED;
        self::$app->changePassword();
        $this->assertSame(http_response_code(), 200);
    }

    #[Depends('testChangePassword')]
    public function testChangePasswordTokenError(): void
    {
        $this->expectOutputString(json_encode(['Error' => 'WrongToken']));
        $_POST['token'] = self::CHANGE_PASS_TOKEN;
        $_POST['newPassword'] = self::USER_PASS_CHANGED;
        self::$app->changePassword();
        $this->assertSame(http_response_code(), 401);
    }

    /**
     * @throws Exception
     */
    #[Depends('testChangePassword')]
    public function testAuth(): void
    {
        $this->expectOutputString('');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        self::$app->auth();
        $this->assertSame(400, http_response_code());

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['userName'] = self::USER_NAME_ONE;
        $_POST['userPassword'] = self::USER_PASS;
        self::$app->auth();
        $this->assertSame(401, http_response_code());

        $_POST['userPassword'] = self::USER_PASS_CHANGED;
        self::$app->auth();
        $this->assertSame(200, http_response_code());

        $dbStub = $this->createStub(DBHandler::class);
        $dbStub->method('getUserData')->willThrowException(new \Exception());
        $app = new App($dbStub);
        $app->setLogger($this->createStub(LoggerInterface::class));
        $app->auth();
        $this->assertSame(500, http_response_code());
    }

    #[Depends('testAuth')]
    public function testChangePasswordOldNewError(): void
    {
        $this->expectOutputString(json_encode(['Error' => 'PasswordError']));
        unset($_POST['token']);
        $_POST['oldPassword'] = self::USER_PASS_CHANGED;
        $_POST['newPassword'] = 'incorrect_pass';
        self::$app->changePassword();
        $this->assertSame(http_response_code(), 400);
    }

    public function testChangePasswordOldNewNoSession(): void
    {
        $sessionID = session_id();
        session_id('no_id_in_database');
        unset($_POST['token']);
        $_POST['oldPassword'] = self::USER_PASS_CHANGED;
        $_POST['newPassword'] = self::USER_PASS;
        self::$app->changePassword();
        $this->assertSame(http_response_code(), 500);
        session_id($sessionID);
    }

    #[Depends('testAuth')]
    public function testChangePasswordOldNew(): void
    {
        unset($_POST['token']);
        $_POST['oldPassword'] = 'wrong_password';
        $_POST['newPassword'] = self::USER_PASS;
        self::$app->changePassword();
        $this->assertSame(http_response_code(), 401);

        $_POST['oldPassword'] = self::USER_PASS_CHANGED;
        self::$app->changePassword();
        $this->assertSame(http_response_code(), 200);
    }

    public function testNewUserWrongName(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['userName'] = 'incorrect*name';
        $_POST['userPassword'] = self::USER_PASS;
        self::$app->newUser();
        $this->expectOutputString('{"Error":"NameError"}');
        $userId = self::$handler->getUserData($_POST['userName']);
        $this->assertFalse($userId);
        $this->assertSame(400, http_response_code());
    }

    public function testNewUserWrongPassword(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['userName'] = 'UserName';
        $_POST['userPassword'] = 'incorrect password';
        self::$app->newUser();
        $this->expectOutputString('{"Error":"PasswordError"}');
        $userId = self::$handler->getUserData($_POST['userName']);
        $this->assertFalse($userId);
        $this->assertSame(400, http_response_code());
    }

    #[Depends('testNewUser')]
    public function testNewUserNameExists(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['userName'] = self::USER_NAME_ONE;
        $_POST['userPassword'] = self::USER_PASS;
        self::$app->newUser();
        $this->expectOutputString('{"Error":"NameExists"}');
        $this->assertSame(400, http_response_code());
    }

    #[Depends('testNewUser')]
    public function testNewUserEmailError(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['userName'] = 'CorrectName';
        $_POST['userPassword'] = self::USER_PASS;
        $_POST['userEmail'] = 'notAnEmail';
        self::$app->newUser();
        $this->expectOutputString('{"Error":"EmailError"}');
        $userId = self::$handler->getUserData($_POST['userName']);
        $this->assertFalse($userId);
        $this->assertSame(400, http_response_code());
    }

    #[Depends('testNewUser')]
    public function testForgotPasswordError(): void
    {
        unset($_POST['email']);
        self::$app->forgotPassword();
        $this->assertSame(http_response_code(), 400);

        $_POST['email'] = 'email_doesnt_exist';
        $this->expectOutputString(json_encode(['Error' => 'NoSuchEmail']));
        self::$app->forgotPassword();
    }

    #[Depends('testNewUser')]
    public function testForgotPassword(): void
    {
        $_POST['email'] = self::USER_EMAIL_ONE;
        self::$app->forgotPassword();
        $this->assertSame(http_response_code(), 200);
    }

    /**
     * @throws Exception
     */
    #[Depends('testNewUser')]
    public function testNewChat(): void
    {
        $this->expectOutputString('');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        self::$app->newChat();
        $this->assertSame(400, http_response_code());

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['userId'] = self::$firstUserID;
        $_POST['chatName'] = self::CHAT_NAME;
        self::$app->newChat();

        $chatId = self::$handler->getChatId(self::CHAT_NAME);
        $this->assertNotFalse($chatId);
        self::$chatID = $chatId;

        $dbMock = $this->createStub(DBHandler::class);
        $dbMock->method('newChat')->willThrowException(new \Exception());
        $dbMock->method('getSessionData')->willReturn(['user_id' => -1]);
        $app = new App($dbMock);
        $app->setLogger($this->createStub(LoggerInterface::class));
        $app->newChat();
        $this->assertSame(500, http_response_code());
    }

    /**
     * @throws Exception
     */
    public function testNewChatSessionException(): void
    {
        $this->expectOutputString('{"Error":"Unauthorized"}');
        $dbMock = $this->createStub(DBHandler::class);
        $dbMock->method('getSessionData')->willReturn(false);
        $app = new App($dbMock);
        $app->setLogger($this->createStub(LoggerInterface::class));
        $app->newChat();
        $this->assertSame(401, http_response_code());
    }


    #[Depends('testNewChat')]
    public function testNewChatNameExists(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['userId'] = self::$firstUserID;
        $_POST['chatName'] = self::CHAT_NAME;
        self::$app->newChat();
        $this->assertSame(400, http_response_code());

        $this->expectOutputString('{"Error":"NameExists"}');
    }

    #[Depends('testNewChat')]
    public function testNewChatNameError(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['userId'] = self::$firstUserID;
        $_POST['chatName'] = '';
        self::$app->newChat();
        $this->assertSame(400, http_response_code());

        $this->expectOutputString('{"Error":"NameError"}');
    }

    #[Depends('testNewChat')]
    public function testChatList(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['userId'] = self::$firstUserID;
        $_POST['chatName'] = self::CHAT_NAME.'TWO';
        self::$app->newChat();

        $chatId = self::$handler->getChatId(self::CHAT_NAME.'TWO');
        $this->assertNotFalse($chatId);

        $this->expectOutputString('[{"chat_name":"testChatName","chat_id":'.(self::$chatID).',"chat_type":0},{"chat_name":"testChatNameTWO","chat_id":'.($chatId).',"chat_type":0}]');
        self::$app->chatList();
        self::$handler->deleteUserFromChat(self::$firstUserID, $chatId);
        self::$handler->deleteChat($chatId);
    }

    /**
     * @throws Exception
     */
    public function testChatListFail(): void
    {
        $this->expectOutputString('');

        unset($_SESSION['userId']);
        self::$app->chatList();
        $this->assertSame(401, http_response_code());

        $_SESSION['userId'] = self::$firstUserID;
        $dbMock = $this->createStub(DBHandler::class);
        $dbMock->method('chatList')->willThrowException(new \Exception());
        $app = new App($dbMock);
        $app->setLogger($this->createStub(LoggerInterface::class));
        $app->chatList();
        $this->assertSame(401, http_response_code());

        $dbMock->method('getSessionData')->willReturn(['user_id' => -1]);
        $app->chatList();
        $this->assertSame(500, http_response_code());
    }

    #[Depends('testNewChat')]
    public function testActiveChatNotInTheChat(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['chatId'] = -1;
        self::$app->activeChat();
        $this->expectOutputString('{"Error":"HostNotInTheChat"}');
        $this->assertSame(400, http_response_code());
    }

    /**
     * @throws Exception
     */
    #[Depends('testNewChat')]
    public function testActiveChat(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        self::$app->activeChat();
        $this->assertSame(400, http_response_code());

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['chatId'] = self::$chatID;
        $_SESSION['userId'] = self::$firstUserID;

        self::$app->activeChat();
        $sessionData = self::$handler->getSessionData(self::COOKIE);
        $this->assertSame($sessionData['active_chat_id'], self::$chatID);
        $this->assertSame(200, http_response_code());

        $dbMock = $this->createStub(DBHandler::class);
        $dbMock->method('setActiveChat')->willThrowException(new \Exception());
        $dbMock->method('isInChat')->willReturn(true);
        $app = new App($dbMock);
        $app->setLogger($this->createStub(LoggerInterface::class));
        $this->expectOutputString('{"Error":"Unauthorized"}');
        $app->activeChat();
        $this->assertSame(401, http_response_code());

        $dbMock->method('getSessionData')->willReturn(['user_id' => -1]);
        $app->activeChat();
        $this->assertSame(500, http_response_code());
    }

    /**
     * @throws Exception
     */
    #[Depends('testNewChat')]
    public function testSetActiveChatStoreException(): void
    {
        $this->expectOutputString('{"Error":"ChatStoreError"}');
        $dbMock = $this->createStub(DBHandler::class);
        $dbMock->method('setActiveChat')->willReturn(false);
        $dbMock->method('isInChat')->willReturn(true);
        $dbMock->method('getSessionData')->willReturn(['user_id' => -1]);
        $app = new App($dbMock);
        $app->setLogger($this->createStub(LoggerInterface::class));
        $app->activeChat();
        $this->assertSame(500, http_response_code());
    }

    /**
     * @throws Exception
     */
    #[Depends('testActiveChat')]
    public function testUploadFileUnknownError(): void
    {
        $this->expectOutputString(json_encode(['Error' => 'UnknownError']));
        $dbMock = $this->createStub(DBHandler::class);
        $dbMock->method('storeMessage')->willThrowException(new \Exception());
        $app = new App($dbMock);
        $logger = $this->createStub(LoggerInterface::class);
        $app->setLogger($logger);
        $_FILES['file'] = [
            'type' => 'some/type',
            'tmp_name' => __DIR__.'/assets/test.file',
            'error' => UPLOAD_ERR_OK,
            'name' => 'test.file'
        ];
        $app->uploadFile();
        $this->assertSame(500, http_response_code());
    }

    #[Depends('testActiveChat')]
    public function testUploadFile(): array
    {
        $this->expectOutputString('');
        $testData = [];

        $userId = $_SESSION['userId'];
        unset($_SESSION['userId']);
        unset($_FILES['file']);
        self::$app->uploadFile();
        $this->assertSame(400, http_response_code());

        $_SESSION['userId'] = $userId;
        $_FILES['file'] = [
            'type' => 'some/type',
            'tmp_name' => __DIR__.'/assets/test.file',
            'error' => UPLOAD_ERR_OK,
            'name' => 'test.file'
        ];
        self::$app->uploadFile();
        $this->assertSame(200, http_response_code());
        $messages = self::$handler->getAllMessages(self::COOKIE);
        $fileMessageRow = $messages[count($messages) - 1];
        $this->assertSame((int)$fileMessageRow['is_file'], 1);
        $testData['anyTypeMessageID'] = $fileMessageRow['message_id'];
        $testData['anyTypeFilePath'] = $fileMessageRow['message'];

        $_FILES['file'] = [
            'type' => 'image/jpeg',
            'tmp_name' => __DIR__.'/assets/img.jpg',
            'error' => UPLOAD_ERR_OK,
            'name' => 'img.jpg'
        ];
        self::$app->uploadFile();
        $this->assertSame(200, http_response_code());
        $messages = self::$handler->getAllMessages(self::COOKIE);
        $fileMessageRow = $messages[count($messages) - 1];
        $this->assertSame((int)$fileMessageRow['is_file'], 1);
        $testData['imageMessageID'] = $fileMessageRow['message_id'];
        $testData['imageFilePath'] = $fileMessageRow['message'];

        return $testData;
    }

    #[Depends('testActiveChat')]
    public function testUploadError(): void
    {
        $_FILES['file'] = [
            'error' => 1
        ];
        $this->expectOutputString(json_encode(['Error' => 'UploadError '.'1']));
        self::$app->uploadFile();
        $this->assertSame(400, http_response_code());
    }

    #[Depends('testActiveChat')]
    public function testWrongTypeUploadError(): void
    {
        $_FILES['file'] = [
            'type' => 'image/png',
            'tmp_name' => __DIR__.'/assets/test.file',
            'error' => UPLOAD_ERR_OK,
            'name' => 'test.file'
        ];
        self::$app->uploadFile();
        $this->expectOutputString(json_encode(['Error' => 'WrongImageType']));
        $this->assertSame(400, http_response_code());
    }

    #[Depends('testUploadFile')]
    public function testGetFile(array $testData): void
    {
        unset($_GET['messageId']);
        $userID = $_SESSION['userId'];
        unset($_SESSION['userId']);
        self::$app->getFile();
        $this->assertSame(400, http_response_code());

        $_SESSION['userId'] = $userID;
        $storagePath = __DIR__.'/../storage/';
        $_GET['messageId'] = $testData['imageMessageID'];
        ob_start();
        self::$app->getFile();
        $imageOutput = ob_get_clean();
        $this->assertNotEmpty($imageOutput);
        $imageFromStorage = file_get_contents($storagePath.$testData['imageFilePath']);
        $this->assertSame($imageFromStorage, $imageOutput);
        $headers = xdebug_get_headers();
        $this->assertContains('Content-Type: image/jpeg', $headers);
        $this->assertContains('Content-Length: '. filesize($storagePath.$testData['imageFilePath']), $headers);

        $_GET['messageId'] = $testData['anyTypeMessageID'];
        ob_start();
        self::$app->getFile();
        $fileOutput = ob_get_clean();
        $this->assertNotEmpty($imageOutput);
        $fileFromStorage = file_get_contents($storagePath.$testData['anyTypeFilePath']);
        $this->assertSame($fileFromStorage, $fileOutput);
        $headers = xdebug_get_headers();
        $this->assertContains('Content-type: text/plain;charset=UTF-8', $headers);
        $this->assertContains('Content-Disposition: attachment; filename=test.file', $headers);
        $this->assertContains('Content-Length: '. filesize($storagePath.$testData['anyTypeFilePath']), $headers);
    }

    #[Depends('testUploadFile')]
    public function testGetFileNotFount(): void
    {
        $this->expectOutputString(json_encode(['Error' => 'FileNotFound']));
        $_GET['messageId'] = -1;
        self::$app->getFile();
        $this->assertSame(404, http_response_code());
    }

    /**
     * @throws Exception
     */
    #[Depends('testChatList')]
    public function testAddUserToChatHostNotInChat(): void
    {
        $this->expectOutputString('{"Error":"HostNotInTheChat"}');
        $dbMock = $this->createStub(DBHandler::class);
        $dbMock->method('isInChat')->willReturn(false);
        $dbMock->method('getSessionData')->willReturn(['user_id' => -1]);
        $app = new App($dbMock);
        $app->setLogger($this->createStub(LoggerInterface::class));
        $app->addUserToChat();
        $this->assertSame(400, http_response_code());
    }

    #[Depends('testChatList')]
    public function testAddUserToChatUserNotFound(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['userId'] = self::$firstUserID;
        $_POST['chatId'] = 0;
        $_POST['userName'] = 'NoSuchUser';
        $this->expectOutputString('{"Error":"UserNotFound"}');
        self::$app->addUserToChat();
        $this->assertSame(400, http_response_code());
    }

    /**
     */
    #[Depends('testChatList')]
    public function testAddUserToChat(): void
    {
        $this->expectOutputString('');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        self::$app->addUserToChat();
        $this->assertSame(400, http_response_code());

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['userId'] = self::$firstUserID;
        $_POST['chatId'] = self::$chatID;
        $_POST['userName'] = self::USER_NAME_TWO;
        self::$app->addUserToChat();
        $result = self::$handler->isInChat(self::$secondUserID, self::$chatID);
        $this->assertTrue($result);
        $this->assertSame(200, http_response_code());
    }

    /**
     * @throws Exception
     */
    #[Depends('testChatList')]
    public function testAddUserToChatExceptions(): void
    {
        $this->expectOutputString('{"Error":"Unauthorized"}');
        $dbMock = $this->createStub(DBHandler::class);
        $dbMock->method('addUserToChat')->willThrowException(new \Exception());
        $dbMock->method('getActiveChat')->willReturn(-1);
        $dbMock->method('isInChat')->willReturn(true);
        $dbMock->method('getUserData')->willReturn(['user_id' => -1]);
        $app = new App($dbMock);
        $app->setLogger($this->createStub(LoggerInterface::class));
        $app->addUserToChat();
        $this->assertSame(401, http_response_code());
        $dbMock->method('getSessionData')->willReturn(['user_id' => -1]);
        $app->addUserToChat();
        $this->assertSame(500, http_response_code());
    }

    /**
     * @throws SessionDataException
     */
    #[Depends('testActiveChat')]
    public function testLoadMessages(): void
    {
        $_SESSION['userId'] = self::$firstUserID;
        self::$handler->storeMessage(self::COOKIE, 'foo', false);
        self::$handler->storeMessage(self::COOKIE, 'bar', false);
        $chatManager = new ChatManager(self::COOKIE, self::$handler);
        $messages = $chatManager->loadMessages();
        $this->expectOutputString(json_encode($messages));
        self::$app->loadMessages();
    }

    /**
     * @throws Exception
     */
    public function testLoadMessagesExceptions(): void
    {
        $this->expectOutputString('');
        $dbMock = $this->createStub(DBHandler::class);
        $app = new App($dbMock);
        $app->setLogger($this->createStub(LoggerInterface::class));
        $app->loadMessages();
        $this->assertSame(401, http_response_code());

        $dbMock->method('getSessionData')->willReturn(['user_id' => -1]);
        $dbMock->method('getAllMessages')->willThrowException(new \Exception());
        $app->loadMessages();
        $this->assertSame(500, http_response_code());

        unset($_SESSION['userId']);
        $app->loadMessages();
        $this->assertSame(400, http_response_code());
    }
}
