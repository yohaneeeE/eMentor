<?php
session_start();

include 'db_connect.php';
// Check login state
$isLoggedIn = isset($_SESSION['fullName']);
$fullName = $isLoggedIn ? $_SESSION['fullName'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard - Digital Career Guidance</title>
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
            position: relative;
        }
        header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 30px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-1px);
        }
        nav {
            background-color: #003060;
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        nav ul {
            list-style: none;
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 8px 15px;
            border-radius: 5px;
        }
        nav ul li a:hover {
            color: #ffcc00;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        h2 {
            color: #004080;
            margin-bottom: 25px;
            text-align: center;
            font-size: 2rem;
            position: relative;
            padding-bottom: 15px;
        }
        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, #004080, #ffcc00);
            border-radius: 3px;
        }
        .intro-text {
            font-size: 1.1em;
            margin-bottom: 40px;
            text-align: center;
            color: #555;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
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
        }
        .footer-links a {
            color: #ffcc00;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .footer-links a:hover {
            color: white;
        }
        @media (max-width: 768px) {
            nav ul {
                gap: 15px;
            }
            .logout-btn {
                position: static;
                margin-top: 15px;
                display: block;
                width: fit-content;
                margin-left: auto;
                margin-right: auto;
            }
            header {
                padding-bottom: 15px;
            }
        }
    </style>
</head>
<body>

<header>
<?php if (isset($_SESSION['fullName']) && $_SESSION['fullName'] !== 'Guest'): ?>
  <a class="logout-btn" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">
    🚪 Logout (<?= htmlspecialchars($_SESSION['fullName']) ?>)
  </a>
<?php else: ?>
  <a class="logout-btn" href="login.php">
    🔐 Login
  </a>
<?php endif; ?>

    <h1>eMentor</h1>
    <p>Welcome to your personalized IT career dashboard</p>

</header>

<nav>
  <ul>
    <li><a href="dashboard.php"  style="background-color:#ffcc00; color:#004080; font-weight:700;">Home</a></li>
    <li><a href="career-guidance.php">Career Guidance</a></li>
    <li><a href="careerpath.php" >Career Path</a></li>
    <li><a href="about.php">About</a></li>
  </ul>
</nav>

<div class="container">
    <section class="description">
        <h2>Welcome to Your Dashboard</h2>
        <p class="intro-text">
            This dashboard is your hub for navigating eMentor’s resources. From discovering tailored career paths to gaining insights into the tech industry's most in-demand skills, you're in the right place to plan your future in Information Technology.
        </p>
    </section>
</div>

<footer>
    <div class="footer-links">
        <a href="privacy.html">Privacy Policy</a>
        <a href="terms.html">Terms of Service</a>
        <a href="contact.html">Contact Us</a>
    </div>
    <p>&copy; 2025 eMentor. All rights reserved.</p>
    <p>Bulacan State University - Bustos Campus</p>
</footer>

<script>
    function confirmLogout() {
        return confirm('Are you sure you want to logout?');
    }
</script>

</body>
</html>
