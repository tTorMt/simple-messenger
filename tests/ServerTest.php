<?php

declare(strict_types=1);

namespace tTorMt\SChat\Tests;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use tTorMt\SChat\Logger\DefaultLogger;
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
        try {
            self::$server->onWorkerStart($wsServer);
            self::$server->onUserConnection($wsServer, $request);
        } catch (Exception $e) {
            $this->fail();
        }
        $this->assertTrue(true);
        $request->fd = 1;
        $request->cookie = self::TEST_USER_COOKIE;
        try {
            self::$server->onUserConnection($wsServer, $request);
        } catch (Exception $e) {
            $this->fail();
        }
        $this->assertTrue(true);
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
        try {
            self::$server->onUserDisconnect($wsServer, 1);
        } catch (Exception $e) {
            $this->fail();
        }
        $this->assertTrue(true);
    }
}
