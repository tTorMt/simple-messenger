<?php

declare(strict_types=1);

namespace tTorMt\SChat\Auth;

use tTorMt\SChat\Messenger\NameExistsException;
use tTorMt\SChat\Storage\DBHandler;

/**
 * Handles user authentication, account creation, and session management.
 */
class AuthHandler
{
    private DBHandler $storage;
    public const int NAME_ERROR = 0;
    public const int PASSWORD_ERROR = 1;
    public const int NAME_EXISTS = 2;
    public const int EMAIL_ERROR = 3;
    public function __construct(DBHandler $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Checks the username and password, and creates a new user account.
     *
     * @param string $userName
     * @param string $password
     * @param string $email
     * @return true|int Returns true on success, or an error code (NAME_ERROR, PASSWORD_ERROR, NAME_EXISTS) on failure.
     */
    public function newUserAccount(string $userName, string $password, string $email): true|int
    {
        $userName = AuthValidator::nameTrim($userName);
        if (!AuthValidator::nameCheck($userName)) {
            return self::NAME_ERROR;
        }
        if (!AuthValidator::passCheck($password)) {
            return self::PASSWORD_ERROR;
        }
        $userEmail = AuthValidator::nameTrim($email);
        if (!AuthValidator::validateEmail($userEmail)) {
            return self::EMAIL_ERROR;
        }
        try {
            $this->storage->newUser($userName, password_hash($password, PASSWORD_DEFAULT), $userEmail);
        } catch (NameExistsException $exception) {
            return self::NAME_EXISTS;
        }
        return true;
    }

    /**
     * Authenticates the user using name or email. Ensure the session is started before calling this method.
     *
     * @param string $userName
     * @param string $password
     * @return bool
     */
    public function authenticate(string $userName, string $password): bool
    {
        if (!(AuthValidator::nameCheck($userName) || AuthValidator::validateEmail($userName)) || !AuthValidator::passCheck($password)) {
            return false;
        }

        $userData = $this->storage->getUserData($userName);
        if (!empty($userData) && password_verify($password, $userData['password_hash']) && $userData['is_verified'] == 1) {
            $this->storage->storeSession($userData['user_id'], session_id());
            $_SESSION['userId'] = $userData['user_id'];
            $_SESSION['userName'] = $userData['user_name'];
            return true;
        }
        return false;
    }

    /**
     * Deletes the user account.
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
     * Clears the session data. Ensure the session is started before calling this method.
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
