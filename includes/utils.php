<?php

declare(strict_types=1);

class inputUtils
{
    const NAME_MAX_LENGTH = 20;
    const NAME_MIN_LENGTH = 4;
    const NAME_PATTERN = '/^[a-zA-Z][a-zA-Z_\' 0-9]+$/';
    const PASS_MIN_LENGTH = 8;
    const PASS_MAX_LENGTH = 32;
    const PASS_PATTERNS = ['/[a-z]+/', '/[A-Z]+/', '/[0-9]+/', '/[!@$%&.]+/'];

    public static function nameCheck(string $name): bool
    {
        return strlen($name) >= self::NAME_MIN_LENGTH  && strlen($name) <= self::NAME_MAX_LENGTH
            && preg_match(self::NAME_PATTERN, $name);
    }

    public static function passCheck(string $pass): bool
    {
        if (strlen($pass) >= self::PASS_MIN_LENGTH && strlen($pass) <= self::PASS_MAX_LENGTH) {
            foreach (self::PASS_PATTERNS as $pattern) {
                if (!preg_match($pattern, $pass))
                    return false;
            }
            return true;
        } else
            return false;
    }

    public static function nameTrim(string $name): string {
    	$spacesPattern = '/\s+/';
    	$result = trim($name);
    	$result = preg_replace($spacesPattern, ' ', $result);
    	return $result;
    }
}
