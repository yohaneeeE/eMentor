<?php
session_start();

$adminLoginMessage = "";

// ---------------------- SIMPLE ADMIN LOGIN ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adminEmail'])) {
    $email = trim($_POST['adminEmail'] ?? '');
    $password = $_POST['adminPassword'] ?? '';

    // Simple check (hardcoded admin credentials)
    if ($email === "admin" && $password === "admin") {
        $_SESSION['admin_id'] = 1;
        $_SESSION['adminName'] = "Administrator";
        header("Location: dashboard.php");
        exit;
    } else {
        $adminLoginMessage = "❌ Invalid admin credentials.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>eMentor - Admin Login</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f7fa;
      margin: 0;
      padding: 0;
    }
    header {
      background: linear-gradient(135deg, #004080, #0066cc);
      color: white;
      padding: 25px 0;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }  
    .container {
      max-width: 500px;
      margin: 30px auto;
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #004080;
    }
    .form-group {
      margin-bottom: 15px;
    }
    label {
      display: block;
      font-weight: bold;
      margin-bottom: 5px;
    }
    input[type="text"], input[type="email"], input[type="password"] {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
    }
    button {
      background: #004080;
      color: #fff;
      border: none;
      padding: 12px;
      width: 100%;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
    }
    button:hover {
      background: #660000;
    }
    .message {
      text-align: center;
      margin: 10px 0;
      font-weight: bold;
      color: red;
    }
    @media (max-width: 600px) {
      .container { margin: 10px; padding: 15px; }
    }
  </style>
</head>
<body>
<header>
  <h1>eMentor</h1>
  <p>Admin Control Panel</p>
</header>

<div class="container">
  <form method="post">
    <h2>Admin Login</h2>
    <div class="form-group">
      <label>Email or Username</label>
      <input type="text" name="adminEmail" required>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="adminPassword" required>
    </div>
    <button type="submit">Login</button>
    <p class="message"><?= $adminLoginMessage ?></p>
  </form>
</div>
</body>
</html>
