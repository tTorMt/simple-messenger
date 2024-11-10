<?php

declare(strict_types=1);

namespace tTorMt\SChat\Auth;

/**
 * Verifies the username and password for compliance with rules.
 */
class AuthValidator
{
    private const int NAME_MAX_LENGTH = 20;
    private const int NAME_MIN_LENGTH = 4;
    /**
     * The username may contain only letters (a-Z), numbers, underscores (_), or apostrophes (').
     */
    private const string NAME_PATTERN = '/^[a-zA-Z][a-zA-Z_\' 0-9]+$/';
    private const int PASS_MIN_LENGTH = 8;
    private const int PASS_MAX_LENGTH = 32;
    /**
     * The password must contain letters (a-Z), numbers, and special characters (!@$%&_).
     */
    private const array PASS_PATTERNS = ['/[a-z]+/', '/[A-Z]+/', '/[0-9]+/', '/[!_@$%&.]+/'];

    /**
     * Checks if the username meets the requirements.
     *
     * @param string $name
     * @return bool
     */
    public static function nameCheck(string $name): bool
    {
        return strlen($name) >= self::NAME_MIN_LENGTH  && strlen($name) <= self::NAME_MAX_LENGTH
            && preg_match(self::NAME_PATTERN, $name);
    }

    /**
     * Checks if the password meets the security requirements.
     *
     * @param string $pass
     * @return bool
     */
    public static function passCheck(string $pass): bool
    {
        if (strlen($pass) >= self::PASS_MIN_LENGTH && strlen($pass) <= self::PASS_MAX_LENGTH) {
            foreach (self::PASS_PATTERNS as $pattern) {
                if (!preg_match($pattern, $pass)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Validates email
     *
     * @param string $email
     * @return bool
     */
    public static function validateEmail(string $email): bool
    {
        return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Trims and normalizes spaces in the username.
     *
     * @param string $name
     * @return string
     */
    public static function nameTrim(string $name): string
    {
        $spacesPattern = '/\s+/';
        $result = trim($name);
        return preg_replace($spacesPattern, ' ', $result);
    }
}
