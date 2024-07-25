<?php

declare(strict_types=1);

namespace tTorMt\SChat\Tests;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
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
     */
    public function testChatUser(): void
    {
        $serverStub = $this->createStub(Server::class);
        $DBStub = $this->createStub(DBHandler::class);
        $DBStub->method('storeMessage')->willReturn(true);
        $chatUser = new ChatUser(0, 0, 0, 0, $serverStub, $DBStub);
        $this->assertSame($chatUser->getUserId(), 0);
        $this->assertNotNull($chatUser);
        $chatUser->startUpdates();
        $chatUser->process(['message', 'hello world']);
        $chatUser->process(['setGID', [1, 1]]);
        $chatUser->process(['setMID', 0]);
        $chatUser->process(['close']);
        $this->assertTrue(true);
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
}
