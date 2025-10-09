<?php
session_start();
include 'db_connect.php';

// Check login state
$isLoggedIn = isset($_SESSION['fullName']);
$fullName = $isLoggedIn ? $_SESSION['fullName'] : null;

// ❌ Redirect unverified users
if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $fullName);
    $stmt->execute();
    $stmt->bind_result($isVerified);
    $stmt->fetch();
    $stmt->close();

    if (!$isVerified) {
        header("Location: verify.php");
        exit;
    }
} else {
    // Not logged in → redirect to login
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Dashboard - Digital Career Guidance</title>
<style>
    /* your existing styles unchanged */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { height: 100%; }
    body {
        display: flex;
        flex-direction: column;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #e6e6e6;
        color: #333;
        line-height: 1.6;
    }
    main { flex: 1; }
    header {
        background: linear-gradient(135deg, #444, #666);
        color: white;
        padding: 20px;
        text-align: center;
        position: relative;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    header h1 { font-size: 2rem; }
    header p { font-size: 1rem; opacity: 0.9; }
    .hamburger {
        position: absolute;
        top: 20px; left: 20px;
        width: 30px; height: 22px;
        display: flex; flex-direction: column;
        justify-content: space-between;
        cursor: pointer;
        transition: transform 0.3s ease;
        z-index: 300;
    }
    .hamburger span { height: 4px; background: white; border-radius: 2px; transition: all 0.3s ease; }
    .hamburger:hover { transform: scale(1.1); }
    .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
    .hamburger.active span:nth-child(2) { opacity: 0; }
    .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(6px, -6px); }
    .sidebar {
        position: fixed; top: 0; left: -250px;
        width: 250px; height: 100%;
        background: #333;
        color: white; padding: 60px 20px;
        transition: left 0.3s ease;
        display: flex; flex-direction: column;
        gap: 20px;
        z-index: 200;
    }
    .sidebar.active { left: 0; }
    .sidebar a { color: white; text-decoration: none; font-size: 1.1rem; padding: 8px 0; display: block; transition: color 0.3s ease, transform 0.2s ease; }
    .sidebar a:hover { color: #ffcc00; transform: translateX(5px); }
    .sidebar .user-info { margin-top:auto; padding-top:15px; border-top:1px solid rgba(255,255,255,0.2); font-size:0.95rem; color:#ffcc00; text-align:center; }
    .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); opacity: 0; visibility: hidden; transition: opacity 0.3s ease; z-index: 100; }
    .overlay.active { opacity: 1; visibility: visible; }
    .container { flex: 1; max-width: 1200px; margin: 40px auto; padding: 30px; background: white; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    h2 { color: #444; margin-bottom: 20px; text-align: center; font-size: 1.8rem; position: relative; padding-bottom: 10px; }
    h2::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 80px; height: 3px; background: linear-gradient(90deg, #666, #ffcc00); border-radius: 3px; }
    footer { text-align: center; padding: 20px; background: linear-gradient(135deg, #444, #666); color: white; font-size: 0.9rem; margin-top: auto; }
    .footer-links { margin-bottom: 12px; display: flex; justify-content: center; gap: 20px; }
    .footer-links a { color: #ffcc00; text-decoration: none; transition: color 0.3s ease; }
    .footer-links a:hover { color: white; }
</style>
</head>
<body>

<header>
    <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
    <h1>eMentor</h1>
    <p>Your personalized IT career dashboard</p>
</header>

<div class="sidebar" id="sidebar">
    <a href="dashboard.php">Home</a>
    <a href="career-guidance.php">Career Guidance</a>
    <a href="careerpath.php">Career Path</a>
    <a href="about.php">About</a>
    <hr style="border: 1px solid rgba(255,255,255,0.2);">
    <?php if ($isLoggedIn): ?>
        <a href="settings.php">Settings</a>
        <a href="logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
        <div class="user-info">
            Logged in as <br><strong><?php echo htmlspecialchars($fullName); ?></strong>
        </div>
    <?php else: ?>
        <a href="login.php">Login</a>
    <?php endif; ?>
</div>

<div class="overlay" id="overlay"></div>

<main>
<div class="container">
    <section class="description">
        <h2>Welcome to Your Dashboard</h2>
        <p style="text-align:center; max-width:800px; margin:auto;">
            This dashboard is your hub for navigating eMentor’s resources. 
            From discovering tailored career paths to gaining insights into the tech industry's 
            most in-demand skills, you're in the right place to plan your future in Information Technology.
        </p>
    </section>
</div>
</main>

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
const hamburger = document.getElementById('hamburger');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    hamburger.classList.toggle('active');
});
overlay.addEventListener('click', () => {
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
    hamburger.classList.remove('active');
});
</script>

</body>
</html>
