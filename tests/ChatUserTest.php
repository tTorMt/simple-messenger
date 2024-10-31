<?php

declare(strict_types=1);

namespace tTorMt\SChat\Tests;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Swoole\WebSocket\Server;
use tTorMt\SChat\Messenger\ChatUser;
use tTorMt\SChat\Messenger\IncorrectCommandException;
use tTorMt\SChat\Messenger\MessageStoreException;
use tTorMt\SChat\Messenger\SessionDataException;
use tTorMt\SChat\Messenger\UpdateStartException;
use tTorMt\SChat\Storage\DBHandler;

class ChatUserTest extends TestCase
{
    private const string TEST_SESSION_ID = 'TestSessionID';

    /**
     * @throws MessageStoreException
     * @throws Exception
     * @throws UpdateStartException
     * @throws IncorrectCommandException
     * @throws ReflectionException
     * @throws SessionDataException
     */
    public function testChatUser(): void
    {
        $serverMock = $this->createMock(Server::class);
        $DBMock = $this->createMock(DBHandler::class);
        $chatUser = new ChatUser(0, self::TEST_SESSION_ID, 0, $serverMock, $DBMock);
        $chatUserReflect = new ReflectionClass($chatUser);
        $this->assertNotNull($chatUser);

        $chatUser->startUpdates();
        $timerIdReflect = $chatUserReflect->getProperty('timerId');
        $timerId = $timerIdReflect->getValue($chatUser);
        $this->assertNotFalse($timerId);

        $DBMock->expects($this->once())->method('storeMessage')
            ->with(self::TEST_SESSION_ID, $this->identicalTo('hello world'))
            ->willReturn(true);
        $chatUser->process(['message', 'hello world']);

        $DBMock->expects($this->once())->method('setActiveChat')->with(self::TEST_SESSION_ID, 1)->willReturn(true);
        $chatUser->process(['setGID', [1, 1]]);

        $midValue = $chatUserReflect->getProperty('lastMID')->getValue($chatUser);

        $this->assertEquals(1, $midValue);
        $chatUser->process(['setMID', 0]);
        $midValue = $chatUserReflect->getProperty('lastMID')->getValue($chatUser);
        $this->assertEquals(0, $midValue);

        $chatUser->process(['close']);
        $timerId = $timerIdReflect->isInitialized($chatUser);
        $this->assertFalse($timerId);

        $DBMock = $this->createMock(DBHandler::class);
        $DBMock->method('setActiveChat')->willReturn(true);
        $chatUser = new ChatUser(0, self::TEST_SESSION_ID, 0, $serverMock, $DBMock);
        $updateReflect = $chatUserReflect->getMethod('update');
        $chatUser->process(['setGID', [-1, -1]]);
        $serverMock->expects($this->once())->method('push')->with($this->identicalTo(0), $this->identicalTo(json_encode([])));
        $DBMock->expects($this->never())->method('getLastMessages');
        $updateReflect->invoke($chatUser);
        $chatUser->process(['close']);

        $serverMock = $this->createMock(Server::class);
        $DBMock = $this->createMock(DBHandler::class);
        $DBMock->method('setActiveChat')->willReturn(true);
        $chatUser = new ChatUser(0, self::TEST_SESSION_ID, 0, $serverMock, $DBMock);
        $chatUser->process(['setGID', [1, 1]]);
        $serverMock->expects($this->once())->method('push')->with($this->identicalTo(0), $this->identicalTo(json_encode([
            [ 'message' => 'foo', 'is_file' => false ],
            [ 'message' => 'bar', 'is_file' => false ]
        ])));
        $DBMock->expects($this->once())->method('getLastMessages')->with($this->identicalTo(self::TEST_SESSION_ID), $this->identicalTo(1))->willReturn([
            [ 'message' => 'foo', 'is_file' => false ],
            [ 'message' => 'bar', 'is_file' => false ]
        ]);
        $updateReflect->invoke($chatUser);
        $chatUser->process(['close']);

        $serverMock = $this->createMock(Server::class);
        $DBMock = $this->createMock(DBHandler::class);
        $DBMock->method('setActiveChat')->willReturn(true);
        $chatUser = new ChatUser(0, self::TEST_SESSION_ID, 0, $serverMock, $DBMock);
        $chatUser->process(['setGID', [1, 1]]);
        $serverMock->expects($this->once())->method('push')->with($this->identicalTo(0), $this->identicalTo(json_encode([
            [ 'message' => '', 'is_file' => true ]
        ])));
        $DBMock->expects($this->once())->method('getLastMessages')->with($this->identicalTo(self::TEST_SESSION_ID), $this->identicalTo(1))->willReturn([
            [ 'message' => '/path/to/file', 'is_file' => true ]
        ]);
        $updateReflect->invoke($chatUser);
        $chatUser->process(['close']);

        $this->expectException(IncorrectCommandException::class);
        $chatUser->process(['incorrectCommand']);
    }

    /**
     * @throws MessageStoreException
     * @throws UpdateStartException
     * @throws Exception
     * @throws IncorrectCommandException|SessionDataException
     */
    public function testException(): void
    {
        $serverStub = $this->createStub(Server::class);
        $DBStub = $this->createStub(DBHandler::class);
        $DBStub->method('storeMessage')->willReturn(false);
        $chatUser = new ChatUser(0, self::TEST_SESSION_ID, 0, $serverStub, $DBStub);
        $this->expectException(MessageStoreException::class);
        $chatUser->process(['message', 'hello world']);
    }

    /**
     * @throws IncorrectCommandException
     * @throws UpdateStartException
     * @throws Exception|SessionDataException
     */
    public function testMySqlException(): void
    {
        $serverStub = $this->createStub(Server::class);
        $DBStub = $this->createStub(DBHandler::class);
        $DBStub->method('storeMessage')->willThrowException(new \mysqli_sql_exception());
        $chatUser = new ChatUser(0, self::TEST_SESSION_ID, 0, $serverStub, $DBStub);
        $this->expectException(MessageStoreException::class);
        $chatUser->process(['message', 'hello world']);
    }

    /**
     * @throws IncorrectCommandException
     * @throws UpdateStartException
     * @throws Exception|MessageStoreException
     */
    public function testSessionDataException(): void
    {
        $serverStub = $this->createStub(Server::class);
        $DBStub = $this->createStub(DBHandler::class);
        $DBStub->method('setActiveChat')->willReturn(false);
        $chatUser = new ChatUser(0, self::TEST_SESSION_ID, 0, $serverStub, $DBStub);
        $this->expectException(SessionDataException::class);
        $chatUser->process(['setGID', [-1, -1]]);
    }
}
