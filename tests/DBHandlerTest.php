<?php

declare(strict_types=1);

namespace tTorMt\SChat\Tests;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use tTorMt\SChat\Storage\DBHandler;
use tTorMt\SChat\Storage\MySqlHandler;

class DBHandlerTest extends TestCase
{
    private static DBHandler $handler;
    private const string USER_NAME = 'MyUserTestName';
    private const string PASSWORD_HASH = 'MyUserTestPasswordHash';
    private const string CHAT_NAME = 'MyChatTestName';
    private const string COOKIE = 'MyTestCookie123';
    private const string MESSAGE_ONE = 'MyTestMessage1';
    private const string MESSAGE_TWO = 'MyTestMessage2';

    public static function setUpBeforeClass(): void
    {
        self::$handler = new MySqlHandler();
    }

    public static function tearDownAfterClass(): void
    {
        self::$handler->closeConnection();
    }

    public function testDBConnection(): void
    {
        $handler = new MySqlHandler();
        $this->assertNotNull($handler);
        $this->assertTrue($handler->closeConnection());
    }

    public function testUserStoring(): array
    {
        $userId = self::$handler->newUser(self::USER_NAME, self::PASSWORD_HASH);
        $userData = self::$handler->getUserData(self::USER_NAME);
        $this->assertSame(self::USER_NAME, $userData['user_name']);
        $this->assertSame(self::PASSWORD_HASH, $userData['password_hash']);
        $this->assertSame($userId, $userData['user_id']);
        return ['userId' => $userId];
    }

    #[Depends('testUserStoring')]
    public function testChats(array $testData): array
    {
        $result = self::$handler->newChat(self::CHAT_NAME, 0);
        $this->assertTrue($result);
        $chatId = self::$handler->getChatId(self::CHAT_NAME);
        $this->assertTrue($chatId && $chatId > -1);
        $testData['chatId'] = $chatId;
        return $testData;
    }

    #[Depends('testChats')]
    public function testAddUserToChat(array $testData): array
    {
        $this->assertTrue(self::$handler->addUserToChat($testData['chatId'], $testData['userId']));
        $chatId = self::$handler->chatList($testData['userId'])[0]['chat_id'];
        $this->assertTrue($testData['chatId'] === $chatId);
        return $testData;
    }

    #[Depends('testAddUserToChat')]
    public function testSession(array $testData): array
    {
        $this->assertFalse(self::$handler->getSessionData(self::COOKIE));
        $this->assertTrue(self::$handler->storeSession($testData['userId'], self::COOKIE));
        $sessionData = self::$handler->getSessionData(self::COOKIE);
        $this->assertNotEmpty($sessionData);
        $this->assertSame($testData['userId'], $sessionData['user_id']);
        $this->assertTrue(self::$handler->setActiveChat($testData['chatId'], $testData['userId']));
        $activeChatId = self::$handler->getActiveChat($testData['userId']);
        $this->assertIsInt($activeChatId);
        $this->assertSame($testData['chatId'], $activeChatId);
        return $testData;
    }

    #[Depends('testSession')]
    public function testMessaging(array $testData): array
    {
        $this->assertTrue(self::$handler->storeMessage($testData['userId'], $testData['chatId'], self::MESSAGE_ONE));
        $this->assertTrue(self::$handler->storeMessage($testData['userId'], $testData['chatId'], self::MESSAGE_TWO));
        $messages = self::$handler->getAllMessages($testData['chatId']);
        $this->assertNotEmpty($messages);
        $firstMessageId = $messages[0]['message_id'];
        $messagesFromId = self::$handler->getLastMessages($testData['chatId'], $firstMessageId);
        $this->assertNotEmpty($messagesFromId);
        $this->assertSame($messages[1], $messagesFromId[0]);
        return $testData;
    }

    #[Depends('testMessaging')]
    public function testDeleteMessages(array $testData): void
    {
        $this->assertTrue(self::$handler->deleteMessagesFromChat($testData['chatId']));
    }

    #[Depends('testSession')]
    public function testDeleteSession(array $testData): void
    {
        $this->assertTrue(self::$handler->deleteSession($testData['userId']));
    }

    #[Depends('testAddUserToChat')]
    public function testDeleteUserFromChat(array $testData): void
    {
        $this->assertTrue(self::$handler->deleteUserFromChat($testData['userId'], $testData['chatId']));
    }

    #[Depends('testUserStoring')]
    public function testUserDeleting(array $testData): void
    {
        $this->assertTrue(self::$handler->deleteUser($testData['userId']));
    }

    #[Depends('testChats')]
    public function testDeleteChat(array $testData): void
    {
        $this->assertTrue(self::$handler->deleteChat($testData['chatId']));
    }
}
