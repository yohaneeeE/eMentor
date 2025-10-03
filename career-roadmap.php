<?php
session_start();

// DB connection (adjust as needed)
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "careerguidance";

$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname, 3307);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get career_id from GET, validate
if (!isset($_GET['career_id']) || !is_numeric($_GET['career_id'])) {
    die("Invalid career ID.");
}
$career_id = intval($_GET['career_id']);

// Fetch career info
$stmt = $conn->prepare("SELECT title, category, description FROM careers WHERE id = ?");
$stmt->bind_param("i", $career_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Career not found.");
}
$career = $result->fetch_assoc();
$stmt->close();

// Fetch roadmap steps ordered by step_number
$stmt = $conn->prepare("SELECT step_number, step_title, step_detail FROM career_roadmaps WHERE career_id = ? ORDER BY step_number ASC");
$stmt->bind_param("i", $career_id);
$stmt->execute();
$roadmap_result = $stmt->get_result();

$roadmap_steps = [];
while ($row = $roadmap_result->fetch_assoc()) {
    $roadmap_steps[] = $row;
}
$stmt->close();

// Fetch certificates for this career
$stmt = $conn->prepare("SELECT certificate_title, provider, description, skills FROM certificates WHERE career_id = ?");
$stmt->bind_param("i", $career_id);
$stmt->execute();
$cert_result = $stmt->get_result();

$certificates = [];
while ($row = $cert_result->fetch_assoc()) {
    $certificates[] = $row;
}
$stmt->close();

$conn->close();

// Check login state
$isLoggedIn = isset($_SESSION['fullName']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Roadmap for <?= htmlspecialchars($career['title']) ?></title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #e6e6e6; /* grey theme */
        color: grey;
        line-height:1.6;
    }

    header {
        background: linear-gradient(135deg, #444, #666);
        color: white;
        padding: 25px 0;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        position: relative;
    }
    header h1 { font-size: 2rem; }
    header p { font-size: 1rem; opacity:0.9; }

    /* Hamburger button */
    .hamburger {
        position: absolute;
        top: 20px; left: 20px;
        width: 30px; height: 22px;
        display: flex; flex-direction: column;
        justify-content: space-between;
        cursor: pointer;
        z-index: 300;
        transition: transform 0.3s ease;
    }
    .hamburger span {
        height: 4px;
        background: white;
        border-radius: 2px;
        transition: all 0.3s ease;
    }
    .hamburger:hover { transform: scale(1.1); }
    .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px,5px); }
    .hamburger.active span:nth-child(2) { opacity:0; }
    .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(6px,-6px); }

    /* Sidebar Menu */
    .sidebar {
        position: fixed; top:0; left:-250px;
        width:250px; height:100%;
        background:#333; color:white;
        padding:60px 20px;
        display:flex; flex-direction:column;
        gap:20px;
        transition:left 0.3s ease;
        z-index:200;
    }
    .sidebar.active { left:0; }
    .sidebar a {
        color:white; text-decoration:none;
        font-size:1.1rem; padding:8px 0;
        display:block; transition: color 0.3s ease, transform 0.2s ease;
    }
    .sidebar a:hover { color:#ffcc00; transform:translateX(5px); }
        .sidebar .user-info {
        margin-top:auto; /* push to bottom */
        padding-top:15px;
        border-top:1px solid rgba(255,255,255,0.2);
        font-size:0.95rem;
        color:#ffcc00;
        text-align:center;
    }
    /* Overlay */
    .overlay {
        position:fixed; top:0; left:0;
        width:100%; height:100%;
        background: rgba(0,0,0,0.4);
        opacity:0; visibility:hidden;
        transition: opacity 0.3s ease;
        z-index:100;
    }
    .overlay.active { opacity:1; visibility:visible; }

    main {
        max-width: 900px;
        margin: 40px auto;
        background: #fff; /* white container for contrast */
        border-radius: 15px;
        padding: 30px 40px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }

    h2 {
        color: grey;
        font-size: 2rem;
        margin-bottom:5px;
    }
    .category {
        color:white;
        font-weight:600;
        margin-bottom:25px;
        background:#004080;
        display:inline-block;
        padding:6px 14px;
        border-radius:15px;
        font-size:1rem;
    }
    .description { margin-bottom:35px; font-size:1.1rem; color:#555; }

    .roadmap-step {
        background:#f5f5f5; /* subtle grey for steps */
        border-left:6px solid #004080;
        padding:20px 25px;
        margin-bottom:20px;
        border-radius:8px;
        box-shadow:0 2px 8px rgba(0,0,0,0.05);
    }
    .step-number { font-weight:700; color:grey; font-size:1.2rem; margin-bottom:6px; }
    .step-title { font-weight:600; font-size:1.3rem; margin-bottom:8px; }
    .step-description { font-size:1rem; color:#333; line-height:1.4; }

    .certificate {
        background:#fff; /* white for contrast */
        border-left:6px solid #ffcc00;
        padding:20px 25px;
        margin-bottom:20px;
        border-radius:8px;
        box-shadow:0 2px 8px rgba(0,0,0,0.05);
    }
    .cert-title { font-weight:600; font-size:1.2rem; color:grey; margin-bottom:6px; }
    .cert-provider { font-size:0.95rem; font-style:italic; margin-bottom:8px; }
    .cert-description { margin-bottom:6px; }
    .cert-skills { font-size:0.95rem; color:#444; }

    a.back-link {
        display:inline-block;
        margin-top:30px;
        background-color:#ffcc00; /* yellow button */
        color:#333; /* dark text for contrast */
        padding:10px 18px;
        border-radius:8px;
        text-decoration:none;
        font-weight:600;
        transition: background-color 0.3s ease;
    }
    a.back-link:hover { background-color:#e6b800; } /* darker yellow on hover */

    footer {
        text-align:center;
        padding:20px;
        background: linear-gradient(135deg,#444,#666);
        color:white;
        font-size:0.9rem;
        margin-top:auto;
    }
    .footer-links {
        margin-bottom:12px;
        display:flex;
        justify-content:center;
        gap:20px;
    }
    .footer-links a { color:#ffcc00; text-decoration:none; }
    .footer-links a:hover { color:white; }
</style>
</head>
<body>

<header>
    <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
    <h1>Career Roadmap</h1>
    <p>Plan your path with recommended steps & certificates</p>
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
  <h2><?= htmlspecialchars($career['title']) ?></h2>
  <div class="category"><?= htmlspecialchars($career['category']) ?></div>
  <div class="description"><?= nl2br(htmlspecialchars($career['description'])) ?></div>

  <h3> Roadmap</h3>
  <?php if (empty($roadmap_steps)): ?>
    <p style="color:#999; font-style: italic;">No roadmap steps available yet for this career.</p>
  <?php else: ?>
    <?php foreach ($roadmap_steps as $step): ?>
      <div class="roadmap-step">
        <div class="step-number">Step <?= $step['step_number'] ?></div>
        <div class="step-title"><?= htmlspecialchars($step['step_title']) ?></div>
        <div class="step-description"><?= nl2br(htmlspecialchars($step['step_detail'])) ?></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <h3> Recommended Certificates</h3>
  <?php if (empty($certificates)): ?>
    <p style="color:#999; font-style: italic;">No certificates available yet for this career.</p>
  <?php else: ?>
    <?php foreach ($certificates as $cert): ?>
      <div class="certificate">
        <div class="cert-title"><?= htmlspecialchars($cert['certificate_title']) ?></div>
        <div class="cert-provider">Offered by <?= htmlspecialchars($cert['provider']) ?></div>
        <div class="cert-description"><?= nl2br(htmlspecialchars($cert['description'])) ?></div>
        <div class="cert-skills"><strong>Skills:</strong> <?= htmlspecialchars($cert['skills']) ?></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <a class="back-link" href="career-guidance.php">&larr; Back to Careers</a>
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
