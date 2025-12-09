<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'load_env.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

echo "--- Loading Configuration ---\n";
$host = getenv('SMTP_HOST');
$user = getenv('SMTP_USER');
$pass = getenv('SMTP_PASS');
$port = getenv('SMTP_PORT');

echo "Host: " . ($host ? $host : "NOT SET") . "\n";
echo "User: " . ($user ? $user : "NOT SET") . "\n";
// Mask password for display
echo "Pass: " . ($pass ? str_repeat('*', strlen($pass)) : "NOT SET") . "\n\n";

if (!$host || !$user || !$pass) {
    echo "ERROR: Missing required configuration in .env\n";
    exit(1);
}

$mail = new PHPMailer(true);

try {
    echo "--- Attempting Connection ---\n";
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;      // Enable verbose debug output
    $mail->isSMTP();
    $mail->Host       = $host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $user;
    $mail->Password   = $pass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $port;

    $mail->setFrom($user, 'Test Script');
    $mail->addAddress($user); // Send to self

    $mail->isHTML(false);
    $mail->Subject = 'Test Email from Trillos Records';
    $mail->Body    = 'If you receive this, your email configuration is correct!';

    $mail->send();
    echo "\n--- SUCCESS ---\n";
    echo "Email sent successfully to $user\n";
} catch (Exception $e) {
    echo "\n--- ERROR ---\n";
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
?>
