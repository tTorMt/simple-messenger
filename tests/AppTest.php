<?php

namespace tTorMt\SChat\Tests;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use tTorMt\SChat\App;
use tTorMt\SChat\Logger\DefaultLogger;
use tTorMt\SChat\Messenger\ChatStoreException;
use tTorMt\SChat\Storage\DBHandler;
use tTorMt\SChat\Storage\MySqlHandler;

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
    private const string USER_NAME_ONE = 'TestNameOne';
    private const string USER_NAME_TWO = 'TestNameTwo';

    public static function setUpBeforeClass(): void
    {
        session_id(self::COOKIE);
    }

    public static function tearDownAfterClass(): void
    {
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
        self::$app->newUser();
        $userId = self::$handler->getUserData(self::USER_NAME_ONE);
        $this->assertNotFalse($userId);
        self::$firstUserID = $userId['user_id'];
        $this->assertSame(200, http_response_code());

        $_POST['userName'] = self::USER_NAME_TWO;
        self::$app->newUser();
        $userId = self::$handler->getUserData(self::USER_NAME_TWO);
        $this->assertNotFalse($userId);
        self::$secondUserID = $userId['user_id'];
        $this->assertSame(200, http_response_code());

        $DBStub = $this->createStub(DBHandler::class);
        $DBStub->method('newUser')->willThrowException(new \PHPUnit\Framework\Exception());
        $app = new App($DBStub);
        $app->setLogger($this->createStub(LoggerInterface::class));
        $app->newUser();
        $this->assertSame(500, http_response_code());
    }

    /**
     * @throws Exception
     */
    #[Depends('testNewUser')]
    public function testAuth(): void
    {
        $this->expectOutputString('');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        self::$app->auth();
        $this->assertSame(400, http_response_code());

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['userName'] = self::USER_NAME_ONE;
        $_POST['userPassword'] = self::USER_PASS.'wrong';
        self::$app->auth();
        $this->assertSame(401, http_response_code());

        $_POST['userPassword'] = self::USER_PASS;
        self::$app->auth();
        $this->assertSame(200, http_response_code());

        $dbStub = $this->createStub(DBHandler::class);
        $dbStub->method('getUserData')->willThrowException(new \Exception());
        $app = new App($dbStub);
        $app->setLogger($this->createStub(LoggerInterface::class));
        $app->auth();
        $this->assertSame(500, http_response_code());
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
        $app = new App($dbMock);
        $app->setLogger($this->createStub(LoggerInterface::class));
        $app->newChat();
        $this->assertSame(500, http_response_code());
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
        $this->assertSame(500, http_response_code());
    }

    #[Depends('testNewChat')]
    public function testActiveChatNotInTheChat(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['chatId'] = self::$chatID;
        $_SESSION['userId'] = self::$secondUserID;
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
        $app->activeChat();
        $this->assertSame(500, http_response_code());
    }

    #[Depends('testNewChat')]
    public function testSetActiveChatStoreException(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['chatId'] = self::$chatID;
        $_SESSION['userId'] = self::$firstUserID;

        self::$handler->deleteSession(self::$firstUserID);
        $this->expectOutputString('{"Error":"ChatStoreError"}');
        self::$app->activeChat();
        $this->assertSame(500, http_response_code());
    }

    #[Depends('testChatList')]
    public function testAddUserToChatHostNotInChat(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['userId'] = self::$secondUserID;
        $_POST['chatId'] = 0;
        $_POST['userName'] = self::USER_NAME_ONE;
        $this->expectOutputString('{"Error":"HostNotInTheChat"}');
        self::$app->addUserToChat();
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
     * @throws Exception
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

        $dbMock = $this->createStub(DBHandler::class);
        $dbMock->method('getUserData')->willThrowException(new \Exception());
        $app = new App($dbMock);
        $app->setLogger($this->createStub(LoggerInterface::class));
        $app->addUserToChat();
        $this->assertSame(500, http_response_code());
    }
}
