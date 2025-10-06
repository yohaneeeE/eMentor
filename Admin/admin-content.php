<?php
// Database connection parameters
$dbAdminPath = __DIR__ . '/db_admin.php';
if (!file_exists($dbAdminPath)) {
    die('Database configuration file not found: ' . htmlspecialchars($dbAdminPath));
}
require_once $dbAdminPath;

// Ensure required variables are present
if (empty($dsn) || !isset($user) || !isset($pass) || !isset($options)) {
    die('Database configuration variables ($dsn, $user, $pass, $options) are not properly set in db_admin.php');
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}

// Handle add new career
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addCareer'])) {
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $description = $_POST['description'] ?? '';
    $skills = $_POST['skills'] ?? '';
    if ($title && $category) {
        $stmt = $pdo->prepare("INSERT INTO careers (title, category, description, skills) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $category, $description, $skills]);
        $message = "New career added successfully.";
    } else {
        $error = "Title and Category are required.";
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM careers WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Career ID $id deleted successfully.";
}

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = (int)$_POST['edit_id'];
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $description = $_POST['description'] ?? '';
    $skills = $_POST['skills'] ?? '';

    if ($title && $category) {
        $stmt = $pdo->prepare("UPDATE careers SET title = ?, category = ?, description = ?, skills = ? WHERE id = ?");
        $stmt->execute([$title, $category, $description, $skills, $id]);
        $message = "Career ID $id updated successfully.";
    } else {
        $error = "Please fill in all required fields (title and category).";
    }
}

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$totalStmt = $pdo->query("SELECT COUNT(*) FROM careers");
$totalRows = $totalStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $pdo->prepare("SELECT * FROM careers ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$careers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Career Management</title>
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body {
  font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background:#e9ecef;
  color:#333;
}

/* Header */
header {
  background:linear-gradient(135deg,#444,#222);
  color:#fff;
  text-align:center;
  padding:25px 0;
}
header h1 {margin:0; font-size:2rem;}
header p {opacity:0.85;}

/* Sidebar */
.sidebar {
  height:100vh;
  width:250px;
  position:fixed;
  top:0;
  left:-250px;
  background:#2f2f2f;
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
.sidebar a:hover, .sidebar a.active { background:#444; }
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

/* Container */
.container {
  max-width:1200px;
  margin:40px auto;
  padding:30px;
  background:#fff;
  border-radius:12px;
  box-shadow:0 5px 20px rgba(0,0,0,0.08);
}

/* Search/filter */
.user-controls {
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:20px;
  flex-wrap:wrap;
  gap:15px;
}
.search-box input {
  padding:10px 14px;
  border:2px solid #ccc;
  border-radius:8px;
  width:300px;
  font-size:1rem;
  background:#fdfdfd;
}
.search-box input:focus {
  outline:none;
  border-color:#666;
}

/* Table */
table.user-table {
  width:100%;
  border-collapse:collapse;
  margin-top:15px;
  border-radius:10px;
  overflow:hidden;
  background:#fff;
}
table.user-table th {
  background:#555;
  color:#fff;
  padding:12px;
  text-align:left;
}
table.user-table td {
  padding:12px;
  border-bottom:1px solid #ddd;
  vertical-align:top;
}
table.user-table tr:hover { background:#f1f1f1; }

/* Buttons */
.btn {
  padding:6px 12px;
  border:none;
  border-radius:6px;
  cursor:pointer;
  font-size:0.9rem;
  margin:2px;
  color:#fff;
}
.btn-edit {background:#6c757d;}
.btn-edit:hover {background:#5a6268;}
.btn-save {background:#28a745;}
.btn-save:hover {background:#218838;}
.btn-cancel {background:#dc3545;}
.btn-cancel:hover {background:#a71d2a;}
    .btn-add {background:#444;color:#fff;margin-bottom:15px;padding:10px 16px;border-radius:6px;}
/* Message */
.message {
  padding:12px 15px;
  margin:20px 0;
  border-radius:8px;
  font-weight:600;
  color:#fff;
  max-width:800px;
  margin-left:auto;
  margin-right:auto;
}
.success {background:#28a745;}
.error {background:#dc3545;}

/* Pagination */
.pagination {
  display:flex;
  justify-content:center;
  margin-top:25px;
  gap:8px;
}
.pagination a {
  display:inline-block;
  padding:8px 14px;
  border-radius:6px;
  background:#f1f1f1;
  color:#333;
  text-decoration:none;
  transition:0.3s;
  font-weight:500;
}
.pagination a:hover { background:#ccc; }
.pagination a.active {
  background:#444;
  color:#fff;
}

/* Modal */
.modal {
  display:none;
  position:fixed;
  z-index:2000;
  left:0;
  top:0;
  width:100%;
  height:100%;
  background:rgba(0,0,0,0.5);
  overflow:auto;
}
.modal-content {
  background:#fff;
  margin:10% auto;
  padding:20px;
  border-radius:10px;
  max-width:500px;
  box-shadow:0 5px 20px rgba(0,0,0,0.2);
}
.modal-content h3 { margin-top:0; margin-bottom:15px; }
.modal-content input, .modal-content textarea {
  width:100%;
  padding:10px;
  margin:8px 0;
  border:1px solid #ccc;
  border-radius:6px;
}
.modal-content button { margin-top:10px; }
.close {
  color:#aaa;
  float:right;
  font-size:24px;
  cursor:pointer;
}
.close:hover {color:#000;}

@media(max-width:768px){
  .user-controls{flex-direction:column;align-items:stretch;}
  .search-box input{width:100%;}
  table.user-table{font-size:0.9rem;}
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
  <h1>Career Management</h1>
  <p>Manage careers, descriptions, and skills</p>
</header>

<div class="container">
  <?php if (!empty($message)): ?>
      <div class="message success"><?= htmlspecialchars($message) ?></div>
  <?php elseif (!empty($error)): ?>
      <div class="message error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Add Career Button -->
  <div style="text-align:right; margin-bottom:15px;">
    <button class="btn-add" onclick="openModal()">+ Add Career</button>
  </div>

  <!-- Modal -->
  <div id="addCareerModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal()">&times;</span>
      <h3>Add New Career</h3>
      <form method="POST">
        <input type="hidden" name="addCareer" value="1">
        <label>Title</label>
        <input type="text" name="title" required>
        <label>Category</label>
        <input type="text" name="category" required>
        <label>Description</label>
        <textarea name="description"></textarea>
        <label>Skills</label>
        <textarea name="skills"></textarea>
        <button type="submit" class="btn btn-save">Save</button>
      </form>
    </div>
  </div>

  <!-- Filter/Search -->
  <div class="user-controls">
    <div class="search-box">
      <input type="text" id="searchInput" placeholder="Search careers...">
    </div>
  </div>

  <!-- Table -->
  <table id="user-table" class="user-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Category</th>
        <th>Description</th>
        <th>Skills</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($careers as $career): ?>
      <tr data-id="<?= $career['id'] ?>">
        <form method="POST">
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
        </form>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="delete_id" value="<?= $career['id'] ?>" />
            <button type="submit" class="btn btn-cancel" onclick="return confirm('Delete this career?')">Delete</button>
        </form>
          </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <div class="pagination">
    <?php for ($i=1; $i <= $totalPages; $i++): ?>
      <a href="?page=<?= $i ?>" class="<?= ($i==$page)?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
      <a href="?page=<?= $page+1 ?>">Next &raquo;</a>
    <?php endif; ?>
  </div>
</div>

<script>
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  sidebar.style.left = (sidebar.style.left === "0px") ? "-250px" : "0px";
}
function openModal() { document.getElementById('addCareerModal').style.display = 'block'; }
function closeModal() { document.getElementById('addCareerModal').style.display = 'none'; }
window.onclick = function(event) {
  let modal = document.getElementById('addCareerModal');
  if (event.target == modal) modal.style.display = 'none';
}

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

// Filter/Search
document.getElementById('searchInput').addEventListener('keyup', function(){
  let filter = this.value.toLowerCase();
  let rows = document.querySelectorAll('#user-table tbody tr');
  rows.forEach(row=>{
    let text = row.textContent.toLowerCase();
    row.style.display = text.includes(filter) ? '' : 'none';
  });
});
</script>
</body>
</html>
