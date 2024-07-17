<?php

declare(strict_types=1);

namespace tTorMt\SChat\Storage;

use mysqli;

class DBHandler
{
    /*
     * Mysqli connection object
     */
    private mysqli $dataBase;
    /**
     * Queries array for methods
     */
    private const array QUERIES = [
        'writeNewUser' => 'INSERT INTO user (user_name, password_hash) VALUES (?, ?)',
        'getUserData' => 'SELECT user_id, user_name, password_hash FROM user WHERE user_name = ?',
        'deleteUser' => 'DELETE FROM user WHERE user_id = ?',
        'newChat' => 'INSERT INTO chat (chat_name, chat_type) VALUES (?, ?)',
        'getChatId' => 'SELECT chat_id FROM chat WHERE chat_name = ?',
        'deleteChat' => 'DELETE FROM chat WHERE chat_id = ?',
        'addUserToChat' => 'INSERT INTO chat_user VALUES (?, ?)',
        'deleteUserFromChat' => 'DELETE FROM chat_user WHERE user_id = ? AND chat_id = ?',
        'getChatList' => 'SELECT chat_name, chat_id, chat_type FROM chat JOIN chat_user USING(chat_id) WHERE user_id = ?',
        'setActiveChat' => 'UPDATE session_data SET active_chat_id = ? WHERE user_id = ?',
        'getActiveChat' => 'SELECT active_chat_id FROM session_data WHERE user_id = ?',
        'getAllMessages' => 'SELECT * FROM message WHERE chat_id = ?',
        'getLastMessages' => 'SELECT * FROM message WHERE chat_id = ? AND message_id > ?',
        'storeSession' => 'INSERT INTO session_data (user_id, cookie) VALUES (?, ?) ON DUPLICATE KEY UPDATE cookie = ?',
        'deleteSession' => 'DELETE FROM session_data WHERE user_id = ?',
        'getSessionData' => 'SELECT user_id, active_chat_id FROM session_data WHERE cookie = ?',
        'storeMessage' => 'INSERT INTO message (user_id, chat_id, message) VALUES (?, ?, ?)',
        'deleteMessagesFromChat' => 'DELETE FROM message WHERE chat_id = ?'
    ];

    /**
     * Construct method makes mysqli connection
     */
    public function __construct()
    {
        $config = parse_ini_file(__DIR__ . '/../../config/config.ini');
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $mysqli = new mysqli($config['mysql_host'], $config['mysql_user'], $config['mysql_pass'], $config['mysql_db'], (int)$config['mysql_port']);
        $mysqli->set_charset('utf8mb4');
        $this->dataBase = $mysqli;
    }

    /**
     * Closes connection
     *
     * @return bool
     */
    public function closeConnection(): bool
    {
        return $this->dataBase->close();
    }

    /**
     * Creates user account and saves it to database.
     *
     * @param string $userName
     * @param string $passwordHash
     * @return int - new user id
     */
    public function newUser(string $userName, string $passwordHash): int
    {
        $statement = $this->dataBase->prepare(self::QUERIES['writeNewUser']);
        $statement->bind_param('ss', $userName, $passwordHash);
        $statement->execute();
        $statement->close();
        $statement = $this->dataBase->prepare(self::QUERIES['getUserData']);
        $statement->bind_param('s', $userName);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_assoc();
        $statement->close();
        return (int)$result['user_id'];
    }

    /**
     * Gets user data
     *
     * @param string $userName
     * @return array|false ['user_id' => , 'user_name' => , 'password_hash' => ]
     */
    public function getUserData(string $userName): array|false
    {
        $statement = $this->dataBase->prepare(self::QUERIES['getUserData']);
        $statement->bind_param('s', $userName);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_assoc();
        $statement->close();
        return $result ?? false;
    }

    /**
     * Deletes user account
     *
     * @param int $userId
     * @return bool
     */
    public function deleteUser(int $userId): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['deleteUser']);
        $statement->bind_param('i', $userId);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Creates new chat
     *
     * @param string $chatName
     * @param int $chatType
     * @return bool
     */
    public function newChat(string $chatName, int $chatType): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['newChat']);
        $statement->bind_param('si', $chatName, $chatType);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Gets chat id
     *
     * @param string $chatName
     * @return int|false
     */
    public function getChatId(string $chatName): int|false
    {
        $statement = $this->dataBase->prepare(self::QUERIES['getChatId']);
        $statement->bind_param('s', $chatName);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_assoc();
        $statement->close();
        return $result['chat_id'] ?? false;
    }

    /**
     * Deletes chat
     *
     * @param int $chatId
     * @return bool
     */
    public function deleteChat(int $chatId): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['deleteChat']);
        $statement->bind_param('i', $chatId);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Adds user to existing chat
     *
     * @param int $chatId
     * @param int $userId
     * @return bool
     */
    public function addUserToChat(int $chatId, int $userId): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['addUserToChat']);
        $statement->bind_param('ii', $chatId, $userId);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Deletes user from chat
     *
     * @param int $userId
     * @param int $chatId
     * @return bool false if user not in the chat
     */
    public function deleteUserFromChat(int $userId, int $chatId): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['deleteUserFromChat']);
        $statement->bind_param('ii', $userId, $chatId);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Gets user chat list
     *
     * @param int $userId
     * @return array ['chat_name' =>, 'chat_id' =>, 'chat_type']
     */
    public function chatList(int $userId): array
    {
        $statement = $this->dataBase->prepare(self::QUERIES['getChatList']);
        $statement->bind_param('i', $userId);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $result;
    }

    /**
     * Sets active chat to user session
     *
     * @param int $activeChatId
     * @param int $userId
     * @return bool
     */
    public function setActiveChat(int $activeChatId, int $userId): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['setActiveChat']);
        $statement->bind_param('ii', $activeChatId, $userId);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Gets user active chat from session
     *
     * @param int $userId
     * @return int|false
     */
    public function getActiveChat(int $userId): int|false
    {
        $statement = $this->dataBase->prepare(self::QUERIES['getActiveChat']);
        $statement->bind_param('i', $userId);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_assoc();
        $statement->close();
        return $result['active_chat_id'] ?? false;
    }

    /**
     * Gets all messages from chat
     *
     * @param int $chatId
     * @return array ['user_id' =>, 'chat_id' =>, 'message' =>, 'messages_date' =>, 'message_id' =>]
     */
    public function getAllMessages(int $chatId): array
    {
        $statement = $this->dataBase->prepare(self::QUERIES['getAllMessages']);
        $statement->bind_param('i', $chatId);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $result;
    }

    /**
     * Gets messages from id
     *
     * @param int $chatId
     * @param int $lastMessageId
     * @return array ['user_id' =>, 'chat_id' =>, 'message' =>, 'messages_date' =>, 'message_id' =>]
     */
    public function getLastMessages(int $chatId, int $lastMessageId): array
    {
        $statement = $this->dataBase->prepare(self::QUERIES['getLastMessages']);
        $statement->bind_param('ii', $chatId, $lastMessageId);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $result;
    }

    /**
     * Stores session to database
     *
     * @param int $userId
     * @param string $cookie
     * @return bool
     */
    public function storeSession(int $userId, string $cookie): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['storeSession']);
        $statement->bind_param('iss', $userId, $cookie, $cookie);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Deletes session from database
     *
     * @param int $userId
     * @return bool
     */
    public function deleteSession(int $userId): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['deleteSession']);
        $statement->bind_param('i', $userId);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Gets session data by cookie
     *
     * @param string $cookie
     * @return array|false ['user_id' =>, 'active_chat_id' => ]
     */
    public function getSessionData(string $cookie): array|false
    {
        $statement = $this->dataBase->prepare(self::QUERIES['getSessionData']);
        $statement->bind_param('s', $cookie);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_assoc();
        $statement->close();
        return empty($result) ? false : $result;
    }

    /**
     * Stores message to database
     *
     * @param int $userId
     * @param int $chatId
     * @param string $message
     * @return bool
     */
    public function storeMessage(int $userId, int $chatId, string $message): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['storeMessage']);
        $statement->bind_param('iis', $userId, $chatId, $message);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Deletes messages from chat by chat id
     *
     * @param int $chatId
     * @return bool
     */
    public function deleteMessagesFromChat(int $chatId): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['deleteMessagesFromChat']);
        $statement->bind_param('i', $chatId);
        $statement->execute();
        return $statement->affected_rows > 0;
    }
}
