<?php

declare(strict_types=1);

namespace tTorMt\SChat\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use tTorMt\SChat\Auth\AuthValidator;

class AuthValidatorTest extends TestCase
{
    private ?AuthValidator $authValidator;
    private const array CORRECT_USERNAMES = [
        ["Alexander'_the_great"],
        ['taxi'],
        ['s0u0p0e0r_u3s1e4r']
    ];

    private const array CORRECT_PASSWORDS = [
        ['oworld123!Hellomynameisheyheyhey'],
        ['my_nAme_1!'],
        ['sh0rp@Ss']
    ];

    private const array INCORRECT_USERNAMES = [
        ['incorrect*name'],
        ['shr'],
        ['too_long_username_gives_error'],
        ['you!can\'tuse@'],
        ['<h1>tag</h1>']
    ];

    private const array INCORRECT_PASSWORDS = [
        ['incorrect password'],
        ['shorT1!'],
        ['Too_long_password!Too_long_password0'],
        ['<h1>tag</h1>']
    ];

    public function setUp(): void
    {
        $this->authValidator = new AuthValidator();
    }

    public function tearDown(): void
    {
        $this->authValidator = null;
    }

    public static function correctNamesProvider(): array
    {
        return self::CORRECT_USERNAMES;
    }

    public static function incorrectNamesProvider(): array
    {
        return self::INCORRECT_USERNAMES;
    }

    public static function correctPasswordsProvider(): array
    {
        return self::CORRECT_PASSWORDS;
    }

    public static function incorrectPasswordsProvider(): array
    {
        return self::INCORRECT_PASSWORDS;
    }

    #[DataProvider('correctNamesProvider')]
    public function testCorrectUserNames(string $userName): void
    {
        $this->assertTrue($this->authValidator->nameCheck($userName));
    }

    #[DataProvider('incorrectNamesProvider')]
    public function testIncorrectUserNames(string $userName): void
    {
        $this->assertFalse($this->authValidator->nameCheck($userName));
    }

    #[DataProvider('correctPasswordsProvider')]
    public function testCorrectPasswords(string $password): void
    {
        $this->assertTrue($this->authValidator->passCheck($password));
    }

    #[DataProvider('incorrectPasswordsProvider')]
    public function testIncorrectPasswords(string $password): void
    {
        $this->assertFalse($this->authValidator->passCheck($password));
    }

    public function testEmailCheck(): void
    {
        $this->assertTrue($this->authValidator->validateEmail('foo@bar.com'));
        $this->assertFalse($this->authValidator->validateEmail('foobar'));
    }

    public function testTrim(): void
    {
        $trimmedName = $this->authValidator->nameTrim("         Test_Name       \n   \t  \r");
        $this->assertSame("Test_Name", $trimmedName);
    }
}
