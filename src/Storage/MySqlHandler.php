<?php

declare(strict_types=1);

namespace tTorMt\SChat\Storage;

use Exception;
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
        'writeNewEmail' => 'INSERT INTO email (email) VALUES (?)',
        'writeNewUser' => 'INSERT INTO user (user_name, password_hash, email_id) SELECT ?, ?, email_id FROM email WHERE email.email = ?',
        'addEmailVerificationToken' => 'INSERT INTO email_ver_tokens(email_id, token) SELECT email_id, ? FROM email WHERE email = ? ON DUPLICATE KEY UPDATE token = ?',
        'userVerification' => 'UPDATE email SET is_verified = 1 WHERE email_id = (SELECT email_id FROM email_ver_tokens WHERE token = ?)',
        'emailVerificationCheck' => 'SELECT is_verified FROM email WHERE email = ?',
        'emailTokenVerification' => 'SELECT is_verified FROM email JOIN email_ver_tokens USING (email_id) WHERE token = ?',
        'deleteEmailToken' => 'DELETE FROM email_ver_tokens WHERE token  = ?',
        'addPasswordToken' => 'INSERT INTO pass_cha_tokens(user_id, token) SELECT user_id, ? FROM user JOIN email USING(email_id) WHERE email = ?',
        'deletePasswordToken' => 'DELETE FROM pass_cha_tokens WHERE token = ?',
        'changePassword' => 'UPDATE user SET password_hash = ? WHERE user_id = (SELECT user_id FROM session_data WHERE cookie = ?)',
        'changePasswordByToken' => 'UPDATE user SET password_hash = ? WHERE user_id = (SELECT user_id FROM pass_cha_tokens WHERE token = ?)',
        'clearEmailTokens' => 'DELETE FROM email_ver_tokens WHERE email_id = (SELECT email_id FROM email JOIN user USING(email_id) WHERE email = ? OR user_name = ? OR user_id = ?)',
        'clearPasswordTokens' => 'DELETE FROM pass_cha_tokens WHERE user_id = (SELECT user_id FROM email JOIN user USING(email_id) WHERE email = ? OR user_name = ?) OR user_id = ?',
        'getUserData' => 'SELECT user_id, user_name, password_hash, email_id, email, is_verified FROM user JOIN email USING (email_id) WHERE user_name = ? OR email = ?',
        'deleteUser' => 'DELETE email, user FROM user JOIN email USING (email_id) WHERE user_id = ?',
        'newChat' => 'INSERT INTO chat (chat_name, chat_type) VALUES (?, ?)',
        'getChatId' => 'SELECT chat_id FROM chat WHERE chat_name = ?',
        'deleteChat' => 'DELETE FROM chat WHERE chat_id = ?',
        'addUserToChat' => 'INSERT INTO chat_user VALUES (?, ?)',
        'deleteUserFromChat' => 'DELETE FROM chat_user WHERE user_id = ? AND chat_id = ?',
        'isInChat' => 'SELECT * FROM chat_user WHERE user_id = ? AND chat_id = ?',
        'getChatList' => 'SELECT chat_name, chat_id, chat_type FROM chat JOIN chat_user USING(chat_id) WHERE user_id = (SELECT user_id FROM session_data WHERE cookie = ?)',
        'setActiveChat' => 'UPDATE session_data SET active_chat_id = ? WHERE cookie = ?',
        'getActiveChat' => 'SELECT active_chat_id FROM session_data WHERE cookie = ?',
        'getAllMessages' => 'SELECT user_name, chat_id, message, message_date, message_id, is_file FROM message JOIN user USING(user_id) WHERE chat_id = (SELECT active_chat_id FROM session_data WHERE cookie =?) ORDER BY message_id',
        'getLastMessages' => 'SELECT user_name, chat_id, message, message_date, message_id, is_file FROM message JOIN user USING(user_id) WHERE chat_id = (SELECT active_chat_id FROM session_data WHERE cookie =?) AND message.message_id > ? ORDER BY message_id',
        'storeSession' => 'INSERT INTO session_data (user_id, cookie) VALUES (?, ?) ON DUPLICATE KEY UPDATE cookie = ?',
        'deleteSession' => 'DELETE FROM session_data WHERE user_id = ?',
        'getSessionData' => 'SELECT user_id, active_chat_id FROM session_data WHERE cookie = ?',
        'storeMessage' => 'INSERT INTO message (user_id, chat_id, message, is_file) SELECT user_id, active_chat_id, ?, ? FROM session_data WHERE cookie = ?',
        'deleteMessagesFromChat' => 'DELETE FROM message WHERE chat_id = ?',
        'getFilePath' => 'SELECT message FROM message JOIN session_data ON message.chat_id = session_data.active_chat_id WHERE is_file = 1 AND cookie = ? AND message_id = ?'
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
     * @param string $email
     * @return int - new user id
     * @throws NameExistsException
     * @throws Exception
     */
    public function newUser(string $userName, string $passwordHash, string $email): int
    {
        try {
            $this->dataBase->begin_transaction();

            $statement = $this->dataBase->prepare(self::QUERIES['writeNewEmail']);
            $statement->bind_param('s', $email);
            $statement->execute();
            if ($statement->affected_rows === 0) {
                $this->dataBase->rollback();
                throw new Exception('Email is not inserted');
            }

            $statement = $this->dataBase->prepare(self::QUERIES['writeNewUser']);
            $statement->bind_param('sss', $userName, $passwordHash, $email);
            $statement->execute();
            if ($statement->affected_rows === 0) {
                $this->dataBase->rollback();
                $statement->close();
                throw new Exception('New user is not inserted');
            }

            $statement = $this->dataBase->prepare(self::QUERIES['getUserData']);
            $statement->bind_param('ss', $userName, $userName);
            $statement->execute();
            $result = $statement->get_result();
            $result = $result->fetch_assoc();
            if (is_null($result)) {
                $this->dataBase->rollback();
                throw new Exception('Failed to get user data');
            }
            $this->dataBase->commit();
            $statement->close();
        } catch (mysqli_sql_exception $exception) {
            $this->dataBase->rollback();
            if ($exception->getCode() === 1062) {
                throw new NameExistsException();
            }
            throw $exception;
        }
        return (int)$result['user_id'];
    }

    /**
     * Adds an email verification token row
     *
     * @param string $email
     * @param string $token
     * @return bool
     * @throws Exception
     */
    public function addEmailVerificationToken(string $email, string $token): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['addEmailVerificationToken']);
        $statement->bind_param('sss', $token, $email, $token);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Verification of the user email.
     *
     * @param string $token
     * @return bool
     */
    public function emailTokenVerification(string $token): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['emailTokenVerification']);
        $statement->bind_param('s', $token);
        $statement->execute();
        $result = $statement->get_result()->fetch_assoc();
        if (empty($result)) {
            return false;
        }
        if ($result['is_verified']) {
            return true;
        }
        $statement = $this->dataBase->prepare(self::QUERIES['userVerification']);
        $statement->bind_param('s', $token);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Removes an email token
     *
     * @param string $token
     * @return bool
     */
    public function deleteEmailToken(string $token): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['deleteEmailToken']);
        $statement->bind_param('s', $token);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Changes the user's password using a token.
     *
     * @param string $token
     * @param string $newPasswordHash
     * @return bool
     */
    public function changePasswordByToken(string $token, string $newPasswordHash): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['changePasswordByToken']);
        $statement->bind_param('ss', $newPasswordHash, $token);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Generates a new password change token and stores it in the database.
     *
     * @param string $email
     * @param string $token
     * @return bool
     */
    public function addPasswordToken(string $email, string $token): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['addPasswordToken']);
        $statement->bind_param('ss', $token, $email);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Removes a password token
     *
     * @param string $token
     * @return bool
     */
    public function deletePasswordToken(string $token): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['deletePasswordToken']);
        $statement->bind_param('s', $token);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Changes the user's password using a session ID.
     *
     * @param string $sessionId
     * @param string $newPasswordHash
     * @return bool
     */
    public function changePassword(string $sessionId, string $newPasswordHash): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['changePassword']);
        $statement->bind_param('ss', $newPasswordHash, $sessionId);
        $statement->execute();
        return $statement->affected_rows > 0;
    }

    /**
     * Removes email verification and change password tokens
     *
     * @param string $user - username, email or userID
     * @return void
     */
    public function clearTokens(string $user): void
    {
        $statement = $this->dataBase->prepare(self::QUERIES['clearEmailTokens']);
        $userID = is_numeric($user) ? (int)$user : -1;
        $statement->bind_param('ssi', $user, $user, $userID);
        $statement->execute();
        $statement->close();
        $statement = $this->dataBase->prepare(self::QUERIES['clearPasswordTokens']);
        $statement->bind_param('ssi', $user, $user, $userID);
        $statement->execute();
        $statement->close();
    }

    /**
     * Retrieves user data
     *
     * @param string $userName
     * @return array|false ['user_id' => , 'user_name' => , 'password_hash' => , 'email_id' =>, 'email' =>, 'is_verified' =>]
     */
    public function getUserData(string $userName): array|false
    {
        $statement = $this->dataBase->prepare(self::QUERIES['getUserData']);
        $statement->bind_param('ss', $userName, $userName);
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
        $this->clearTokens((string)$userId);
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
     * @return array ['user_name' =>, 'chat_id' =>, 'message' =>, 'messages_date' =>, 'message_id' =>, 'is_file' =>]
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
     * @return array ['user_name' =>, 'chat_id' =>, 'message' =>, 'messages_date' =>, 'message_id' =>, 'is_file' =>]
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
     * @param bool $isFile
     * @return bool
     */
    public function storeMessage(string $sessionId, string $message, bool $isFile = false): bool
    {
        $statement = $this->dataBase->prepare(self::QUERIES['storeMessage']);
        $statement->bind_param('sis', $message, $isFile, $sessionId);
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

    /**
     * Retrieves the path of a file stored in a message from an active chat.
     *
     * @param string $sessionId
     * @param int $messageId
     * @return string|false
     */
    public function getFilePath(string $sessionId, int $messageId): string|false
    {
        $statement = $this->dataBase->prepare(self::QUERIES['getFilePath']);
        $statement->bind_param('si', $sessionId, $messageId);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_assoc();
        $statement->close();
        return empty($result) ? false : $result['message'];
    }
}
