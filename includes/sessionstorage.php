<?php

declare(strict_types=1);

require_once('storagehandler.php');

class SessionStorage implements StorageHandler {
    public function isNameVacant(string $name): bool {
        return !isset($_SESSION[$name]);
    }
    public function storeUser(string $name, string $pass) {
        $_SESSION[$name] = password_hash($pass, PASSWORD_DEFAULT);
    }
    public function checkCredentials(string $name, string $pass): bool {
        return isset($_SESSION[$name]) && password_verify($pass, $_SESSION[$name]);
    }
}
