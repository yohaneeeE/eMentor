<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registerEmail'])) {
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['registerEmail']);
    $password = $_POST['registerPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    $resetPhrase = trim($_POST['resetPhrase']);

    if (!$fullName || !$email || !$password || !$confirmPassword || !$resetPhrase) {
        echo "<script>alert('All fields are required.');</script>";
    } elseif ($password !== $confirmPassword) {
        echo "<script>alert('Passwords do not match.');</script>";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "<script>alert(' Email already registered.');</script>";
        } else {
            $stmt->close();
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (fullName, email, password, reset_phrase) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $fullName, $email, $hashedPassword, $resetPhrase);
            if ($stmt->execute()) {
                echo "<script>alert(' Registration successful! You can login now.'); window.location='login.php';</script>";
            } else {
                echo "<script>alert(' Registration failed.');</script>";
            }
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
  <title>Register</title>
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
  <h2>Register</h2>
  <form method="post">
    <label>Full Name</label>
    <input type="text" name="fullName" required>
    <label>Email</label>
    <input type="email" name="registerEmail" required>
    <label>Password</label>
    <input type="password" name="registerPassword" required>
    <label>Confirm Password</label>
    <input type="password" name="confirmPassword" required>
    <label>Password Reset Phrase</label>
    <input type="text" name="resetPhrase" required placeholder="e.g. My first pet's name">
    <button type="submit">Register</button>
  </form>
  <div class="links">
    <p><a href="login.php">Back to Login</a></p>
  </div>
</div>
</body>
</html>
