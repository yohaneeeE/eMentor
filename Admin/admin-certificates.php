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

// ===== Handle Add Certificate =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_certificate'])) {
    $career_id = $_POST['career_id'] ?? '';
    $title = $_POST['certificate_title'] ?? '';
    $provider = $_POST['provider'] ?? '';
    $description = $_POST['description'] ?? '';
    $skills = $_POST['skills'] ?? '';

    if ($career_id && $title && $provider) {
        $stmt = $pdo->prepare("INSERT INTO certificates (career_id, certificate_title, provider, description, skills) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$career_id, $title, $provider, $description, $skills]);
        $message = "✅ New certificate added successfully.";
    } else {
        $error = "⚠️ Please fill in required fields (career_id, certificate_title, provider).";
    }
}

// ===== Handle Edit Certificate =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = (int)$_POST['edit_id'];
    $career_id = $_POST['career_id'] ?? '';
    $title = $_POST['certificate_title'] ?? '';
    $provider = $_POST['provider'] ?? '';
    $description = $_POST['description'] ?? '';
    $skills = $_POST['skills'] ?? '';

    if ($career_id && $title && $provider) {
        $stmt = $pdo->prepare("UPDATE certificates SET career_id=?, certificate_title=?, provider=?, description=?, skills=? WHERE id=?");
        $stmt->execute([$career_id, $title, $provider, $description, $skills, $id]);
        $message = "✅ Certificate ID $id updated successfully.";
    } else {
        $error = "⚠️ Please fill in required fields (career_id, certificate_title, provider).";
    }
}

// ===== Handle Delete Certificate =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM certificates WHERE id=?");
    $stmt->execute([$id]);
    $message = "🗑️ Certificate ID $id deleted successfully.";
}

// Fetch all certificates
$stmt = $pdo->query("SELECT * FROM certificates ORDER BY id DESC");
$certs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Certificate Management</title>
  <style>
    body {font-family: Arial, sans-serif; background:#f9f9f9; margin:0;}
    header {background:linear-gradient(135deg,#004080,#0066cc);color:#fff;text-align:center;padding:20px;}
    nav {background-color:#003060;padding:15px 0;position:sticky;top:0;z-index:100;}
    nav ul {list-style:none;display:flex;justify-content:center;gap:30px;flex-wrap:wrap;}
    nav ul li a {color:white;text-decoration:none;font-weight:600;padding:8px 15px;border-radius:5px;transition:0.3s;}
    nav ul li a:hover, nav ul li a.active {color:#ffcc00;background:rgba(255,255,255,0.1);}
    .container {max-width:1200px;margin:30px auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
    table {width:100%;border-collapse:collapse;}
    th,td {padding:10px;border-bottom:1px solid #ddd;}
    th {background:#004080;color:#fff;text-align:left;}
    .btn {padding:6px 12px;border:none;border-radius:5px;cursor:pointer;}
    .btn-edit {background:#0066cc;color:#fff;}
    .btn-save {background:#28a745;color:#fff;}
    .btn-cancel {background:#dc3545;color:#fff;}
    .btn-delete {background:#e74c3c;color:#fff;}
    textarea,input[type=text] {width:100%;padding:6px;}
    .message {padding:10px;margin:15px 0;border-radius:5px;}
    .success {background:#28a745;color:#fff;}
    .error {background:#dc3545;color:#fff;}
    h2 {margin-top:0;}
    .add-form {margin-bottom:20px;padding:15px;border:1px solid #ddd;border-radius:8px;background:#f8f9fa;}
  </style>
</head>
<body>
<header>
  <h1>Certificate Management</h1>
</header>
<nav>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="admin-users.php">User Management</a></li>
        <li><a href="admin-content.php">Career Content</a></li>
        <li><a href="admin-certificates.php" class="active">Certificates</a></li>
        <li><a href="admin-roadmaps.php">Career Roadmaps</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<div class="container">
  <h2>Certificates</h2>
  <?php if (!empty($message)): ?><div class="message success"><?= $message ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="message error"><?= $error ?></div><?php endif; ?>

  <!-- Add Certificate Form -->
  <div class="add-form">
    <h3>Add New Certificate</h3>
    <form method="POST">
      <input type="hidden" name="add_certificate" value="1">
      <p><input type="text" name="career_id" placeholder="Career ID" required></p>
      <p><input type="text" name="certificate_title" placeholder="Certificate Title" required></p>
      <p><input type="text" name="provider" placeholder="Provider" required></p>
      <p><textarea name="description" placeholder="Description"></textarea></p>
      <p><textarea name="skills" placeholder="Skills"></textarea></p>
      <p><button type="submit" class="btn btn-save">Add Certificate</button></p>
    </form>
  </div>

  <table>
    <thead>
      <tr><th>ID</th><th>Career ID</th><th>Title</th><th>Provider</th><th>Description</th><th>Skills</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($certs as $c): ?>
      <tr>
        <form method="POST">
          <td><?= $c['id'] ?><input type="hidden" name="edit_id" value="<?= $c['id'] ?>"></td>
          <td><span class="view-mode"><?= $c['career_id'] ?></span>
              <input class="edit-mode" type="text" name="career_id" value="<?= $c['career_id'] ?>" style="display:none;"></td>
          <td><span class="view-mode"><?= htmlspecialchars($c['certificate_title']) ?></span>
              <input class="edit-mode" type="text" name="certificate_title" value="<?= htmlspecialchars($c['certificate_title']) ?>" style="display:none;"></td>
          <td><span class="view-mode"><?= htmlspecialchars($c['provider']) ?></span>
              <input class="edit-mode" type="text" name="provider" value="<?= htmlspecialchars($c['provider']) ?>" style="display:none;"></td>
          <td><span class="view-mode"><?= nl2br(htmlspecialchars($c['description'])) ?></span>
              <textarea class="edit-mode" name="description" style="display:none;"><?= htmlspecialchars($c['description']) ?></textarea></td>
          <td><span class="view-mode"><?= nl2br(htmlspecialchars($c['skills'])) ?></span>
              <textarea class="edit-mode" name="skills" style="display:none;"><?= htmlspecialchars($c['skills']) ?></textarea></td>
          <td>
            <button type="button" class="btn btn-edit view-mode" onclick="startEdit(this)">Edit</button>
            <button type="submit" class="btn btn-save edit-mode" style="display:none;">Save</button>
            <button type="button" class="btn btn-cancel edit-mode" style="display:none;" onclick="cancelEdit(this)">Cancel</button>
        </form>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
            <button type="submit" class="btn btn-delete" onclick="return confirm('Delete this certificate?')">Delete</button>
        </form>
          </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<script>
function startEdit(btn){const tr=btn.closest("tr");toggle(tr,true);}
function cancelEdit(btn){const tr=btn.closest("tr");toggle(tr,false);}
function toggle(row,edit){row.querySelectorAll(".view-mode").forEach(el=>el.style.display=edit?"none":"");row.querySelectorAll(".edit-mode").forEach(el=>el.style.display=edit?"":"none");}
</script>
</body>
</html>
