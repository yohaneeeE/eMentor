<?php
// Database connection parameters
$host = 'localhost';
$port = 3307;
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

    // Fetch users with only available fields
    $stmt = $pdo->query('SELECT id, fullname, email, created_at FROM users ORDER BY created_at DESC');
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Database connection failed: " . htmlspecialchars($e->getMessage());
    exit;
}

// Format created_at date
function formatDate($datetime) {
    return date('M d, Y', strtotime($datetime));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Management - Admin Dashboard</title>
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
        .user-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .search-box input {
            padding: 12px 15px;
            border: 2px solid #e0e9ff;
            border-radius: 8px;
            font-size: 1rem;
            width: 300px;
            transition: border-color 0.3s ease;
        }
        .search-box input:focus {
            outline: none;
            border-color: #004080;
        }
        .btn {
            background: linear-gradient(135deg, #004080, #0066cc);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: linear-gradient(135deg, #003060, #004080);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 64, 128, 0.3);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        .btn-secondary:hover {
            background: linear-gradient(135deg, #218838, #1ea085);
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #e74c3c);
        }
        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333, #dc2626);
        }
        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #ffb300);
            color: #333;
        }
        .btn-warning:hover {
            background: linear-gradient(135deg, #e0a800, #ff8f00);
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        .user-table th {
            background: linear-gradient(135deg, #004080, #0066cc);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        .user-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        .user-table tr:hover {
            background-color: #f8f9fa;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-name {
            font-weight: 600;
            color: #333;
        }
        .user-email {
            color: #666;
            font-size: 0.9rem;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .role-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .role-admin {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .role-user {
            background-color: #cce5ff;
            color: #004080;
        }
        .role-moderator {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .btn-small {
            background: linear-gradient(135deg, #004080, #0066cc);
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
        }
        .btn-small:hover {
            background: linear-gradient(135deg, #003060, #004080);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 64, 128, 0.3);
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 30px;
            gap: 10px;
        }
        .pagination button {
            padding: 8px 12px;
            border: 2px solid #e0e9ff;
            background: white;
            color: #004080;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .pagination button:hover {
            background: #004080;
            color: white;
        }
        .pagination button.active {
            background: #004080;
            color: white;
        }
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-item {
            background-color: #f2f7ff;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #e0e9ff;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #004080;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
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
            .user-controls {
                flex-direction: column;
                align-items: stretch;
            }
            .search-box input {
                width: 100%;
            }
            .user-table {
                font-size: 0.9rem;
            }
            .user-table th,
            .user-table td {
                padding: 10px;
            }
            .action-buttons {
                flex-direction: column;
            }
            nav ul {
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>User Management</h1>
        <p>Manage user accounts</p>
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
        <h2>User Management Dashboard</h2>

        
        <!-- Users Table -->
        <table class="user-table" id="userTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Registered On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div class="user-info">
                            <img src="https://img.icons8.com/color/48/000000/user.png" alt="User" class="user-avatar" />
                            <div>
                                <div class="user-name"><?= htmlspecialchars($user['fullname']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= formatDate($user['created_at']) ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-small" onclick="editUser(<?= (int)$user['id'] ?>)">Edit</button>
                            <button class="btn-small btn-danger" onclick="deleteUser(<?= (int)$user['id'] ?>)">Delete</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($users)): ?>
                <tr>
                    <td colspan="4" style="text-align:center;">No users found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function editUser(userId) {
            alert(`Edit user with ID: ${userId}`);
            // You can implement redirection or modal popup here
        }
        function deleteUser(userId) {
            if(confirm('Are you sure you want to delete this user?')) {
                alert(`User ${userId} deleted.`);
                // Implement AJAX or form submission to delete user here
            }
        }
    </script>
</body>
</html>
