<?php

declare(strict_types=1);

require_once('storagehandler.php');

class DBStorage implements StorageHandler {
    private $dataBase;
    private const PATH_TO_CREDENTIALS = '../../private/dbcredent.php';

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

    public function checkCredentials(string $name, string $pass): bool {
        $statement = $this->dataBase->prepare(
            'SELECT user_name, user_pass FROM user WHERE user_name = ?'
        );
        $statement->bind_param('s', $name);
        $statement->execute();
        $result = $statement->get_result();
        $result = $result->fetch_assoc();
        return password_verify($pass, $result['user_pass']);
    }

    public function closeStorage() {
        $this->dataBase->close();
    }

    public function searchUserNames(string $namePart):array {
        $statement = $this->dataBase->prepare('
            SELECT user_name FROM user WHERE user_name LIKE ?');
        $namePart = '%'.$namePart.'%';
        $statement->bind_param('s', $namePart);
        $statement->execute();
        $result = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $userNames = [];
        foreach ($result as $row) {
            $userNames[] = $row['user_name'];
        }
        return $userNames;
    }

    private function DBConnect() {
        require_once(DBStorage::PATH_TO_CREDENTIALS);

        return new mysqli('localhost', $login, $pass, 'simple_chat');
    }
}
