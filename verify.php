<?php
session_start();
include 'db_connect.php';
include 'register_mail.php';

if (!isset($_SESSION['pending_verification_email'])) {
    header("Location: register.php");
    exit;
}

$email = $_SESSION['pending_verification_email'];
if (!isset($_SESSION['attempts'])) $_SESSION['attempts'] = 0;

function js_alert($msg, $redirect=null){
    $js = "<script>alert(".json_encode($msg).");";
    if($redirect) $js .= "window.location=".json_encode($redirect).";";
    else $js .= "window.location='verify.php';";
    $js .= "</script>";
    echo $js; exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ===== Verify Code =====
    if(isset($_POST['verify'])){
        $inputCode = preg_replace('/\D/','',$_POST['verification_code']);

        $stmt = $conn->prepare("SELECT fullName,password,verification_code FROM pending_users WHERE email=? LIMIT 1");
        $stmt->bind_param("s",$email);
        $stmt->execute();
        $stmt->bind_result($fullName,$hashedPassword,$storedCode);
        $hasRow = $stmt->fetch();
        $stmt->close();

        if(!$hasRow) {
            session_destroy();
            js_alert("No pending verification. Please register again.","register.php");
        }

        $_SESSION['attempts']++;

        // convert both to string for safe comparison
        if((string)$inputCode === (string)$storedCode){
            // Insert into users
            $stmt = $conn->prepare("INSERT INTO users (fullName,email,password,is_verified) VALUES (?,?,?,1)");
            $stmt->bind_param("sss",$fullName,$email,$hashedPassword);
            if($stmt->execute()){
                $userId = $stmt->insert_id;
                $stmt->close();

                // delete from pending_users
                $del = $conn->prepare("DELETE FROM pending_users WHERE email=?");
                $del->bind_param("s",$email);
                $del->execute();
                $del->close();

                // set session and redirect
                $_SESSION['user_id'] = $userId;
                $_SESSION['fullName'] = $fullName;
                $_SESSION['email'] = $email;
                $_SESSION['is_verified'] = 1;
                unset($_SESSION['attempts'], $_SESSION['pending_verification_email']);

                js_alert("Account successfully created! Please login to continue.","login.php");
                exit;
            } else {
                js_alert("Error creating account. Try again.","verify.php");
            }
        } else {
            if($_SESSION['attempts'] >= 3){
                $del = $conn->prepare("DELETE FROM pending_users WHERE email=?");
                $del->bind_param("s",$email);
                $del->execute();
                $del->close();
                session_destroy();
                js_alert("Verification failed 3 times. Please register again.","register.php");
            } else {
                $remain = 3 - $_SESSION['attempts'];
                js_alert("Incorrect code. $remain attempt(s) left.");
            }
        }
    }

    // ===== Resend Code =====
    if(isset($_POST['resend'])){
        $newCode = strval(rand(100000,999999));

        $up = $conn->prepare("UPDATE pending_users SET verification_code=?, created_at=NOW() WHERE email=?");
        $up->bind_param("ss",$newCode,$email);
        $up->execute();
        $up->close();

        // fetch full name
        $stmt = $conn->prepare("SELECT fullName FROM pending_users WHERE email=? LIMIT 1");
        $stmt->bind_param("s",$email);
        $stmt->execute();
        $stmt->bind_result($fullName);
        $stmt->fetch();
        $stmt->close();

        if(function_exists('sendVerificationEmail')){
            sendVerificationEmail($fullName,$email,$newCode);
            echo "<script>alert('New verification code sent to your email.');</script>";
        } else echo "<script>alert('Failed to resend email.');</script>";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Verify Email | eMentor</title>
<style>
body{font-family:sans-serif;background:#e6e6e6;}
.container{max-width:400px;margin:50px auto;background:#fff;padding:20px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.08);}
h2{text-align:center;color:grey;margin-bottom:20px;}
input{width:100%;padding:10px;margin-top:5px;border:1px solid #ddd;border-radius:5px;}
button{margin-top:15px;width:100%;padding:10px;background:#ffcc00;color:#004080;border:none;border-radius:5px;font-weight:600;cursor:pointer;transition: all 0.3s ease;}
button:hover{background:#e6b800;}
</style>
</head>
<body>
<div class="container">
<h2>Enter Verification Code</h2>
<p>A verification code was sent to <strong><?php echo htmlspecialchars($email); ?></strong></p>
<form method="post">
<input type="text" name="verification_code" placeholder="6-digit code" maxlength="6" required>
<button type="submit" name="verify">Verify & Continue</button>
<button type="submit" name="resend">Resend Code</button>
</form>
</div>
</body>
</html>
