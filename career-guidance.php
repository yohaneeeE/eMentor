<?php
session_start();

// DB connection
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "em_mentor";

$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname, 3307);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check login state
$isLoggedIn = isset($_SESSION['fullName']);
$fullName   = $isLoggedIn ? $_SESSION['fullName'] : null;

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
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Career Guidance</title>
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
    .career-card {
        border: 1px solid #ddd;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        transition: box-shadow 0.3s ease;
        background: #fafafa;
    }
    .career-card:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.12);
    }
    .career-title {
        color: #004080;
        font-weight: 700;
        font-size: 1.5rem;
        margin-bottom: 5px;
    }
    .career-category {
        font-weight: 600;
        color: #ffcc00;
        margin-bottom: 12px;
        display: inline-block;
        background: #004080;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.9rem;
    }
    .career-description {
        margin-bottom: 12px;
        font-size: 1rem;
        color: #444;
    }
    .career-skills {
        font-style: italic;
        color: #666;
        font-size: 0.95rem;
    }
    .career-card a {
        display: inline-block;
        margin-top: 12px;
        padding: 8px 14px;
        background-color: #004080;
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: background-color 0.3s ease;
    }
    .career-card a:hover {
        background-color: #0066cc;
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
  <h1>eMentor</h1>
  <p>Explore various career paths and guidance</p>

<?php if ($isLoggedIn): ?>
  <a class="logout-btn" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">
    🚪 Logout (<?= htmlspecialchars($fullName) ?>)
  </a>
<?php else: ?>
  <a class="logout-btn" href="login.php">
    🔐 Login
  </a>
<?php endif; ?>
</header>

<nav>
  <ul>
    <li><a href="dashboard.php">Home</a></li>
    <li><a href="career-guidance.php" style="background-color:#ffcc00; color:#004080; font-weight:700;">Career Guidance</a></li>
    <li><a href="careerpath.php">Career Path</a></li>
    <li><a href="about.php">About</a></li>
  </ul>
</nav>

<div class="container">
  <h2>Available Careers</h2>
  <div class="intro-text">
    Browse through the different careers to find your best fit based on description and skills required.
  </div>

  <?php if (empty($careers)): ?>
    <p style="text-align:center; color:#999;">No career data available at the moment.</p>
  <?php else: ?>
    <?php foreach ($careers as $career): ?>
      <div class="career-card">
        <div class="career-title"><?= htmlspecialchars($career['title']) ?></div>
        <div class="career-category"><?= htmlspecialchars($career['category']) ?></div>
        <div class="career-description"><?= nl2br(htmlspecialchars($career['description'])) ?></div>
        <div class="career-skills"><strong>Skills Required:</strong> <?= htmlspecialchars($career['skills']) ?></div>
        <a href="career-roadmap.php?career_id=<?= $career['id'] ?>">
          View Roadmap →
        </a>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
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

</body>
</html>
