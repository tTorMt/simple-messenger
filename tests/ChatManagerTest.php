<?php

declare(strict_types=1);

namespace tTorMt\SChat\Tests;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use tTorMt\SChat\Messenger\AddUserException;
use tTorMt\SChat\Messenger\ChatManager;
use tTorMt\SChat\Messenger\ChatStoreException;
use tTorMt\SChat\Messenger\DeleteUserFromChatException;
use tTorMt\SChat\Messenger\NameExistsException;
use tTorMt\SChat\Messenger\NotInTheChatException;
use tTorMt\SChat\Storage\DBHandler;
use tTorMt\SChat\Storage\MySqlHandler;

class ChatManagerTest extends TestCase
{
    private static DBHandler $storage;
    private static int $mainUserID;
    private static int $secondUserID;
    private static int $chatID;
    private const string MAIN_USER_NAME = 'test_user_1';
    private const string SECOND_USER_NAME = 'test_user_2';
    private const string PASS_HASH = 'test_user_password';
    private const string CHAT_NAME = 'test_chat';

    /**
     * @throws NameExistsException
     */
    public static function setUpBeforeClass(): void
    {
        self::$storage = new MySqlHandler();
        self::$mainUserID = self::$storage->newUser(self::MAIN_USER_NAME, self::PASS_HASH);
        self::$secondUserID = self::$storage->newUser(self::SECOND_USER_NAME, self::PASS_HASH);
        self::$storage->storeSession(self::$mainUserID, 'cookie');
    }
    public static function tearDownAfterClass(): void
    {
        self::$storage->deleteMessagesFromChat(self::$chatID);
        self::$storage->deleteSession(self::$mainUserID);
        self::$storage->deleteUserFromChat(self::$secondUserID, self::$chatID);
        self::$storage->deleteUserFromChat(self::$mainUserID, self::$chatID);
        self::$storage->deleteChat(self::$chatID);
        self::$storage->deleteUser(self::$mainUserID);
        self::$storage->deleteUser(self::$secondUserID);
        self::$storage->closeConnection();
    }
    public function testManagerCreation(): ChatManager
    {
        $chatManager = new ChatManager(self::$mainUserID, self::$storage);
        $this->assertInstanceOf(ChatManager::class, $chatManager);
        return $chatManager;
    }

    /**
     * @throws NameExistsException
     * @throws ChatStoreException
     * @throws AddUserException
     */
    #[Depends('testManagerCreation')]
    public function testCreateChat(ChatManager $chatManager): ChatManager
    {
        $chatID = $chatManager->createChat(self::CHAT_NAME);
        $this->assertSame($chatID, self::$storage->getChatId(self::CHAT_NAME));
        self::$chatID = $chatID;
        $chatList = $chatManager->getChatList();
        $this->assertTrue(!empty($chatList) && $chatList[0]['chat_id'] == $chatID);
        return $chatManager;
    }

    /**
     * @throws AddUserException
     * @throws ChatStoreException
     */
    #[Depends('testCreateChat')]
    public function testNameExistsException(ChatManager $chatManager): void
    {
        $this->expectException(NameExistsException::class);
        $chatManager->createChat(self::CHAT_NAME);
    }

    #[Depends('testCreateChat')]
    public function testLoadMessagesNotInTheChat(): void
    {
        $this->expectException(NotInTheChatException::class);
        $chatManager = new ChatManager(self::$secondUserID, self::$storage);
        $chatManager->loadMessages(self::$chatID);
    }

    /**
     * @throws AddUserException
     * @throws Exception
     * @throws NameExistsException
     */
    public function testChatStoreException(): void
    {
        $storageStub = $this->createStub(DBHandler::class);
        $storageStub->method('getChatId')->willReturn(false);
        $chatManager = new ChatManager(-1, $storageStub);
        $this->expectException(ChatStoreException::class);
        $chatManager->createChat('foo');
    }

    /**
     * @throws NameExistsException
     * @throws Exception
     * @throws ChatStoreException
     */
    public function testAddUserException(): void
    {
        $storageStub = $this->createStub(DBHandler::class);
        $storageStub->method('getChatId')->willReturn(false, 0);
        $storageStub->method('addUserToChat')->willReturn(false);
        $chatManager = new ChatManager(-1, $storageStub);
        $this->expectException(AddUserException::class);
        $chatManager->createChat('foo');
    }

    /**
     * @throws NotInTheChatException
     * @throws AddUserException
     */
    #[Depends('testCreateChat')]
    public function testAddUser(ChatManager $chatManager): ChatManager
    {
        $chatManager->addUser(self::$chatID, self::$secondUserID);
        $this->assertTrue(self::$storage->isInChat(self::$secondUserID, self::$chatID));
        return $chatManager;
    }

    /**
     * @throws NotInTheChatException
     */
    #[Depends('testAddUser')]
    public function testAddUserExceptionOnAddUser(ChatManager $chatManager): void
    {
        $this->expectException(AddUserException::class);
        $chatManager->addUser(self::$chatID, self::$secondUserID);
    }

    /**
     * @throws AddUserException
     */
    #[Depends('testAddUser')]
    public function testNotInTheChatException(ChatManager $chatManager): void
    {
        $this->expectException(NotInTheChatException::class);
        $chatManager->addUser(-1, self::$secondUserID);
    }

    /**
     * @throws NotInTheChatException
     */
    #[Depends('testAddUser')]
    public function testLoadMessages(ChatManager $chatManager): void
    {
        self::$storage->storeMessage(self::$mainUserID, self::$chatID, 'foo');
        self::$storage->storeMessage(self::$secondUserID, self::$chatID, 'bar');
        $messages = $chatManager->loadMessages(self::$chatID);
        self::assertNotEmpty($messages);
        self::assertSame(
            [
                $messages[0]['user_name'], $messages[0]['chat_id'], $messages[0]['message'],
                $messages[1]['user_name'], $messages[1]['chat_id'], $messages[1]['message']],
            [
                self::MAIN_USER_NAME, self::$chatID, 'foo',
                self::SECOND_USER_NAME, self::$chatID, 'bar'
            ]
        );
    }

    /**
     * @throws NotInTheChatException
     * @throws ChatStoreException
     */
    #[Depends('testCreateChat')]
    public function testSetActiveChat(ChatManager $chatManager): void
    {
        $chatManager->setActiveChat(self::$chatID);
        $chatId = self::$storage->getActiveChat(self::$mainUserID);
        $this->assertSame($chatId, self::$chatID);
    }

    /**
     * @throws ChatStoreException
     */
    #[Depends('testCreateChat')]
    public function testNotInTheChatSACException(ChatManager $chatManager): void
    {
        $this->expectException(NotInTheChatException::class);
        $chatManager->setActiveChat(-1);
    }

    /**
     * @throws NotInTheChatException
     */
    #[Depends('testCreateChat')]
    public function testChatStoreSACException(ChatManager $chatManager): void
    {
        self::$storage->deleteSession(self::$mainUserID);
        $this->expectException(ChatStoreException::class);
        $chatManager->setActiveChat(self::$chatID);
    }

    /**
     * @throws DeleteUserFromChatException
     */
    #[Depends('testCreateChat')]
    public function testLeaveChat(ChatManager $chatManager): ChatManager
    {
        $chatManager->leaveChat(self::$chatID);
        $this->assertEmpty($chatManager->getChatList());
        return $chatManager;
    }

    #[Depends('testLeaveChat')]
    public function testDeleteUserFromChatException(ChatManager $chatManager): void
    {
        $this->expectException(DeleteUserFromChatException::class);
        $chatManager->leaveChat(self::$chatID);
    }
}
