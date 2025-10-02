<?php
session_start();

include 'db_connect.php';
// Initialize variables for messages
$registerSuccess = '';
$registerError = '';
$loginError = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Basic validation
    if (!$fullName || !$email || !$password || !$confirmPassword) {
        $registerError = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerError = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $registerError = "Password must be at least 6 characters.";
    } elseif ($password !== $confirmPassword) {
        $registerError = "Passwords do not match.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $registerError = "Email is already registered.";
        } else {
            // Insert user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (fullName, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $fullName, $email, $hashedPassword);
            if ($stmt->execute()) {
                $registerSuccess = "Account created successfully! You can now login.";
            } else {
                $registerError = "Registration failed: " . $conn->error;
            }
        }
        $stmt->close();
    }
}

// Handle login
// ... your existing code above ...

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $loginError = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, fullName, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $fullName, $hashedPassword);
            $stmt->fetch();
            if (password_verify($password, $hashedPassword)) {
                // Login success, set session
                $_SESSION['user_id'] = $id;
                $_SESSION['fullName'] = $fullName;  // <-- store fullName here

                // Redirect to dashboard or index
                header("Location: dashboard.php");
                exit;
            } else {
                $loginError = "Invalid credentials.";
            }
        } else {
            $loginError = "Invalid credentials.";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - Digital Career Guidance</title>
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f9f9f9;
    color: #333;
    line-height: 1.6;
  }

  header {
    background: linear-gradient(135deg, #004080, #0066cc);
    color: white;
    padding: 25px 0;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  header h1 {
    font-size: 2.5rem;
    margin-bottom: 10px;
  }

  header p {
    font-size: 1.1rem;
    opacity: 0.9;
  }

  .container {
    max-width: 500px;
    margin: 50px auto;
    padding: 40px 30px;
    background: #ffffff;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  }

  h2 {
    color: #004080;
    margin-bottom: 25px;
    text-align: center;
    font-size: 1.8rem;
    position: relative;
    padding-bottom: 15px;
  }

  h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: linear-gradient(90deg, #004080, #ffcc00);
    border-radius: 3px;
  }

  .form-toggle {
    display: flex;
    justify-content: center;
    margin-bottom: 25px;
  }

  .toggle-btn {
    flex: 1;
    padding: 12px 0;
    cursor: pointer;
    background: #eee;
    border: none;
    outline: none;
    transition: 0.3s;
    font-weight: 600;
    color: #004080;
  }

  .toggle-btn.active {
    background: #004080;
    color: #fff;
  }

  .form-container form {
    display: none;
  }

  .form-container form.active {
    display: block;
  }

  .form-group {
    margin-bottom: 20px;
  }

  label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #333;
  }

  input[type="email"],
  input[type="password"],
  input[type="text"] {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
  }

  input:focus {
    border-color: #004080;
    outline: none;
  }

  .submit-btn {
    background: #004080;
    color: white;
    border: none;
    padding: 12px 0;
    width: 100%;
    font-size: 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s ease;
  }

  .submit-btn:hover {
    background: #003060;
  }

  .forgot-password {
    text-align: right;
    margin-top: 10px;
  }

  .forgot-password a {
    color: #004080;
    text-decoration: none;
    font-size: 0.9rem;
  }

  .forgot-password a:hover {
    text-decoration: underline;
  }

  .divider {
    text-align: center;
    margin: 30px 0;
    position: relative;
  }

  .divider span {
    background: #fff;
    padding: 0 15px;
    position: relative;
    z-index: 2;
    font-weight: bold;
    color: #666;
  }

  .divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 5%;
    right: 5%;
    height: 1px;
    background: #ccc;
    z-index: 1;
  }

  .guest-login {
    text-align: center;
  }

  .guest-btn {
    background: #ffcc00;
    color: #004080;
    padding: 10px 25px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: background 0.3s ease;
  }

  .guest-btn:hover {
    background: #e6b800;
  }

  footer {
    text-align: center;
    padding: 30px 0;
    background: linear-gradient(135deg, #003060, #004080);
    color: white;
    font-size: 0.95em;
    margin-top: 60px;
  }

  .footer-links {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 15px;
    flex-wrap: wrap;
  }

  .footer-links a {
    color: #ffcc00;
    text-decoration: none;
    transition: color 0.3s ease;
  }

  .footer-links a:hover {
    color: white;
  }

  @media (max-width: 600px) {
    .container {
      margin: 20px;
      padding: 20px;
    }

    .toggle-btn {
      font-size: 0.9rem;
    }
  }
</style>

</head>
<body>

<header>
  <h1>eMentor</h1>
  <p>Access your digital career guidance</p>
</header>

<div class="container">
  <h2 id="form-title">Login to Your Account</h2>

  <div class="form-toggle">
    <button class="toggle-btn active" onclick="showLogin()">Login</button>
    <button class="toggle-btn" onclick="showRegister()">Register</button>
  </div>

  <div class="form-container">
    <!-- Login Form -->
    <form class="login-form active" method="post" id="loginForm" novalidate>
      <?php if ($loginError): ?>
        <p style="color:#dc3545; margin-bottom: 15px;"><?php echo htmlspecialchars($loginError); ?></p>
      <?php endif; ?>
      <div class="form-group">
        <label for="loginEmail">Email Address/Student Number</label>
        <input type="email" id="loginEmail" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
      </div>

      <div class="form-group">
        <label for="loginPassword">Password</label>
        <input type="password" id="loginPassword" name="password" required>
      </div>

      <button type="submit" name="login" class="submit-btn">Login</button>

      <div class="forgot-password">
        <a href="#" onclick="alert('Password reset functionality would be implemented here. Please contact support for now.')">Forgot your password?</a>
      </div>
    </form>

    <!-- Registration Form -->
    <form class="register-form" method="post" id="registerForm" novalidate>
      <?php if ($registerError): ?>
        <p style="color:#dc3545; margin-bottom: 15px;"><?php echo htmlspecialchars($registerError); ?></p>
      <?php elseif ($registerSuccess): ?>
        <p style="color:#28a745; margin-bottom: 15px;"><?php echo htmlspecialchars($registerSuccess); ?></p>
      <?php endif; ?>

      <div class="form-group">
        <label for="registerName">Full Name</label>
        <input type="text" id="registerName" name="fullName" required value="<?php echo isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : ''; ?>">
      </div>

      <div class="form-group">
        <label for="registerEmail">Email Address/Student Number</label>
        <input type="email" id="registerEmail" name="email" required value="<?php echo isset($_POST['email']) && isset($_POST['register']) ? htmlspecialchars($_POST['email']) : ''; ?>">
      </div>

      <div class="form-group">
        <label for="registerPassword">Password</label>
        <input type="password" id="registerPassword" name="password" required>
      </div>

      <div class="form-group">
        <label for="confirmPassword">Confirm Password</label>
        <input type="password" id="confirmPassword" name="confirmPassword" required>
      </div>

      <button type="submit" name="register" class="submit-btn">Create Account</button>
    </form>
  </div>

  <div class="divider">
    <span>OR</span>
  </div>

  <div class="guest-login">
    <a href="dashboard.php" class="guest-btn">Continue as Guest</a>
  </div>
</div>

<footer>
  <div class="footer-links">
    <a href="privacy.html">Privacy Policy</a>
    <a href="terms.html">Terms of Service</a>
    <a href="contact.html">Contact Us</a>
  </div>
  <p>&copy; 2025 eMentor System. All rights reserved.</p>
  <p>Bulacan State University - Bustos Campus</p>
</footer>

<script>
  // Show Login/Register toggle logic

  function showLogin() {
    document.querySelector('.login-form').classList.add('active');
    document.querySelector('.register-form').classList.remove('active');
    document.querySelector('.toggle-btn:first-child').classList.add('active');
    document.querySelector('.toggle-btn:last-child').classList.remove('active');
    document.getElementById('form-title').textContent = 'Login to Your Account';
  }

  function showRegister() {
    document.querySelector('.register-form').classList.add('active');
    document.querySelector('.login-form').classList.remove('active');
    document.querySelector('.toggle-btn:last-child').classList.add('active');
    document.querySelector('.toggle-btn:first-child').classList.remove('active');
    document.getElementById('form-title').textContent = 'Create New Account';
  }

  // Auto-show registration form if there were registration errors or success
  <?php if ($registerError || $registerSuccess): ?>
    showRegister();
  <?php endif; ?>

</script>

</body>
</html>
