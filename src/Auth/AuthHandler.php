<?php

declare(strict_types=1);

namespace tTorMt\SChat\Auth;

use mysqli_sql_exception;
use tTorMt\SChat\Storage\DBHandler;

/**
 * Authenticate user, create account or clear session
 */
class AuthHandler
{
    private DBHandler $storage;
    public const int NAME_ERROR = 0;
    public const int PASSWORD_ERROR = 1;
    public const int NAME_EXISTS = 2;
    public function __construct(DBHandler $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Checks username and password, creates new user account
     *
     * @param string $userName
     * @param string $password
     * @return true|int if failed returns error code NAME_ERROR, PASSWORD_ERROR, NAME_EXISTS
     */
    public function newUserAccount(string $userName, string $password): true|int
    {
        $userName = AuthValidator::nameTrim($userName);
        if (!AuthValidator::nameCheck($userName)) {
            return self::NAME_ERROR;
        }
        if (!AuthValidator::passCheck($password)) {
            return self::PASSWORD_ERROR;
        }
        try {
            $this->storage->newUser($userName, password_hash($password, PASSWORD_DEFAULT));
        } catch (mysqli_sql_exception $exception) {
            if ($exception->getCode() === 1062) {
                return self::NAME_EXISTS;
            }
            throw $exception;
        }
        return true;
    }

    /**
     * Authenticate user. Start session before using it.
     *
     * @param string $userName
     * @param string $password
     * @return bool
     */
    public function authenticate(string $userName, string $password): bool
    {
        if (!AuthValidator::nameCheck($userName) || !AuthValidator::passCheck($password)) {
            return false;
        }

        $userData = $this->storage->getUserData($userName);
        if (!empty($userData) && password_verify($password, $userData['password_hash'])) {
            $this->storage->storeSession($userData['user_id'], session_id());
            $_SESSION['userId'] = $userData['user_id'];
            $_SESSION['userName'] = $userData['user_name'];
            return true;
        }
        return false;
    }

    /**
     * Delete user account
     *
     * @param int $userId
     * @return bool
     */
    public function deleteUserAccount(int $userId): bool
    {
        $this->clearSession();
        return $this->storage->deleteUser($userId);
    }

    /**
     * Clears session data. Session must be started
     *
     * @return bool
     */
    public function clearSession(): bool
    {
        if (!empty($_SESSION) && $this->storage->deleteSession($_SESSION['userId'])) {
            $_SESSION = [];
            return true;
        }
        return false;
    }
}
