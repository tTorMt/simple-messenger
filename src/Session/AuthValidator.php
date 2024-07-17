<?php

declare(strict_types=1);

namespace tTorMt\SChat\Session;

/**
 * Verifies the username and password for compliance with rules.
 */
class AuthValidator
{
    private const int NAME_MAX_LENGTH = 20;
    private const int NAME_MIN_LENGTH = 4;
    /**
     * Username may contain only a-Z, numbers, '_' or "'" symbol
     */
    private const string NAME_PATTERN = '/^[a-zA-Z][a-zA-Z_\' 0-9]+$/';
    private const int PASS_MIN_LENGTH = 8;
    private const int PASS_MAX_LENGTH = 32;
    /**
     * Password must contain a-Z, numbers, and special characters !@$%&_.
     */
    private const array PASS_PATTERNS = ['/[a-z]+/', '/[A-Z]+/', '/[0-9]+/', '/[!_@$%&.]+/'];

    /**
     * Checks the username meets requirements
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
     * Checks if password meets security requirements
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

    public static function nameTrim(string $name): string
    {
        $spacesPattern = '/\s+/';
        $result = trim($name);
        return preg_replace($spacesPattern, ' ', $result);
    }
}
