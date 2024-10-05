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
     * @param int $userId
     * @param DBHandler $DBHandler
     */
    public function __construct(int $userId, DBHandler $DBHandler)
    {
        $this->userId = $userId;
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
        try {
            if (!$this->DBHandler->addUserToChat($chatId, $userId)) {
                throw new AddUserException();
            }
        } catch (AddUserException $e) {
            throw $e;
        } catch (\Exception $exception) {
            throw new AddUserException($exception->getMessage(), $exception->getCode(), $exception);
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
        return $this->DBHandler->chatList($this->userId);
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
        if (!$this->DBHandler->setActiveChat($chatId, $this->userId)) {
            throw new ChatStoreException();
        }
    }

    /**
     * Loads all messages from a chat
     *
     * @param int $activeChatId
     * @return array
     * @throws NotInTheChatException
     */
    public function loadMessages(int $activeChatId): array
    {
        if (!$this->DBHandler->isInChat($this->userId, $activeChatId)) {
            throw new NotInTheChatException();
        }
        return $this->DBHandler->getAllMessages($activeChatId);
    }
}
