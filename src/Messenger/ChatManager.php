<?php

declare(strict_types=1);

namespace tTorMt\SChat\Messenger;

use tTorMt\SChat\Storage\DBHandler;

/**
 * Manages chat groups
 */
class ChatManager
{
    /**
     * A session ID of an active user
     * @var string
     */
    private string $sessionId;

    /**
     * The ID of active user
     * @var int
     */
    private int $userId;

    /**
     * A DBHandler used for manipulating chats
     */
    private DBHandler $DBHandler;

    /**
     * The constructor requires the ID of the active user
     *
     * @param string $sessionId
     * @param DBHandler $DBHandler
     * @throws SessionDataException
     */
    public function __construct(string $sessionId, DBHandler $DBHandler)
    {
        $this->sessionId = $sessionId;
        $this->userId = $DBHandler->getSessionData($sessionId)['user_id'];
        if ($this->userId === false) {
            throw new SessionDataException();
        }
        $this->DBHandler = $DBHandler;
    }

    /**
     * Creates a new chat. Adds the creator to the chat
     *
     * @param string $chatName
     * @return int A new chat ID
     * @throws ChatStoreException
     * @throws NameExistsException
     * @throws AddUserException
     */
    public function createChat(string $chatName): int
    {
        if ($this->DBHandler->getChatId($chatName) !== false) {
            throw new NameExistsException();
        }

        $this->DBHandler->newChat($chatName, 0);
        $chatId = $this->DBHandler->getChatId($chatName);
        if ($chatId === false) {
            throw new ChatStoreException();
        }
        if (!$this->DBHandler->addUserToChat($chatId, $this->userId)) {
            throw new AddUserException();
        }
        return $chatId;
    }

    /**
     * Adds a user to the existing chat if the active user is in that chat
     *
     * @param int $chatId
     * @param int $userId
     * @return void
     * @throws NotInTheChatException
     * @throws AddUserException
     */
    public function addUser(int $chatId, int $userId): void
    {
        if (!$this->DBHandler->isInChat($this->userId, $chatId)) {
            throw new NotInTheChatException();
        }
        if (!$this->DBHandler->addUserToChat($chatId, $userId)) {
            throw new AddUserException();
        }
    }

    /**
     * Removes the user from the chat
     *
     * @param int $chatId
     * @return void
     * @throws DeleteUserFromChatException
     */
    public function leaveChat(int $chatId): void
    {
        if (!$this->DBHandler->deleteUserFromChat($this->userId, $chatId)) {
            throw new DeleteUserFromChatException();
        }
    }

    /**
     * Gets list of the active user chats
     *
     * @return array ['chat_name' =>, 'chat_id' =>, 'chat_type']
     */
    public function getChatList(): array
    {
        return $this->DBHandler->chatList($this->sessionId);
    }

    /**
     * Sets an active chat ID
     *
     * @param int $chatId
     * @return void
     * @throws NotInTheChatException
     * @throws ChatStoreException
     */
    public function setActiveChat(int $chatId): void
    {
        if (!$this->DBHandler->isInChat($this->userId, $chatId)) {
            throw new NotInTheChatException();
        }
        if (!$this->DBHandler->setActiveChat($this->sessionId, $chatId)) {
            throw new ChatStoreException();
        }
    }

    /**
     * Loads all messages from an active chat
     *
     * @return array
     */
    public function loadMessages(): array
    {
        return $this->DBHandler->getAllMessages($this->sessionId);
    }
}
