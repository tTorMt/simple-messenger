<?php

declare(strict_types=1);

namespace tTorMt\SChat\Auth;

/**
 * Handles sending email verification and password reset links.
 */
class MailHandler
{
    private string $email;
    /**
     * Initializes with an email address to send links to.
     *
     * @param string $email The recipient's email address.
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }

    /**
     * Sends an email verification link
     *
     * @param string $linkHash
     * @return bool
     */
    public function sendVerificationLink(string $linkHash): bool
    {
        // TODO: Implement sendVerificationLink() method.
        return true;
    }

    /**
     * Sends an email with a password reset link.
     *
     * @param string $linkHash
     * @return bool
     */
    public function sendResetPasswordLink(string $linkHash): bool
    {
        // TODO: Implement sendResetPasswordLink() method.
        return true;
    }
}
