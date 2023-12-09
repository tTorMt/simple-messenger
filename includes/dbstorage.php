<?php

declare(strict_types=1);

use Swoole\Runtime;

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
            SELECT user_name FROM user WHERE user_name LIKE ?');
        $namePart = '%' . $namePart . '%';
        $statement->bind_param('s', $namePart);
        $statement->execute();
        $result = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $userNames = [];
        foreach ($result as $row) {
            $userNames[] = $row['user_name'];
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
        $result = $stmt->get_result();
        if ($result->num_rows === 0 || $result->num_rows > 1) 
            throw new RuntimeException('Zero rows affected or two many rows in store session');
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

    private function DBConnect() {
        require_once(DBStorage::PATH_TO_CREDENTIALS);

        return new mysqli('localhost', $login, $pass, 'simple_chat');
    }
}
