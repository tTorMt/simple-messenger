<?php

declare(strict_types=1);

namespace tTorMt\SChat\Auth;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Handles sending email verification and password reset links.
 */
class MailHandler
{
    private PHPMailer $mailer;
    private string $domainName;

    /**
     * Initializes with an email address to send links to.
     *
     * @param string $email The recipient's email address.
     * @throws Exception
     */
    public function __construct(string $email)
    {
        $config = parse_ini_file(__DIR__.'/../../config/config.ini');
        $this->domainName = $config['domain_name'];

        $mailer = new PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $config['smtp_server'];
        $mailer->SMTPAuth = true;
        $mailer->Username = $config['mail_username'];
        $mailer->Password = $config['mail_password'];
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mailer->Port = $config['mail_port'];
        $mailer->setFrom($config['mail_username'], 'Simple-Messenger');
        $mailer->addAddress($email);
        $mailer->isHTML();
        $this->mailer = $mailer;
    }

    /**
     * Sends an email verification link
     *
     * @param string $linkHash
     * @return bool
     * @throws Exception
     */
    public function sendVerificationLink(string $linkHash): bool
    {
        $this->mailer->Subject = 'Simple-Messenger email verification';
        $this->mailer->Body = "<h1>Email verification</h1><a href='".$this->domainName."/verifyEmail?emailVerificationToken=$linkHash"."'>Email verification link</a>";
        return $this->mailer->send();
    }

    /**
     * Sends an email with a password reset link.
     *
     * @param string $linkHash
     * @return bool
     * @throws Exception
     */
    public function sendResetPasswordLink(string $linkHash): bool
    {
        $this->mailer->Subject = 'Simple-Messenger password change';
        $this->mailer->Body = "<h1>Password change</h1><a href='".$this->domainName."/changePassword?changePassToken=$linkHash"."'>Change password link</a>";
        return $this->mailer->send();
    }
}
