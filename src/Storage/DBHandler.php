<?php

declare(strict_types=1);

namespace tTorMt\SChat\Storage;

/**
 * Database handler interface
 */
interface DBHandler
{
    /**
     * Closes the database connection
     *
     * @return bool
     */
    public function closeConnection(): bool;

    /**
     * Creates a user account and saves it to the database.
     *
     * @param string $userName
     * @param string $passwordHash
     * @param string $email
     * @return int - new user id
     */
    public function newUser(string $userName, string $passwordHash, string $email): int;

    /**
     * Retrieves user data
     *
     * @param string $userName
     * @return array|false ['user_id' => , 'user_name' => , 'password_hash' => , 'email_id' =>, 'email' =>, 'is_verified' =>]
     */
    public function getUserData(string $userName): array|false;

    /**
     * Deletes a user account
     *
     * @param int $userId
     * @return bool
     */
    public function deleteUser(int $userId): bool;

    /**
     * Creates a new chat
     *
     * @param string $chatName
     * @param int $chatType
     * @return bool
     */
    public function newChat(string $chatName, int $chatType): bool;

    /**
     * Retrieves chat ID
     *
     * @param string $chatName
     * @return int|false
     */
    public function getChatId(string $chatName): int|false;

    /**
     * Deletes a chat
     *
     * @param int $chatId
     * @return bool
     */
    public function deleteChat(int $chatId): bool;

    /**
     * Adds a user to an existing chat
     *
     * @param int $chatId
     * @param int $userId
     * @return bool
     */
    public function addUserToChat(int $chatId, int $userId): bool;

    /**
     * Removes a user from chat
     *
     * @param int $userId
     * @param int $chatId
     * @return bool false if user not in the chat
     */
    public function deleteUserFromChat(int $userId, int $chatId): bool;

    /**
     * Retrieves the active user's chat list
     *
     * @param string $sessionId
     * @return array ['chat_name' =>, 'chat_id' =>, 'chat_type']
     */
    public function chatList(string $sessionId): array;

    /**
     * Checks if the user is in the chat
     * @param int $userId
     * @param int $chatId
     * @return bool
     */
    public function isInChat(int $userId, int $chatId): bool;

    /**
     * Sets the active chat for a user session
     *
     * @param string $sessionId
     * @param int $activeChatId
     * @return bool
     */
    public function setActiveChat(string $sessionId, int $activeChatId): bool;

    /**
     * Retrieves the user's active chat from the session
     *
     * @param string $sessionId
     * @return int|false
     */
    public function getActiveChat(string $sessionId): int|false;

    /**
     * Retrieves all messages from the active chat stored in the session
     *
     * @param string $sessionId
     * @return array ['user_name' =>, 'chat_id' =>, 'message' =>, 'messages_date' =>, 'message_id' =>]
     */
    public function getAllMessages(string $sessionId): array;

    /**
     * Retrieves messages from specific ID and newer from the active chat stored in the session
     *
     * @param string $sessionId
     * @param int $lastMessageId
     * @return array ['user_name' =>, 'chat_id' =>, 'message' =>, 'messages_date' =>, 'message_id' =>]
     */
    public function getLastMessages(string $sessionId, int $lastMessageId): array;

    /**
     * Stores a session in the database
     *
     * @param int $userId
     * @param string $cookie
     * @return bool
     */
    public function storeSession(int $userId, string $cookie): bool;

    /**
     * Removes a session from the database
     *
     * @param int $userId
     * @return bool
     */
    public function deleteSession(int $userId): bool;

    /**
     * Retrieves a session data by cookie
     *
     * @param string $sessionId
     * @return array|false ['user_id' =>, 'active_chat_id' => ]
     */
    public function getSessionData(string $sessionId): array|false;

    /**
     * Stores a message in the database
     *
     * @param string $sessionId
     * @param string $message
     * @param bool $isFile
     * @return bool
     */
    public function storeMessage(string $sessionId, string $message, bool $isFile): bool;

    /**
     * Removes messages from chat table by chat ID
     *
     * @param int $chatId
     * @return bool
     */
    public function deleteMessagesFromChat(int $chatId): bool;

    /**
     * Retrieves the path of a file stored in a message from an active chat.
     *
     * @param string $sessionId
     * @param int $messageId
     * @return string|false
     */
    public function getFilePath(string $sessionId, int $messageId): string|false;
}
