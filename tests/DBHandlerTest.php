<?php

declare(strict_types=1);

namespace tTorMt\SChat\Tests;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use tTorMt\SChat\Messenger\NameExistsException;
use tTorMt\SChat\Storage\DBHandler;
use tTorMt\SChat\Storage\MySqlHandler;

class DBHandlerTest extends TestCase
{
    private static DBHandler $handler;
    private const string USER_NAME = 'MyUserTestName';
    private const string USER_EMAIL = 'myuser@email.com';
    private const string PASSWORD_HASH = 'MyUserTestPasswordHash';
    private const string CHAT_NAME = 'MyChatTestName';
    private const string COOKIE = 'MyTestCookie123';
    private const string MESSAGE_ONE = 'MyTestMessage1';
    private const string MESSAGE_TWO = 'MyTestMessage2';
    private const string PATH_TO_FILE = '/path/to/file';

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

    /**
     * @throws NameExistsException
     */
    public function testUserStoring(): array
    {
        $userId = self::$handler->newUser(self::USER_NAME, self::PASSWORD_HASH, self::USER_EMAIL);
        $userData = self::$handler->getUserData(self::USER_NAME);
        $this->assertSame(self::USER_NAME, $userData['user_name']);
        $this->assertSame(self::PASSWORD_HASH, $userData['password_hash']);
        $this->assertSame(self::USER_EMAIL, $userData['email']);
        $this->assertSame($userId, $userData['user_id']);
        return ['userId' => $userId];
    }

    #[Depends('testUserStoring')]
    public function testNameExists(): void
    {
        $this->expectException(NameExistsException::class);
        self::$handler->newUser(self::USER_NAME, self::PASSWORD_HASH, self::USER_EMAIL);
    }

    #[Depends('testUserStoring')]
    public function testSession(array $testData): array
    {
        $this->assertFalse(self::$handler->getSessionData(self::COOKIE));
        $this->assertTrue(self::$handler->storeSession($testData['userId'], self::COOKIE));
        $sessionData = self::$handler->getSessionData(self::COOKIE);
        $this->assertNotEmpty($sessionData);
        $this->assertSame($testData['userId'], $sessionData['user_id']);
        return $testData;
    }

    #[Depends('testSession')]
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
        $chatList = self::$handler->chatList(self::COOKIE);
        $chatId = $chatList[0]['chat_id'];
        $this->assertTrue($testData['chatId'] === $chatId);
        return $testData;
    }

    #[Depends('testAddUserToChat')]
    public function testIsInChat(array $testData): void
    {
        $this->assertTrue(self::$handler->isInChat($testData['userId'], $testData['chatId']));
    }

    #[Depends('testAddUserToChat')]
    public function testActiveChat(array $testData): array
    {
        $this->assertTrue(self::$handler->setActiveChat(self::COOKIE, $testData['chatId']));
        $activeChatId = self::$handler->getActiveChat(self::COOKIE);
        $this->assertIsInt($activeChatId);
        $this->assertSame($testData['chatId'], $activeChatId);
        return $testData;
    }

    #[Depends('testActiveChat')]
    public function testMessaging(array $testData): array
    {
        $this->assertTrue(self::$handler->storeMessage(self::COOKIE, self::MESSAGE_ONE, false));
        $this->assertTrue(self::$handler->storeMessage(self::COOKIE, self::MESSAGE_TWO, false));
        $messages = self::$handler->getAllMessages(self::COOKIE);
        $this->assertNotEmpty($messages);
        $firstMessageId = $messages[0]['message_id'];
        $messagesFromId = self::$handler->getLastMessages(self::COOKIE, $firstMessageId);
        $this->assertNotEmpty($messagesFromId);
        $this->assertSame($messages[1], $messagesFromId[0]);
        return $testData;
    }

    #[Depends('testActiveChat')]
    public function testGetFilePath(array $testData): void
    {
        self::$handler->storeMessage(self::COOKIE, self::PATH_TO_FILE, true);
        $messages = self::$handler->getAllMessages(self::COOKIE);
        $fileMessage = $messages[count($messages) - 1];
        $filePath = self::$handler->getFilePath(self::COOKIE, $fileMessage['message_id']);
        $this->assertNotFalse($filePath);
        $this->assertSame($filePath, self::PATH_TO_FILE);

        self::$handler->storeMessage(self::COOKIE, 'not file message', false);
        $messages = self::$handler->getAllMessages(self::COOKIE);
        $fileMessage = $messages[count($messages) - 1];
        $filePath = self::$handler->getFilePath(self::COOKIE, $fileMessage['message_id']);
        $this->assertFalse($filePath);
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
        $this->assertFalse(self::$handler->getUserData(self::USER_NAME));
    }

    #[Depends('testChats')]
    public function testDeleteChat(array $testData): void
    {
        $this->assertTrue(self::$handler->deleteChat($testData['chatId']));
    }
}
