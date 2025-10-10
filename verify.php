<?php
session_start();
include 'db_connect.php';
include 'register_mail.php'; // must provide sendVerificationEmail($fullName, $email, $code)

// If someone visits this page without a pending email, send them back to register
if (!isset($_SESSION['pending_verification_email'])) {
    header("Location: login.php");

    exit;
}

$email = $_SESSION['pending_verification_email'];
if (!isset($_SESSION['attempts'])) $_SESSION['attempts'] = 0;

// Helper: show JS alert then optionally redirect
function js_alert($msg, $redirect = null) {
    $js = "<script>alert(" . json_encode($msg) . ");";
    if ($redirect) $js .= "window.location=" . json_encode($redirect) . ";";
    else $js .= "window.location='verify.php';";
    $js .= "</script>";
    echo $js;
    
    
    exit;
}

// ===== Verify submission =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $inputCode = trim($_POST['verification_code']);
    // normalize to digits only (optional)
    $inputCode = preg_replace('/\D/', '', $inputCode);

    // get pending user row
    $stmt = $conn->prepare("SELECT fullName, password, reset_phrase, verification_code FROM pending_users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($fullName, $hashedPassword, $resetPhrase, $storedCode);
    $hasRow = $stmt->fetch();
    $stmt->close();

    if (!$hasRow) {
        // nothing to verify — maybe already removed / expired
        session_unset();
        session_destroy();
        js_alert("No pending verification found. Please register again.", "register.php");
    }

    // increment attempts
    $_SESSION['attempts']++;

    // correct code
    if ($inputCode !== '' && $inputCode === (string)$storedCode) {

        // check if user already exists in users table
        $check = $conn->prepare("SELECT id, fullName FROM users WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // user exists — fetch id & name, ensure is_verified = 1
            $check->bind_result($existingId, $existingName);
            $check->fetch();
            $check->close();

            // mark verified in users table (in case it wasn't)
            $upd = $conn->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
            $upd->bind_param("s", $email);
            $upd->execute();
            $upd->close();

            // delete pending_users
            $del = $conn->prepare("DELETE FROM pending_users WHERE email = ?");
            $del->bind_param("s", $email);
            $del->execute();
            $del->close();

            // set session (log in)
            $_SESSION['user_id'] = $existingId;
            $_SESSION['fullName'] = $existingName;
            $_SESSION['email'] = $email;
            $_SESSION['is_verified'] = 1;

            // cleanup
            unset($_SESSION['attempts']);
            unset($_SESSION['pending_verification_email']);

            // success → dashboard
            header("Location: dashboard.php");
            exit;
        }

        // user does not exist yet — insert
        $insert = $conn->prepare("INSERT INTO users (fullName, email, password, reset_phrase, is_verified) VALUES (?, ?, ?, ?, 1)");
        $insert->bind_param("ssss", $fullName, $email, $hashedPassword, $resetPhrase);
        if (!$insert->execute()) {
            // possible race/duplicate — try to recover by selecting existing user
            $insert->close();
            $recover = $conn->prepare("SELECT id, fullName FROM users WHERE email = ? LIMIT 1");
            $recover->bind_param("s", $email);
            $recover->execute();
            $recover->store_result();
            if ($recover->num_rows > 0) {
                $recover->bind_result($rid, $rname);
                $recover->fetch();
                $recover->close();

                // delete pending and login the existing user
                $del = $conn->prepare("DELETE FROM pending_users WHERE email = ?");
                $del->bind_param("s", $email);
                $del->execute();
                $del->close();

                $_SESSION['user_id'] = $rid;
                $_SESSION['fullName'] = $rname;
                $_SESSION['email'] = $email;
                $_SESSION['is_verified'] = 1;

                unset($_SESSION['attempts']);
                unset($_SESSION['pending_verification_email']);
                header("Location: index.php");
                exit;
            } else {
                js_alert("An unexpected error occurred while creating your account. Please contact support.", "register.php");
            }
        } else {
            // success insert
            $newUserId = $insert->insert_id;
            $insert->close();

            // delete from pending_users
            $del = $conn->prepare("DELETE FROM pending_users WHERE email = ?");
            $del->bind_param("s", $email);
            $del->execute();
            $del->close();

            // set session and redirect to dashboard
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['fullName'] = $fullName;
            $_SESSION['email'] = $email;
            $_SESSION['is_verified'] = 1;

            unset($_SESSION['attempts']);
            unset($_SESSION['pending_verification_email']);
            header("Location: dashboard.php");
            exit;
        }
    } else {
        // code mismatch
        if ($_SESSION['attempts'] >= 3) {
            // remove pending record and force re-register
            $stmt = $conn->prepare("DELETE FROM pending_users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->close();

            session_unset();
            session_destroy();
            js_alert("Verification failed 3 times. Please register again.", "register.php");
        } else {
            $remaining = 3 - $_SESSION['attempts'];
            js_alert("Incorrect code. You have $remaining attempt(s) left.");
        }
    }
}

// ===== Resend code handler =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
    // generate new code
    $newCode = strval(rand(100000, 999999));

    // update pending_users
    $up = $conn->prepare("UPDATE pending_users SET verification_code = ?, created_at = NOW() WHERE email = ?");
    $up->bind_param("ss", $newCode, $email);
    $up->execute();
    $up->close();

    // fetch full name
    $stmt = $conn->prepare("SELECT fullName FROM pending_users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($fullName);
    $stmt->fetch();
    $stmt->close();

    // send email (register_mail.php's function)
    if (function_exists('sendVerificationEmail')) {
        sendVerificationEmail($fullName, $email, $newCode);
        echo "<script>alert('A new verification code has been sent to your email.');</script>";
    } else {
        echo "<script>alert('Resend failed: mailer not available.');</script>";
    }
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verify Email | eMentor</title>
<style>
body { font-family: Arial, sans-serif; background: #e6e6e6; }
.container { max-width: 420px; margin: 80px auto; background: #fff; padding: 28px; border-radius: 10px; box-shadow:0 4px 12px rgba(0,0,0,0.12); text-align:center; }
input[type="text"] { width:100%; padding:10px; margin:10px 0; border:1px solid #ccc; border-radius:6px; }
button { width:100%; padding:10px; background:#ffcc00; border:none; border-radius:6px; font-weight:700; cursor:pointer; }
button:hover { background:#e6b800; }
button.resend { background:#007bff; color:#fff; margin-top:8px; }
button.resend:hover { background:#0062cc; }
</style>
</head>
<body>
<div class="container">
    <h2>Enter Verification Code</h2>
    <p>A verification code was sent to <strong><?php echo htmlspecialchars($email); ?></strong></p>

    <form method="post" novalidate>
        <input type="text" name="verification_code" placeholder="6-digit code" maxlength="6" autocomplete="one-time-code" required>
        <button type="submit" name="verify">Verify & Continue</button>
        <button type="submit" name="resend" class="resend">Resend Code</button>
    </form>
</div>
</body>
</html>
