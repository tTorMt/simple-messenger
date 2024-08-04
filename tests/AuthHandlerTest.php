<?php

declare(strict_types=1);

namespace tTorMt\SChat\Tests;

use PHPUnit\Framework\TestCase;
use tTorMt\SChat\Auth\AuthHandler;
use tTorMt\SChat\Storage\MySqlHandlerGenerator;

class AuthHandlerTest extends TestCase
{
    private static AuthHandler $handler;
    private const string USER_NAME = 'newUser';
    private const string PASSWORD = 'newPassword!1';
    private const string INCORRECT_USERNAME = '123@@@hello world';
    private const string INCORRECT_PASSWORD = 'Test';
    private const string INCORRECT_PASSWORD2 = 'TestPass1!';

    public function testCreation(): void
    {
        $handler = new AuthHandler((new MySqlHandlerGenerator())->getDBHandler());
        $this->assertNotNull($handler);
        self::$handler = $handler;
    }

    public function testNewUser(): void
    {
        $this->assertSame(self::$handler->newUserAccount(self::INCORRECT_USERNAME, self::PASSWORD), AuthHandler::NAME_ERROR);
        $this->assertSame(self::$handler->newUserAccount(self::USER_NAME, self::INCORRECT_PASSWORD), AuthHandler::PASSWORD_ERROR);
        $this->assertTrue(self::$handler->newUserAccount(self::USER_NAME, self::PASSWORD));
        $this->assertSame(self::$handler->newUserAccount(self::USER_NAME, self::PASSWORD), AuthHandler::NAME_EXISTS);
    }

    public function testAuthenticatedUser(): void
    {
        $this->assertFalse(self::$handler->authenticate(self::INCORRECT_USERNAME, self::PASSWORD));
        $this->assertFalse(self::$handler->authenticate(self::USER_NAME, self::INCORRECT_PASSWORD));
        $this->assertFalse(self::$handler->authenticate(self::USER_NAME, self::INCORRECT_PASSWORD2));
        $this->assertTrue(self::$handler->authenticate(self::USER_NAME, self::PASSWORD));
        $this->assertSame(self::USER_NAME, $_SESSION['userName']);
    }

    public function testDeleteUserAccount(): void
    {
        $userid = $_SESSION['userId'];
        $this->assertTrue(self::$handler->deleteUserAccount($_SESSION['userId']));
        $this->assertEmpty($_SESSION);
        $this->assertFalse(self::$handler->deleteUserAccount($userid));
    }
}
