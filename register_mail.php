<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

/**
 * Sends a verification email to user after registration
 *
 * @param string $fullName
 * @param string $email
 * @param string $verificationCode
 * @return bool
 */
function sendVerificationEmail($fullName, $email, $verificationCode) {
    $mail = new PHPMailer(true);

    try {
        // ✅ SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ytrbulsubustosofficial@gmail.com';  
        $mail->Password   = 'rrlo ayyo uxfo uwks';  // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // ✅ Headers
        $mail->setFrom('ementorguidance@gmail.com', 'eMentor CITE');
        $mail->addAddress($email, $fullName);
        $mail->addReplyTo('ementorguidance@gmail.com', 'eMentor Support');

        // ✅ Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your eMentor Account';

        $mail->Body = "
        <html>
        <body style='margin:0; padding:0; font-family:Segoe UI, Arial, sans-serif; background-color:#f2f2f2;'>
            <table align='center' width='100%' cellpadding='0' cellspacing='0' style='background-color:#f2f2f2; padding:30px 0;'>
                <tr>
                    <td>
                        <table align='center' width='600' cellpadding='0' cellspacing='0' style='background-color:#ffffff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); overflow:hidden;'>
                            <tr>
                                <td style='background:linear-gradient(135deg, #555, #777); color:white; text-align:center; padding:25px;'>
                                    <h1 style='margin:0; font-size:26px; letter-spacing:0.5px;'>eMentor</h1>
                                    <p style='margin:5px 0 0; font-size:14px; opacity:0.9;'>Career Guidance & Mentorship Platform</p>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding:30px 40px; color:#333;'>
                                    <h2 style='margin-top:0; font-weight:600; color:#444;'>Hello, $fullName 👋</h2>
                                    <p style='font-size:15px; line-height:1.6; color:#555;'>
                                        Thank you for registering with <strong>eMentor</strong>! To activate your account, please use the verification code below:
                                    </p>
                                    <div style='margin:25px 0; text-align:center;'>
                                        <div style='display:inline-block; background-color:#eee; color:#333; font-size:28px; letter-spacing:4px; font-weight:bold; padding:15px 25px; border-radius:8px; border:1px solid #ccc;'>
                                            $verificationCode
                                        </div>
                                    </div>
                                    <p style='font-size:14px; color:#666;'>
                                        Enter this code on the <strong>Verification Page</strong> to complete your registration.
                                    </p>
                                    <p style='font-size:14px; color:#666; margin-top:25px;'>
                                        If you didn’t request this, you can safely ignore this email.
                                    </p>
                                    <p style='margin-top:35px; font-size:14px; color:#555;'>
                                        Best regards,<br>
                                        <strong>The eMentor Team</strong>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style='background-color:#f9f9f9; text-align:center; padding:15px 20px; font-size:12px; color:#999; border-top:1px solid #ddd;'>
                                    <p style='margin:0;'>This is an automated message — please do not reply.</p>
                                    <p style='margin:5px 0 0;'>© " . date('Y') . " eMentor CITE. All rights reserved.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer Error (' . $email . '): ' . $mail->ErrorInfo);
        return false;
    }
}
?>
