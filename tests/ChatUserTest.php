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
use tTorMt\SChat\Messenger\UpdateStartException;
use tTorMt\SChat\Storage\DBHandler;

class ChatUserTest extends TestCase
{
    /**
     * @throws MessageStoreException
     * @throws Exception
     * @throws UpdateStartException
     * @throws IncorrectCommandException
     * @throws ReflectionException
     */
    public function testChatUser(): void
    {
        $serverMock = $this->createMock(Server::class);
        $DBMock = $this->createMock(DBHandler::class);
        $chatUser = new ChatUser(0, 0, 0, 0, $serverMock, $DBMock);
        $chatUserReflect = new ReflectionClass($chatUser);
        $this->assertNotNull($chatUser);
        $this->assertSame($chatUser->getUserId(), 0);

        $chatUser->startUpdates();
        $timerIdReflect = $chatUserReflect->getProperty('timerId');
        $timerId = $timerIdReflect->getValue($chatUser);
        $this->assertNotFalse($timerId);

        $DBMock->expects($this->once())->method('storeMessage')
            ->with($this->identicalTo(0), $this->identicalTo(0), $this->identicalTo('hello world'))
            ->willReturn(true);
        $chatUser->process(['message', 'hello world']);

        $chatUser->process(['setGID', [1, 1]]);
        $gidValue = $chatUserReflect->getProperty('activeGID')->getValue($chatUser);
        $midValue = $chatUserReflect->getProperty('lastMID')->getValue($chatUser);
        $this->assertEquals(1, $gidValue);
        $this->assertEquals(1, $midValue);
        $chatUser->process(['setMID', 0]);
        $midValue = $chatUserReflect->getProperty('lastMID')->getValue($chatUser);
        $this->assertEquals(0, $midValue);

        $DBMock->expects($this->atLeastOnce())->method('deleteSession')->with($this->identicalTo(0));
        $chatUser->process(['close']);
        $timerId = $timerIdReflect->isInitialized($chatUser);
        $this->assertFalse($timerId);

        $updateReflect = $chatUserReflect->getMethod('update');
        $chatUser->process(['setGID', [-1, -1]]);
        $serverMock->expects($this->once())->method('push')->with($this->identicalTo(0), $this->identicalTo(json_encode([])));
        $DBMock->expects($this->never())->method('getLastMessages');
        $updateReflect->invoke($chatUser);
        $chatUser->process(['close']);

        $serverMock = $this->createMock(Server::class);
        $DBMock = $this->createMock(DBHandler::class);
        $chatUser = new ChatUser(0, 0, 0, 0, $serverMock, $DBMock);
        $chatUser->process(['setGID', [1, 1]]);
        $serverMock->expects($this->once())->method('push')->with($this->identicalTo(0), $this->identicalTo(json_encode(['foo', 'bar'])));
        $DBMock->expects($this->once())->method('getLastMessages')->with($this->identicalTo(1), $this->identicalTo(1))->willReturn(['foo', 'bar']);
        $updateReflect->invoke($chatUser);
        $chatUser->process(['close']);

        $this->expectException(IncorrectCommandException::class);
        $chatUser->process(['incorrectCommand']);
    }

    /**
     * @throws MessageStoreException
     * @throws UpdateStartException
     * @throws Exception
     * @throws IncorrectCommandException
     */
    public function testException(): void
    {
        $serverStub = $this->createStub(Server::class);
        $DBStub = $this->createStub(DBHandler::class);
        $DBStub->method('storeMessage')->willReturn(false);
        $chatUser = new ChatUser(0, 0, 0, 0, $serverStub, $DBStub);
        $this->expectException(MessageStoreException::class);
        $chatUser->process(['message', 'hello world']);
    }

    /**
     * @throws IncorrectCommandException
     * @throws UpdateStartException
     * @throws Exception
     */
    public function testMySqlException(): void
    {
        $serverStub = $this->createStub(Server::class);
        $DBStub = $this->createStub(DBHandler::class);
        $DBStub->method('storeMessage')->willThrowException(new \mysqli_sql_exception());
        $chatUser = new ChatUser(0, 0, 0, 0, $serverStub, $DBStub);
        $this->expectException(MessageStoreException::class);
        $chatUser->process(['message', 'hello world']);
    }
}
