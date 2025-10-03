<?php
session_start();
include 'db_connect.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loginEmail'])) {
    $email = trim($_POST['loginEmail']);
    $password = $_POST['loginPassword'];

    $stmt = $conn->prepare("SELECT id, fullName, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $fullName, $hashedPassword);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['fullName'] = $fullName;
            header("Location: dashboard.php");
            exit;
        } else {
            echo "<script>alert('❌ Invalid password.');</script>";
        }
    } else {
        echo "<script>alert('❌ Email not found.');</script>";
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
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
  <h2>Login</h2>
  <form method="post">
    <label>Email</label>
    <input type="email" name="loginEmail" required>
    <label>Password</label>
    <input type="password" name="loginPassword" required>
    <button type="submit">Login</button>
  </form>
  <div class="links">
    <p><a href="register.php">Create Account</a> | <a href="reset.php">Forgot Password?</a></p>
  </div>
</div>
</body>
</html>
