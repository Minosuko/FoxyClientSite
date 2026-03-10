<?php
require_once __DIR__ . '/Mailer/Mailer.php';

/**
 * Send an email using PHPMailer via the Mailer class.
 *
 * @param string $to      Recipient email address
 * @param string $subject Email subject
 * @param string $body    Email body (HTML supported)
 * @param bool   $isHTML  Whether the body is HTML (default: true)
 * @return bool           True on success, false on failure
 */
function send_mail($to, $subject, $body, $isHTML = true) {
    $config = require __DIR__ . '/../config/mail.php';
    
    $mailer = new Mailer(
        $config['host'],
        $config['port'],
        $config['secure'],
        $config['auth'],
        $config['username'],
        $config['password']
    );
    
    $fromName = isset($config['from_name']) ? $config['from_name'] : 'Foxy Client';
    
    return $mailer->send($to, $subject, $body, [
        'isHTML' => $isHTML,
        'From'   => $fromName,
        'to'     => $to
    ]);
}
?>
