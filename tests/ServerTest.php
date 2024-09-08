<?php

declare(strict_types=1);

namespace tTorMt\SChat\Tests;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use tTorMt\SChat\Logger\DefaultLogger;
use tTorMt\SChat\Messenger\ChatUser;
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

    public static function setUpBeforeClass(): void
    {
        $database = new MySqlHandler();
        self::$testUserId = $database->newUser(self::TEST_USER_NAME, 'pass');
        $database->storeSession(self::$testUserId, self::TEST_USER_COOKIE);
    }

    public static function tearDownAfterClass(): void
    {
        $database = new MySqlHandler();
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
        $wsServer->expects($this->once())->method('push')->with(-1, 'User session not found');
        $wsServer->expects($this->once())->method('close');
        $request = $this->createMock(Request::class);
        $request->fd = -1;
        $request->cookie = '';
        $serverReflect = new ReflectionClass(Server::class);

        self::$server->onWorkerStart($wsServer);
        $this->assertInstanceOf(DBHandler::class, $serverReflect->getProperty('dbHandler')->getValue(self::$server));

        self::$server->onUserConnection($wsServer, $request);
        $this->assertFalse(isset($serverReflect->getProperty('connections')->getValue(self::$server)[$request->fd]));

        $request->fd = 1;
        $request->cookie = self::TEST_USER_COOKIE;
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
        $frame->data = '["setGID",["0","0"]]';
        $wsServer = $this->createMock(WsServer::class);
        $wsServer->expects($this->never())->method('push');
        self::$server->onMessage($wsServer, $frame);
        $frame->data = '["incorrect command"]';
        $wsServer = $this->createMock(WsServer::class);
        $wsServer->expects($this->once())->method('push')->with(1, 'Incorrect command', 1, 1);
        self::$server->onMessage($wsServer, $frame);
        $frame->data = '["message","message store error"]';
        $wsServer = $this->createMock(WsServer::class);
        $wsServer->expects($this->once())->method('push')->with(1, 'Message store error', 1, 1);
        self::$server->onMessage($wsServer, $frame);
    }

    /**
     * @throws Exception
     */
    public function testOnDisconnect(): void
    {
        $wsServer = $this->createMock(WsServer::class);
        self::$server->onUserDisconnect($wsServer, 1);
        $connections = (new ReflectionClass(Server::class))->getProperty('connections')->getValue(self::$server);
        $database = new MySqlHandler();
        $this->assertFalse($database->getSessionData(self::TEST_USER_COOKIE));
        $this->assertFalse(isset($connections[1]));
    }
}
