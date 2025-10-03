<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resetEmail'])) {
    $email = trim($_POST['resetEmail']);
    $phrase = trim($_POST['resetPhrase']);
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    if (!$email || !$phrase || !$newPassword || !$confirmPassword) {
        echo "<script>alert('All fields are required.');</script>";
    } elseif ($newPassword !== $confirmPassword) {
        echo "<script>alert('Passwords do not match.');</script>";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_phrase = ?");
        $stmt->bind_param("ss", $email, $phrase);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->close();
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashedPassword, $email);
            if ($stmt->execute()) {
                echo "<script>alert(' Password reset successful!'); window.location='login.php';</script>";
            } else {
                echo "<script>alert(' Error updating password.');</script>";
            }
        } else {
            echo "<script>alert(' Invalid email or reset phrase.');</script>";
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <style>
    body {font-family: Arial; background:#f4f7fa;}
    .container {max-width:400px; margin:50px auto; background:#fff; padding:20px; border-radius:8px;}
    h2 {text-align:center; color:#004080;}
    label {font-weight:bold; display:block; margin-top:10px;}
    input {width:100%; padding:10px; margin-top:5px; border:1px solid #ddd; border-radius:5px;}
    button {margin-top:15px; width:100%; padding:10px; background:#004080; color:white; border:none; border-radius:5px;}
    button:hover {background:#003060;}
    .links {margin-top:15px; text-align:center;}
    .links a {color:#004080; text-decoration:none;}
  </style>
</head>
<body>
<div class="container">
  <h2>Reset Password</h2>
  <form method="post">
    <label>Email</label>
    <input type="email" name="resetEmail" required>
    <label>Reset Phrase</label>
    <input type="text" name="resetPhrase" required>
    <label>New Password</label>
    <input type="password" name="newPassword" required>
    <label>Confirm New Password</label>
    <input type="password" name="confirmPassword" required>
    <button type="submit">Reset Password</button>
  </form>
  <div class="links">
    <p><a href="login.php">Back to Login</a></p>
  </div>
</div>
</body>
</html>
