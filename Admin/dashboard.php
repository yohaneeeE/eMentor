<?php
session_start();
include 'db_admin.php';

// Ensure totals are defined to avoid "Undefined variable" warnings
$totalUsers = 0;
$totalCareers = 0;

// If a database connection is provided, try to count rows safely.
// Supports common connection variables: $conn, $mysqli (mysqli) and $pdo (PDO).
if (isset($conn) && (class_exists('mysqli') && $conn instanceof mysqli)) {
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM users");
    if ($res) {
        $row = $res->fetch_assoc();
        $totalUsers = (int)($row['cnt'] ?? 0);
    }
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM careers");
    if ($res) {
        $row = $res->fetch_assoc();
        $totalCareers = (int)($row['cnt'] ?? 0);
    }
} elseif (isset($mysqli) && (class_exists('mysqli') && $mysqli instanceof mysqli)) {
    $res = $mysqli->query("SELECT COUNT(*) AS cnt FROM users");
    if ($res) {
        $row = $res->fetch_assoc();
        $totalUsers = (int)($row['cnt'] ?? 0);
    }
    $res = $mysqli->query("SELECT COUNT(*) AS cnt FROM careers");
    if ($res) {
        $row = $res->fetch_assoc();
        $totalCareers = (int)($row['cnt'] ?? 0);
    }
} elseif (isset($pdo) && (class_exists('PDO') && $pdo instanceof PDO)) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $totalUsers = (int)$stmt->fetchColumn();
        $stmt = $pdo->query("SELECT COUNT(*) FROM careers");
        $totalCareers = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        // Keep defaults on error
    }
}
// If no connection is available, $totalUsers and $totalCareers remain 0.
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard - Career Trends in IT</title>
  <style>
    * {margin:0; padding:0; box-sizing:border-box;}
    body {
      font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background:#e9ecef;   /* light grey */
      color:#333;
    }

    /* Sidebar */
    .sidebar {
      height:100vh;
      width:250px;
      position:fixed;
      top:0;
      left:-250px;
      background:#2f2f2f;  /* dark grey */
      color:#fff;
      padding-top:60px;
      transition:0.3s;
      overflow:auto;
      z-index:1000;
    }
    .sidebar a {
      display:block;
      padding:14px 20px;
      color:#fff;
      text-decoration:none;
      transition:0.3s;
    }
    .sidebar a:hover, .sidebar a.active {
      background:#444;
    }
    .open-btn {
      font-size:24px;
      cursor:pointer;
      background:none;
      border:none;
      color:#fff;
      position:absolute;
      left:20px;
      top:20px;
      z-index:1100;
    }

    header {
      background:linear-gradient(135deg,#444,#222);
      color:#fff;
      padding:25px 0;
      text-align:center;
      box-shadow:0 4px 12px rgba(0,0,0,0.1);
    }
    header h1 {font-size:2.5rem; margin-bottom:10px;}
    header p {opacity:0.9;}

    .container {
      max-width:1200px;
      margin:40px auto;
      padding:30px;
      background:#fff;
      border-radius:15px;
      box-shadow:0 4px 20px rgba(0,0,0,0.08);
    }

    h2 {
      color:#333;
      margin-bottom:25px;
      text-align:center;
      font-size:2rem;
      position:relative;
      padding-bottom:15px;
    }
    h2::after {
      content:'';
      position:absolute;
      bottom:0;
      left:50%;
      transform:translateX(-50%);
      width:100px;
      height:3px;
      background:linear-gradient(90deg,#444,#888);
      border-radius:3px;
    }
    .intro-text {
      font-size:1.1em;
      margin-bottom:40px;
      text-align:center;
      color:#555;
      max-width:800px;
      margin-left:auto;
      margin-right:auto;
    }

    .stats-grid {
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
      gap:30px;
      margin-bottom:40px;
    }
    .stat-card {
      background-color:#fdfdfd;
      padding:25px 20px;
      border-radius:12px;
      text-align:center;
      box-shadow:0 4px 10px rgba(0,0,0,0.05);
      transition:all 0.3s ease;
      border:1px solid #ddd;
    }
    .stat-card:hover {
      transform:translateY(-5px);
      box-shadow:0 8px 20px rgba(0,0,0,0.1);
      background-color:#f5f5f5;
    }
    .stat-card img {
      width:60px;
      height:60px;
      margin-bottom:15px;
    }
    .stat-number {
      font-size:2.5rem;
      font-weight:bold;
      color:#333;
      margin-bottom:10px;
    }
    .stat-label {
      font-size:1.1rem;
      color:#555;
    }

    footer {
      text-align:center;
      padding:30px 0;
      background:linear-gradient(135deg,#333,#222);
      color:#fff;
      font-size:0.95em;
      margin-top:60px;
    }
    .footer-links {
      display:flex;
      justify-content:center;
      gap:20px;
      margin-bottom:15px;
    }
    .footer-links a {
      color:#ffcc00;
      text-decoration:none;
      transition:color 0.3s ease;
    }
    .footer-links a:hover {color:white;}

    @media (max-width:768px){
      .stats-grid{gap:20px;}
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div id="sidebar" class="sidebar">
    <a href="dashboard.php">Dashboard</a>
    <a href="admin-users.php">User Management</a>
    <a href="admin-content.php">Career Content</a>
    <a href="admin-certificates.php">Certificates</a>
    <a href="admin-roadmaps.php">Career Roadmaps</a>
    <a href="logout.php">Logout</a>
  </div>
  <button class="open-btn" onclick="toggleSidebar()">☰</button>

  <header>
    <h1>Admin Dashboard</h1>
    <p>Manage and monitor your Career Trends platform</p>
  </header>

  <div class="container">
    <h2>System Overview</h2>
    <p class="intro-text">Monitor key metrics and manage your platform efficiently with comprehensive administrative tools.</p>
    
    <div class="stats-grid">
      <div class="stat-card">
        <img src="https://img.icons8.com/color/96/000000/user.png" alt="Users" />
        <div class="stat-number"><?= number_format($totalUsers) ?></div>
        <div class="stat-label">Total Users</div>
      </div>
      <div class="stat-card">
        <img src="https://img.icons8.com/color/96/000000/content.png" alt="Careers" />
        <div class="stat-number"><?= number_format($totalCareers) ?></div>
        <div class="stat-label">Careers</div>
      </div>
    </div>
  </div>

  <footer>
    <div class="footer-links">
      <a href="privacy.php">Privacy Policy</a>
      <a href="terms.php">Terms of Service</a>
      <a href="contact.php">Contact Us</a>
    </div>
    <p>&copy; 2025 Mapping The Future System. All rights reserved.</p>
    <p>Bulacan State University - Bustos Campus</p>
  </footer>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      sidebar.style.left = (sidebar.style.left === "0px") ? "-250px" : "0px";
    }
    const hamburger = document.getElementById('hamburger');
const sidebar   = document.getElementById('sidebar');
const overlay   = document.getElementById('overlay');

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
