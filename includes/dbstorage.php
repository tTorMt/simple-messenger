<?php

declare(strict_types=1);

require_once('storagehandler.php');

class DBStorage implements StorageHandler {
    private $dataBase;
    private const PATH_TO_CREDENTIALS = '/var/www/private/dbcredent.php';

    public function __construct() {
        $this->dataBase = $this->DBConnect();
    }

    public function isNameVacant(string $name): bool {
        $statement = $this->dataBase->prepare(
            'SELECT user_id FROM user WHERE user_name = ?'
        );
        $statement->bind_param('s', $name);
        $statement->execute();
        $result = $statement->get_result();
        return $result->num_rows === 0;
    }

    public function storeUser(string $name, string $pass) {
        $passHash = password_hash($pass, PASSWORD_DEFAULT);
        $statement = $this->dataBase->prepare(
            'INSERT INTO user(user_name, user_pass) VALUES (?, ?)'
        );
        $statement->bind_param('ss', $name, $passHash);
        $statement->execute();
        if ($statement->affected_rows != 1) {
            throw new RuntimeException('Error saving user');
        }
    }

    public function checkCredentials(string $name, string $pass): bool | int {
        $statement = $this->dataBase->prepare(
            'SELECT user_name, user_pass, user_id FROM user WHERE user_name = ?'
        );
        $statement->bind_param('s', $name);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_assoc();
        return password_verify($pass, $result['user_pass']) ? $result['user_id'] : false;
    }

    public function closeStorage() {
        $this->dataBase->close();
    }

    public function searchUserNames(string $namePart): array {
        $statement = $this->dataBase->prepare('
            SELECT user_name, user_id FROM user WHERE user_name LIKE ?');
        $namePart = '%' . $namePart . '%';
        $statement->bind_param('s', $namePart);
        $statement->execute();
        $result = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $userNames = [];
        foreach ($result as $row) {
            $userNames[$row['user_name']] = $row['user_id'];
        }
        return $userNames;
    }

    public function storeSession(string $sessionId, int $user_id) {
        if ($sessionId === '' || is_null($sessionId))
            throw new RuntimeException('No session registered');
        $query = 'INSERT INTO session_data(session_id, user_id) VALUES (?,?)
            ON DUPLICATE KEY UPDATE session_id = ?';
        $stmt = $this->dataBase->prepare($query);
        $stmt->bind_param('sis', $sessionId, $user_id, $sessionId);
        $stmt->execute();
    }

    public function storeConversationId(string $sessionId, int $convId) {
        $query = 'UPDATE session_data SET conv_id = ? WHERE session_id = ?';
        $stmt = $this->dataBase->prepare($query);
        $stmt->bind_param('is', $convId, $sessionId);
        $stmt->execute();
    }

    public function openConversation(int $firstUserId, int $secondUserId): int {
        $query = 'SELECT c1.cs_id FROM 
	    (SELECT cs_id FROM conversation_user
	    WHERE user_id = ?) AS c1 JOIN
        (SELECT cs_id FROM conversation_user
        WHERE user_id = ?) AS c2
        WHERE c1.cs_id = c2.cs_id';
        $stmt = $this->dataBase->prepare($query);
        $stmt->bind_param('ii', $firstUserId, $secondUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $this->dataBase->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
            try {
                $query = 'INSERT INTO conversation(cs_type, cs_name) VALUES (1, "private")';
                $this->dataBase->query($query);
                $query = 'SELECT MAX(cs_id) AS cs_id FROM conversation';
                $result = $this->dataBase->query($query);
                $convId = (int)($result->fetch_assoc()['cs_id']);
                $query = "INSERT INTO conversation_user(cs_id, user_id) 
                    VALUES ($convId, ?), ($convId, ?)";
                $stmt = $this->dataBase->prepare($query);
                $stmt->bind_param('ii', $firstUserId, $secondUserId);
                $stmt->execute();
                $this->dataBase->commit();
                return $convId;
            } catch (mysqli_sql_exception $exception) {
                $this->dataBase->rollback();
                throw $exception;
            }
        } 
        return (int)($result->fetch_assoc()['cs_id']);
    }

    public function clearSession(string $sessionId) {
        $stmt = $this->dataBase->prepare('DELETE FROM session_data WHERE session_id = ?');
        $stmt->bind_param('s', $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0 || $result->num_rows > 1)
            throw new RuntimeException('Zero rows affected or two many rows in clear session');
    }

    public function getUserId(string $sessionId): int | bool {
        $query = 'SELECT user_id FROM session_data WHERE session_id = ?';
        $stmt = $this->dataBase->prepare($query);
        $stmt->bind_param('s', $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0)
            return false;
        elseif ($result->num_rows > 1)
            throw new RuntimeException('To many user_id for one session');
        return ($result->fetch_assoc())['user_id'];
    }

    public function getMessages(int $convId): array {
        $query = 'SELECT user_name, message, ms_date FROM message JOIN user USING(user_id)
            WHERE cs_id = ? ORDER BY ms_date';
        $stmt = $this->dataBase->prepare($query);
        $stmt->bind_param('i', $convId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getMessagesFromDate(int $convId, DateTime $lastRefreshTime): array {
        $sqlDate = null; //To Do convert DateTime to sql datetime
        $query = 'SELECT user_id, message, ms_date FROM message
            WHERE cs_id = ? AND ms_date > ? ORDER BY ms_date';
        $stmt = $this->dataBase->prepare($query);
        $stmt->bind_param('is', $convId, $sqlDate);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function storeMessage(string $message, int $userId, int $convId) {
        $query = 'INSERT INTO message(user_id, cs_id, message, ms_date) 
            VALUES (?, ?, ?, NOW())';
        $stmt = $this->dataBase->prepare($query);
        $stmt->bind_param('iis', $userId, $convId, $message);
        $stmt->execute();
    }

    private function DBConnect() {
        require_once(DBStorage::PATH_TO_CREDENTIALS);

        return new mysqli('localhost', $login, $pass, 'simple_chat');
    }
}
