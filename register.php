<?php
session_start();
require 'db_connect.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $confirm = $_POST['confirmPassword'];

    if (empty($name)) $errors[] = "Name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email.";
    if (strlen($pass) < 6) $errors[] = "Password must be ≥ 6 characters.";
    if ($pass !== $confirm) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email already registered.";
        } else {
            $hashed = password_hash($pass, PASSWORD_BCRYPT);
            $ins = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $name, $email, $hashed);
            if ($ins->execute()) {
                $_SESSION['success'] = "Registration successful! You may now login.";
                header("Location: login.php");
                exit;
            } else {
                $errors[] = "Registration error.";
            }
        }
    }
}
?>
