<?php
/**
 * Minimal outgoing email via PHP's built-in mail(). No SMTP library is
 * used, matching the "no Composer dependencies" constraint - on Hostinger
 * shared hosting, mail() is wired to their outgoing mail service already.
 */

function send_mail_message(string $to, string $subject, string $bodyText, ?string $replyTo = null): bool
{
    $fromAddress = defined('MAIL_FROM_ADDRESS') && MAIL_FROM_ADDRESS !== ''
        ? MAIL_FROM_ADDRESS
        : 'no-reply@' . preg_replace('~^https?://~', '', rtrim(SITE_URL, '/'));
    $fromName = defined('MAIL_FROM_NAME') && MAIL_FROM_NAME !== '' ? MAIL_FROM_NAME : setting('site_title', 'English Badi');

    $headers = [];
    $headers[] = 'From: ' . mail_encode_header($fromName) . ' <' . $fromAddress . '>';
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    try {
        $ok = @mail($to, mail_encode_header($subject), $bodyText, implode("\r\n", $headers));
        if (!$ok) {
            app_log("mail() returned false sending to {$to} / subject: {$subject}");
        }
        return $ok;
    } catch (Throwable $e) {
        app_log('Mail send failed: ' . $e->getMessage());
        return false;
    }
}

function mail_encode_header(string $text): string
{
    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}
