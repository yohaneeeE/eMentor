<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ✅ Correct paths (case-sensitive on most servers)
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

/**
 * Sends a welcome email to a newly registered user.
 *
 * @param string $fullName Full name of the user
 * @param string $email User's email address
 * @return bool True on success, false on failure
 */
function sendWelcomeEmail($fullName, $email) {
    $mail = new PHPMailer(true);

    try {
        // ✅ SMTP Configuration (Gmail recommended)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ementorguidance@gmail.com';   // 🔹 Replace with your Gmail
        $mail->Password   = 'YOUR_APP_PASSWORD_HERE';      // 🔹 Replace with Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // ✅ Email Headers
        $mail->setFrom('ementorguidance@gmail.com', 'eMentor CITE');
        $mail->addAddress($email, $fullName);
        $mail->addReplyTo('ementorguidance@gmail.com', 'eMentor Support');

        // ✅ Email Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to eMentor!';
        $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <h2>Hello, $fullName!</h2>
                <p>🎉 Welcome to <strong>eMentor</strong> — your personal guide for career growth and discovery.</p>
                <p>Your account has been successfully created. You can now log in and explore data-driven career guidance tools designed just for you.</p>
                <br>
                <p>Best regards,<br>
                <strong>The eMentor Team</strong></p>
                <hr>
                <small>This is an automated email — please do not reply directly.</small>
            </body>
            </html>
        ";

        // ✅ Send Email
        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mailer Error ({$email}): " . $mail->ErrorInfo);
        return false;
    }
}
?>
