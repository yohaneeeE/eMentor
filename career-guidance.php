<?php
session_start();

// DB connection
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "careerguidance";

$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname, 3307);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check login state

// Check login state
$isLoggedIn = isset($_SESSION['fullName']);
$fullName = $isLoggedIn ? $_SESSION['fullName'] : null;
// Fetch careers
$sql = "SELECT id, title, category, description, skills FROM careers ORDER BY category, title";
$result = $conn->query($sql);

$careers = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $careers[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Career Guidance - eMentor</title>
<style>
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
        .sidebar .user-info {
        margin-top:auto; /* push to bottom */
        padding-top:15px;
        border-top:1px solid rgba(255,255,255,0.2);
        font-size:0.95rem;
        color:#ffcc00;
        text-align:center;
    }
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

    /* Hamburger button */
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
    .hamburger span {
        height: 4px; background: white; border-radius: 2px;
        transition: all 0.3s ease;
    }
    .hamburger:hover { transform: scale(1.1); }

    /* Hamburger active → X */
    .hamburger.active span:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }
    .hamburger.active span:nth-child(2) {
        opacity: 0;
    }
    .hamburger.active span:nth-child(3) {
        transform: rotate(-45deg) translate(6px, -6px);
    }

    /* Sidebar Menu */
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
    .sidebar a {
        color: white; text-decoration: none;
        font-size: 1.1rem; padding: 8px 0;
        transition: color 0.3s ease, transform 0.2s ease;
        display: block;
    }
    .sidebar a:hover {
        color: #ffcc00;
        transform: translateX(5px);
    }

    /* Overlay */
    .overlay {
        position: fixed; top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.4);
        opacity: 0; visibility: hidden;
        transition: opacity 0.3s ease;
        z-index: 100;
    }
    .overlay.active { opacity: 1; visibility: visible; }

    .container {
        flex: 1;
        max-width: 1200px;
        margin: 40px auto;
        padding: 30px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }

    h2 {
        color: #444;
        margin-bottom: 20px;
        text-align: center;
        font-size: 1.8rem;
        position: relative;
        padding-bottom: 10px;
    }
    h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: linear-gradient(90deg, #666, #ffcc00);
        border-radius: 3px;
    }

    .career-card {
        border: 1px solid #ccc;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 12px;
        background: #fff;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        opacity: 1; /* visible immediately */
    }
    .career-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.15);
    }
    .career-title { font-size: 1.3rem; font-weight: bold; color: #444; }
    .career-category {
        font-weight: bold;
        color: #ffcc00;
        margin-bottom: 10px;
        display: inline-block;
        background: #555;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.9rem;
    }

    footer {
        text-align: center;
        padding: 20px;
        background: linear-gradient(135deg, #444, #666);
        color: white;
        font-size: 0.9rem;
        margin-top: auto; /* sticky footer */
    }
    .footer-links {
        margin-bottom: 12px;
        display: flex;
        justify-content: center;
        gap: 20px;
    }
    .footer-links a {
        color: #ffcc00;
        text-decoration: none;
        transition: color 0.3s ease;
    }
    .footer-links a:hover { color: white; }
</style>
</head>
<body>

<header>
    <div class="hamburger" id="hamburger">
        <span></span><span></span><span></span>
    </div>
    <h1>eMentor</h1>
    <p>Explore various career paths and guidance</p>
</header>

<!-- Sidebar -->
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
    <h2>Available Careers</h2>
    <?php if (empty($careers)): ?>
        <p style="text-align:center; color:#999;">No career data available at the moment.</p>
    <?php else: ?>
        <?php foreach ($careers as $career): ?>
            <div class="career-card">
                <div class="career-title"><?= htmlspecialchars($career['title']) ?></div>
                <div class="career-category"><?= htmlspecialchars($career['category']) ?></div>
                <div class="career-description"><?= nl2br(htmlspecialchars($career['description'])) ?></div>
                <div class="career-skills"><strong>Skills:</strong> <?= htmlspecialchars($career['skills']) ?></div>
                <a href="career-roadmap.php?career_id=<?= $career['id'] ?>">View Roadmap →</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</main>

<footer>
    <div class="footer-links">
        <a href="privacy.html">Privacy Policy</a>
        <a href="terms.html">Terms of Service</a>
        <a href="contact.html">Contact Us</a>
    </div>
    <p>&copy; 2025 eMentor. All rights reserved.</p>
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
