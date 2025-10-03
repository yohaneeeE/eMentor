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
} catch (PDOException $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}

// Handle updates if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = (int)$_POST['edit_id'];
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $description = $_POST['description'] ?? '';
    $skills = $_POST['skills'] ?? '';

    // Basic validation
    if ($title && $category) {
        $stmt = $pdo->prepare("UPDATE careers SET title = ?, category = ?, description = ?, skills = ? WHERE id = ?");
        $stmt->execute([$title, $category, $description, $skills, $id]);
        $message = "Career ID $id updated successfully.";
    } else {
        $error = "Please fill in all required fields (title and category).";
    }
}

// Fetch all careers
$stmt = $pdo->query("SELECT * FROM careers ORDER BY id DESC");
$careers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Career Content Management</title>
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
    table.user-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        table-layout: fixed;
    }
    table.user-table th {
        background: linear-gradient(135deg, #004080, #0066cc);
        color: white;
        padding: 15px;
        text-align: left;
        font-weight: 600;
    }
    table.user-table td {
        padding: 15px;
        border-bottom: 1px solid #e9ecef;
        vertical-align: top;
        word-wrap: break-word;
    }
    table.user-table tr:hover {
        background-color: #f8f9fa;
    }
    input[type="text"], textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 8px 10px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 0.9rem;
        border: 2px solid #e0e9ff;
        border-radius: 8px;
        transition: border-color 0.3s ease;
        resize: vertical;
        min-height: 60px;
    }
    input[type="text"]:focus, textarea:focus {
        outline: none;
        border-color: #004080;
    }
    .message {
        padding: 12px 15px;
        margin: 20px 0;
        border-radius: 8px;
        font-weight: 600;
        color: white;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }
    .success {
        background-color: #28a745;
    }
    .error {
        background-color: #dc3545;
    }
    @media (max-width: 768px) {
        .user-controls {
            flex-direction: column;
            align-items: stretch;
        }
        .search-box input {
            width: 100%;
        }
        table.user-table {
            font-size: 0.9rem;
        }
        table.user-table th,
        table.user-table td {
            padding: 10px;
        }
        .btn {
            padding: 8px 12px;
            font-size: 0.85rem;
        }
    }
</style>

</head>
<body>
 <header>
    <h1>Content Management</h1>
    <p>Manage career-related content and skills</p>
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
    <h2>Career Content Management</h2>


    <?php if (!empty($message)): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php elseif (!empty($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <table id="user-table" class="user-table">
        <thead>
            <tr>
                <th >ID</th>
                <th >Title</th>
                <th >Category</th>
                <th >Description</th>
                <th >Skills</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($careers as $career): ?>
                <tr data-id="<?= $career['id'] ?>">
                    <form method="POST" style="margin:0; padding:0;">
                        <td><?= $career['id'] ?></td>
                        <td>
                            <span class="view-mode"><?= htmlspecialchars($career['title']) ?></span>
                            <input class="edit-mode" type="text" name="title" value="<?= htmlspecialchars($career['title']) ?>" style="display:none;" required />
                        </td>
                        <td>
                            <span class="view-mode"><?= htmlspecialchars($career['category']) ?></span>
                            <input class="edit-mode" type="text" name="category" value="<?= htmlspecialchars($career['category']) ?>" style="display:none;" required />
                        </td>
                        <td>
                            <span class="view-mode"><?= nl2br(htmlspecialchars($career['description'])) ?></span>
                            <textarea class="edit-mode" name="description" style="display:none;"><?= htmlspecialchars($career['description']) ?></textarea>
                        </td>
                        <td>
                            <span class="view-mode"><?= nl2br(htmlspecialchars($career['skills'])) ?></span>
                            <textarea class="edit-mode" name="skills" style="display:none;"><?= htmlspecialchars($career['skills']) ?></textarea>
                        </td>
                        <td>
                            <input type="hidden" name="edit_id" value="<?= $career['id'] ?>" />
                            <button type="button" class="btn btn-edit view-mode" onclick="startEdit(this)">Edit</button>
                            <button type="submit" class="btn btn-save edit-mode" style="display:none;">Save</button>
                            <button type="button" class="btn btn-cancel edit-mode" style="display:none;" onclick="cancelEdit(this)">Cancel</button>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
            </div>
<script>
    function startEdit(btn) {
        const tr = btn.closest('tr');
        toggleEditMode(tr, true);
    }
    function cancelEdit(btn) {
        const tr = btn.closest('tr');
        toggleEditMode(tr, false);
    }
    function toggleEditMode(row, isEdit) {
        row.querySelectorAll('.view-mode').forEach(el => el.style.display = isEdit ? 'none' : '');
        row.querySelectorAll('.edit-mode').forEach(el => el.style.display = isEdit ? '' : 'none');
    }
</script>

</body>
</html>
