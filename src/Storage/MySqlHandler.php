<?php

declare(strict_types=1);

namespace tTorMt\SChat\Storage;

use mysqli;
use mysqli_sql_exception;
use tTorMt\SChat\Messenger\NameExistsException;

/**
 * MySql implementation of DBHandler interface
 */
class MySqlHandler implements DBHandler
{
    /**
     * Mysqli connection object
     */
    private mysqli $dataBase;
    /**
     * Array of queries for methods
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
        'isInChat' => 'SELECT * FROM chat_user WHERE user_id = ? AND chat_id = ?',
        'getChatList' => 'SELECT chat_name, chat_id, chat_type FROM chat JOIN chat_user USING(chat_id) WHERE user_id = (SELECT user_id FROM session_data WHERE cookie = ?)',
        'setActiveChat' => 'UPDATE session_data SET active_chat_id = ? WHERE cookie = ?',
        'getActiveChat' => 'SELECT active_chat_id FROM session_data WHERE cookie = ?',
        'getAllMessages' => 'SELECT user_name, chat_id, message, message_date, message_id FROM message JOIN user USING(user_id) WHERE chat_id = (SELECT active_chat_id FROM session_data WHERE cookie =?) ORDER BY message_id',
        'getLastMessages' => 'SELECT user_name, chat_id, message, message_date, message_id FROM message JOIN user USING(user_id) WHERE chat_id = (SELECT active_chat_id FROM session_data WHERE cookie =?) AND message.message_id > ? ORDER BY message_id',
        'storeSession' => 'INSERT INTO session_data (user_id, cookie) VALUES (?, ?) ON DUPLICATE KEY UPDATE cookie = ?',
        'deleteSession' => 'DELETE FROM session_data WHERE user_id = ?',
        'getSessionData' => 'SELECT user_id, active_chat_id FROM session_data WHERE cookie = ?',
        'storeMessage' => 'INSERT INTO message (user_id, chat_id, message) SELECT user_id, active_chat_id, ? FROM session_data WHERE cookie = ?',
        'deleteMessagesFromChat' => 'DELETE FROM message WHERE chat_id = ?'
    ];

    /**
     * The constructor method establishes the MySQLi connection
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
     * Closes the database connection
     *
     * @return bool
     */
    public function closeConnection(): bool
    {
        return $this->dataBase->close();
    }

    /**
     * Creates a user account and saves it to the database.
     *
     * @param string $userName
     * @param string $passwordHash
     * @return int - new user id
     * @throws NameExistsException
     */
    public function newUser(string $userName, string $passwordHash): int
    {
        try {
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
        } catch (mysqli_sql_exception $exception) {
            if ($exception->getCode() === 1062) {
                throw new NameExistsException();
            }
            throw $exception;
        }
        return (int)$result['user_id'];
    }

    /**
     * Retrieves user data
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
     * Deletes a user account
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
     * Creates a new chat
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
     * Retrieves chat ID
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
     * Deletes a chat
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
     * Adds a user to an existing chat
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
     * Removes a user from chat
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
     * Retrieves a user's chat list
     *
     * @param string $sessionId
     * @return array ['chat_name' =>, 'chat_id' =>, 'chat_type']
     */
    public function chatList(string $sessionId): array
    {
        $statement = $this->dataBase->prepare(self::QUERIES['getChatList']);
        $statement->bind_param('s', $sessionId);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $result;
    }

    /**
     * Sets the active chat for a user session
     *
     * @param string $sessionId
     * @param int $activeChatId
     * @return bool
     */
    public function setActiveChat(string $sessionId, int $activeChatId): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['setActiveChat']);
        $statement->bind_param('is', $activeChatId, $sessionId);
        $statement->execute();
        return $activeChatId === $this->getActiveChat($sessionId);
    }

    /**
     * Retrieves the user's active chat from the session
     *
     * @param string $sessionId
     * @return int|false
     */
    public function getActiveChat(string $sessionId): int|false
    {
        $statement = $this->dataBase->prepare(self::QUERIES['getActiveChat']);
        $statement->bind_param('s', $sessionId);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_assoc();
        $statement->close();
        return $result['active_chat_id'] ?? false;
    }

    /**
     * Retrieves all messages from the active chat (active chat id from the session)
     *
     * @param string $sessionId
     * @return array ['user_name' =>, 'chat_id' =>, 'message' =>, 'messages_date' =>, 'message_id' =>]
     */
    public function getAllMessages(string $sessionId): array
    {
        $statement = $this->dataBase->prepare(self::QUERIES['getAllMessages']);
        $statement->bind_param('s', $sessionId);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $result;
    }

    /**
     * Retrieves messages from specific ID and newer (using an active chat from session)
     *
     * @param string $sessionId
     * @param int $lastMessageId
     * @return array ['user_name' =>, 'chat_id' =>, 'message' =>, 'messages_date' =>, 'message_id' =>]
     */
    public function getLastMessages(string $sessionId, int $lastMessageId): array
    {
        $statement = $this->dataBase->prepare(self::QUERIES['getLastMessages']);
        $statement->bind_param('si', $sessionId, $lastMessageId);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $result;
    }

    /**
     * Stores a session in the database
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
     * Removes a session from the database
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
     * Retrieves a session data by cookie
     *
     * @param string $sessionId
     * @return array|false ['user_id' =>, 'active_chat_id' => ]
     */
    public function getSessionData(string $sessionId): array|false
    {
        $statement = $this->dataBase->prepare(self::QUERIES['getSessionData']);
        $statement->bind_param('s', $sessionId);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_assoc();
        $statement->close();
        return empty($result) ? false : $result;
    }

    /**
     * Stores a message in the database
     *
     * @param string $sessionId
     * @param string $message
     * @return bool
     */
    public function storeMessage(string $sessionId, string $message): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['storeMessage']);
        $statement->bind_param('ss', $message, $sessionId);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Removes messages from chat table by chat ID
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

    /**
     * Checks if the user is in the chat
     *
     * @param int $userId
     * @param int $chatId
     * @return bool
     */
    public function isInChat(int $userId, int $chatId): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['isInChat']);
        $statement->bind_param('ii', $userId, $chatId);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_assoc();
        $statement->close();
        return !empty($result);
    }
}
