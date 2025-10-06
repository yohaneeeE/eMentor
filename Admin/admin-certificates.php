<?php
// Database connection parameters
include 'db_admin.php';

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
        $message = "New certificate added successfully.";
    } else {
        $error = "Please fill in required fields (career_id, certificate_title, provider).";
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
        $message = "Certificate ID $id updated successfully.";
    } else {
        $error = "Please fill in required fields (career_id, certificate_title, provider).";
    }
}

// ===== Handle Delete Certificate =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM certificates WHERE id=?");
    $stmt->execute([$id]);
    $message = "Certificate ID $id deleted successfully.";
}

// ===== Filtering =====
$filterCareerId = $_GET['career_id'] ?? '';
$whereClause = "";
$params = [];

if ($filterCareerId !== '') {
    $whereClause = "WHERE career_id = ?";
    $params[] = $filterCareerId;
}

// ===== Pagination =====
$limit = 5; // rows per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM certificates $whereClause");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = $totalRows > 0 ? ceil($totalRows / $limit) : 1;

// Fetch paginated results
$sql = "SELECT * FROM certificates $whereClause ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$certs = $stmt->fetchAll();

// Fetch all unique Career IDs for filter dropdown
$careerStmt = $pdo->query("SELECT DISTINCT career_id FROM certificates ORDER BY career_id ASC");
$careerIds = $careerStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Certificate Management</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background:#f4f4f4;
      margin:0;
    }
    header {
      background:linear-gradient(135deg,#2c2c2c,#444);
      color:#fff;
      text-align:center;
      padding:20px;
      position:relative;
    }
    header h1 {
      margin:0;
      font-size:1.8rem;
    }
    .container {
      max-width:1200px;
      margin:20px auto;
      background:#fff;
      padding:20px;
      border-radius:10px;
      box-shadow:0 3px 12px rgba(0,0,0,0.1);
    }
    h2 { margin-top:0; color:#333; }
    table {
      width:100%;
      border-collapse:collapse;
      margin-top:15px;
    }
    th, td {
      padding:10px;
      border:1px solid #ddd;
      text-align:left;
      vertical-align:top;
    }
    th { background:#555; color:#fff; }
    .btn {
      padding:6px 12px;
      border:none;
      border-radius:5px;
      cursor:pointer;
    }
    .btn-edit {background:#0066cc;color:#fff;}
    .btn-save {background:#28a745;color:#fff;display:none;}
    .btn-cancel {background:#6c757d;color:#fff;display:none;}
    .btn-delete {background:#e74c3c;color:#fff;}
    .btn-add {background:#444;color:#fff;margin-bottom:15px;padding:10px 16px;border-radius:6px;}
    .filter-box { margin-bottom:15px; display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
    .filter-box label { font-weight:600; color:#333; }
    .pagination { margin-top:15px; text-align:center; }
    .pagination a {
      margin:0 5px;
      padding:6px 12px;
      border:1px solid #ccc;
      text-decoration:none;
      color:#333;
      border-radius:4px;
    }
       /* Sidebar */
    .sidebar {
      height:100vh;
      width:250px;
      position:fixed;
      top:0;
      left:-250px;
      background:#2c2c2c;
      color:#fff;
      padding-top:60px;
      transition:0.3s;
      overflow:auto;
    }
    .sidebar a {
      display:block;
      padding:12px 20px;
      color:#fff;
      text-decoration:none;
    }
    .sidebar a:hover {
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
    .pagination a.active {
      background:#444;
      color:#fff;
      border-color:#444;
    }

    /* Modal Styles */
    .modal {display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.5);}
    .modal-content {background:#fff;margin:6% auto;padding:20px;border-radius:10px;width:420px;box-shadow:0 5px 15px rgba(0,0,0,0.3);}
    .modal-content h3 {margin-top:0;}
    .close {color:#aaa;float:right;font-size:24px;font-weight:bold;cursor:pointer;}
    .close:hover {color:#000;}
    input[type=text], textarea, select {
      width:100%;padding:8px;margin:6px 0;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;
    }
    .message {padding:10px;margin:15px 0;border-radius:5px;}
    .success {background:#d4edda;color:#155724;border:1px solid #c3e6cb;}
    .error {background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}
  </style>
</head>
<body>
<header>
  <h1>Certificate Management</h1>
</header>
<!-- Sidebar -->
<div id="sidebar" class="sidebar">
  <a href="dashboard.php">Dashboard</a>
  <a href="admin-users.php">User Management</a>
  <a href="admin-content.php">Career Content</a>
  <a href="admin-certificates.php">Certificates</a>
  <a href="admin-roadmaps.php">Career Roadmaps</a>
  <a href="logout.php">Logout</a>
</div>

<!-- Hamburger Button -->
<button class="open-btn" onclick="toggleSidebar()">☰</button>


<div class="container">
  <h2>Certificates</h2>

  <?php if (!empty($message)): ?><div class="message success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="message error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <!-- Add Button (opens modal) -->
  <button class="btn-add" onclick="openModal()">+ Add Certificate</button>

  <!-- Modal -->
  <div id="addModal" class="modal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true">
      <span class="close" onclick="closeModal()" title="Close">&times;</span>
      <h3>Add New Certificate</h3>
      <form method="POST" id="addForm">
        <input type="hidden" name="add_certificate" value="1">
        <p><input type="text" name="career_id" placeholder="Career ID" required></p>
        <p><input type="text" name="certificate_title" placeholder="Certificate Title" required></p>
        <p><input type="text" name="provider" placeholder="Provider" required></p>
        <p><textarea name="description" placeholder="Description"></textarea></p>
        <p><textarea name="skills" placeholder="Skills"></textarea></p>
        <p style="text-align:right;">
          <button type="button" class="btn" onclick="closeModal()" style="margin-right:8px;">Cancel</button>
          <button type="submit" class="btn btn-save">Add Certificate</button>
        </p>
      </form>
    </div>
  </div>

  <!-- Filter Dropdown (above table) -->
  <div class="filter-box">
    <form method="GET" id="filterForm" style="margin:0; display:flex; gap:8px; align-items:center;">
      <label for="career_id">Filter by Career ID:</label>
      <select name="career_id" id="career_id" onchange="this.form.submit()">
        <option value="">-- All --</option>
        <?php foreach ($careerIds as $cid): ?>
          <option value="<?= htmlspecialchars($cid) ?>" <?= $cid == $filterCareerId ? 'selected' : '' ?>><?= htmlspecialchars($cid) ?></option>
        <?php endforeach; ?>
      </select>
      <div style="margin-left:auto; font-size:0.95rem; color:#666;">Showing <?= htmlspecialchars((int)$totalRows) ?> result(s)</div>
    </form>
  </div>

  <table>
    <thead>
      <tr><th style="width:60px">ID</th><th style="width:100px">Career ID</th><th>Title</th><th style="width:140px">Provider</th><th>Description</th><th style="width:140px">Skills</th><th style="width:160px">Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($certs as $c): ?>
      <tr>
        <!-- Edit form for row -->
        <form method="POST" class="edit-form" style="margin:0;">
          <td>
            <?= (int)$c['id'] ?>
            <input type="hidden" name="edit_id" value="<?= (int)$c['id'] ?>">
          </td>

          <td><input type="text" name="career_id" value="<?= htmlspecialchars($c['career_id']) ?>" disabled data-original="<?= htmlspecialchars($c['career_id']) ?>"></td>

          <td><input type="text" name="certificate_title" value="<?= htmlspecialchars($c['certificate_title']) ?>" disabled data-original="<?= htmlspecialchars($c['certificate_title']) ?>"></td>

          <td><input type="text" name="provider" value="<?= htmlspecialchars($c['provider']) ?>" disabled data-original="<?= htmlspecialchars($c['provider']) ?>"></td>

          <td><textarea name="description" disabled data-original="<?= htmlspecialchars($c['description']) ?>"><?= htmlspecialchars($c['description']) ?></textarea></td>

          <td><textarea name="skills" disabled data-original="<?= htmlspecialchars($c['skills']) ?>"><?= htmlspecialchars($c['skills']) ?></textarea></td>

          <td>
            <button type="button" class="btn btn-edit" onclick="enableEdit(this)">Edit</button>
            <button type="submit" class="btn btn-save">Save</button>
            <button type="button" class="btn btn-cancel" onclick="cancelEdit(this)">Cancel</button>
        </form>

        <!-- Delete form -->
        <form method="POST" style="display:inline;margin:0;">
          <input type="hidden" name="delete_id" value="<?= (int)$c['id'] ?>">
          <button type="submit" class="btn btn-delete" onclick="return confirm('Delete this certificate?')">Delete</button>
        </form>
          </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($certs)): ?>
      <tr><td colspan="7" style="text-align:center;color:#666;padding:20px;">No certificates found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <div class="pagination" aria-label="Pagination">
    <?php
      // Build base query params for pagination links
      $baseCareerParam = $filterCareerId !== '' ? '&career_id=' . urlencode($filterCareerId) : '';
      for ($i=1; $i <= $totalPages; $i++):
        $activeClass = ($i == $page) ? 'active' : '';
    ?>
      <a href="?page=<?= $i . $baseCareerParam ?>" class="<?= $activeClass ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
</div>

<script>
  // Modal handling
  function openModal() {
    const modal = document.getElementById('addModal');
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden','false');
  }
  function closeModal() {
    const modal = document.getElementById('addModal');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden','true');
    // optional: reset form
    const f = document.getElementById('addForm');
    if (f) f.reset();
  }
  window.onclick = function(e) {
    const modal = document.getElementById('addModal');
    if (e.target === modal) closeModal();
  }

  // Enable edit on row
  function enableEdit(btn) {
    const row = btn.closest('tr');
    if (!row) return;
    // Store original values (if not already stored)
    row.querySelectorAll('input[disabled], textarea[disabled]').forEach(el => {
      if (typeof el.dataset.original === 'undefined' || el.dataset.original === '') {
        el.dataset.original = el.value || el.textContent || '';
      }
      el.disabled = false;
    });

    // Toggle buttons visibility
    const editBtn = row.querySelector('.btn-edit');
    const saveBtn = row.querySelector('.btn-save');
    const cancelBtn = row.querySelector('.btn-cancel');
    if (editBtn) editBtn.style.display = 'none';
    if (saveBtn) saveBtn.style.display = 'inline-block';
    if (cancelBtn) cancelBtn.style.display = 'inline-block';
  }

  // Cancel edit: revert to original and disable
  function cancelEdit(btn) {
    const row = btn.closest('tr');
    if (!row) return;
    row.querySelectorAll('input, textarea').forEach(el => {
      if (typeof el.dataset.original !== 'undefined') {
        el.value = el.dataset.original;
      }
      el.disabled = true;
    });

    const editBtn = row.querySelector('.btn-edit');
    const saveBtn = row.querySelector('.btn-save');
    const cancelBtn = row.querySelector('.btn-cancel');
    if (editBtn) editBtn.style.display = 'inline-block';
    if (saveBtn) saveBtn.style.display = 'none';
    if (cancelBtn) cancelBtn.style.display = 'none';
  }

  // Before any edit-form submission, ensure disabled inputs are enabled so values are submitted
  document.querySelectorAll('.edit-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
      // enable all fields inside this form so they submit
      form.querySelectorAll('input, textarea').forEach(function(el) {
        el.disabled = false;
      });
      // allow form to submit normally
    });
  });

  // Accessibility: close modal on Esc
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') {
      const modal = document.getElementById('addModal');
      if (modal && modal.style.display === 'block') closeModal();
    }
  });
 
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  if (sidebar.style.left === '0px') {
    sidebar.style.left = '-250px';
  } else {
    sidebar.style.left = '0px';
  }
}

</script>
</body>
</html>
