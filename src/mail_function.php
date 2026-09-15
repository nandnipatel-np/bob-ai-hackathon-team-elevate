<?php
/**
 * CCP - Mail Configuration using PHPMailer
 * Path: mail_function.php
 */

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendMail($to, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'naiyapatel1405@gmail.com'; // Your Gmail
        $mail->Password   = 'emeq pnyf hwdh phpz'; // Put your real Gmail App Password here
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // ✅ better than plain 'tls'
        $mail->Port       = 587;

        // Optional debug (keep OFF in final project)
        $mail->SMTPDebug  = 0;

        // Fix for local XAMPP certificate issues
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Sender and Receiver
        $mail->setFrom('naiyapatel1405@gmail.com', 'CCP - Campus Canteen');
        $mail->addAddress($to);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        // Send mail
        if ($mail->send()) {
            return true;
        } else {
            return "Mail Error: " . $mail->ErrorInfo;
        }

    } catch (Exception $e) {
        return "Mailer Exception: " . $mail->ErrorInfo;
    }
}
?>