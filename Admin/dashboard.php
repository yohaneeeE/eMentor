<?php
$host = 'localhost';
$port = 3307; // added port variable
$db   = 'careerguidance';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Fetch total users
    $stmt = $pdo->query('SELECT COUNT(*) as total_users FROM users');
    $totalUsers = $stmt->fetch()['total_users'];

    // Fetch total careers
    $stmt = $pdo->query('SELECT COUNT(*) as total_careers FROM careers');
    $totalCareers = $stmt->fetch()['total_careers'];

} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard - Career Trends in IT</title>
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
        nav ul li a:hover, nav ul li a.active {
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        .stat-card {
            background-color: #f2f7ff;
            padding: 25px 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid #e0e9ff;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            background-color: #e6f0ff;
        }
        .stat-card img {
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #004080;
            margin-bottom: 10px;
        }
        .stat-label {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 5px;
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
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }
            nav ul {
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
        <p>Manage and monitor your Career Trends platform</p>
    </header>

<nav>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="admin-users.php">User Management</a></li>
        <li><a href="admin-content.php">Career Content</a></li>
        <li><a href="admin-certificates.php">Certificates</a></li>
        <li><a href="admin-roadmaps.php">Career Roadmaps</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>


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
                <img src="https://img.icons8.com/color/96/000000/content.png" alt="Career Paths" />
                <div class="stat-number"><?= number_format($totalCareers) ?></div>
                <div class="stat-label">Career</div>
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
</body>
</html>
