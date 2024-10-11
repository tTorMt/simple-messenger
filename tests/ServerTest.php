<?php

declare(strict_types=1);

namespace tTorMt\SChat\Tests;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use tTorMt\SChat\Logger\DefaultLogger;
use tTorMt\SChat\Messenger\ChatUser;
use tTorMt\SChat\Messenger\NameExistsException;
use tTorMt\SChat\Storage\DBHandler;
use tTorMt\SChat\Storage\MySqlHandler;
use tTorMt\SChat\Storage\MySqlHandlerGenerator;
use tTorMt\SChat\WebSocket\Server;
use Swoole\WebSocket\Server as WsServer;

class ServerTest extends TestCase
{
    private static Server $server;
    // Test user data. Insert in user and session_data tables
    private const string TEST_USER_NAME = 'server_test_user';

    private const string TEST_USER_COOKIE = 'server_test_user_cookie';
    private static int $testUserId;
    private static int $chatId;

    /**
     * @throws NameExistsException
     */
    public static function setUpBeforeClass(): void
    {
        $database = new MySqlHandler();
        self::$testUserId = $database->newUser(self::TEST_USER_NAME, 'pass');
        $database->storeSession(self::$testUserId, self::TEST_USER_COOKIE);
        $database->newChat('TestChat', 0);
        self::$chatId = $database->getChatId('TestChat');
        $database->addUserToChat(self::$chatId, self::$testUserId);
    }

    public static function tearDownAfterClass(): void
    {
        $database = new MySqlHandler();
        $database->deleteUserFromChat(self::$testUserId, self::$chatId);
        $database->deleteChat(self::$chatId);
        $database->deleteUser(self::$testUserId);
    }

    public function testServerCreation(): void
    {
        self::$server = new Server(new MySqlHandlerGenerator());
        self::$server->setLogger(new DefaultLogger());
        $this->assertNotNull(self::$server);
        $this->assertInstanceOf(Server::class, self::$server);

    }

    /**
     * @throws Exception
     */
    public function testOnUserConnection(): void
    {
        $wsServer = $this->createMock(WsServer::class);
        $wsServer->expects($this->once())->method('push')->with(-1, '{"Error":"UserNotFound"}');
        $wsServer->expects($this->once())->method('close');
        $request = $this->createMock(Request::class);
        $request->fd = -1;
        $request->cookie = ['PHPSESSID' => ''];
        $serverReflect = new ReflectionClass(Server::class);

        self::$server->onWorkerStart($wsServer);
        $this->assertInstanceOf(DBHandler::class, $serverReflect->getProperty('dbHandler')->getValue(self::$server));

        self::$server->onUserConnection($wsServer, $request);
        $this->assertFalse(isset($serverReflect->getProperty('connections')->getValue(self::$server)[$request->fd]));

        $request->fd = 1;
        $request->cookie = ['PHPSESSID' => self::TEST_USER_COOKIE];
        self::$server->onUserConnection($wsServer, $request);
        $chatUser = $serverReflect->getProperty('connections')->getValue(self::$server)[$request->fd];
        $this->assertInstanceOf(ChatUser::class, $chatUser);
    }

    /**
     * @throws Exception
     */
    public function testOnMessage(): void
    {
        $frame = $this->createMock(Frame::class);
        $frame->fd = 1;
        $frame->data = '["setGID",["'.self::$chatId.'","0"]]';
        $wsServer = $this->createMock(WsServer::class);
        $wsServer->expects($this->never())->method('push');
        self::$server->onMessage($wsServer, $frame);
        $frame->data = '["incorrect command"]';
        $wsServer = $this->createMock(WsServer::class);
        $wsServer->expects($this->once())->method('push')->with(1, json_encode(['Error' => 'IncorrectCommand']), 1, 1);
        self::$server->onMessage($wsServer, $frame);
        $frame->data = '["message","message store error"]';
        $wsServer = $this->createMock(WsServer::class);
        $wsServer->expects($this->once())->method('push')->with(1, json_encode(['Error' => 'MessageStoreError']), 1, 1);
        (new MySqlHandler())->deleteSession(self::$testUserId);
        self::$server->onMessage($wsServer, $frame);
    }

    /**
     * @throws Exception|ReflectionException
     */
    public function testOnDisconnect(): void
    {
        $wsServer = $this->createMock(WsServer::class);
        self::$server->onUserDisconnect($wsServer, 1);
        $connections = (new ReflectionClass(Server::class))->getProperty('connections')->getValue(self::$server);
        $this->assertFalse(isset($connections[1]));
    }
}
