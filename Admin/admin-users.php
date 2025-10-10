<?php
include 'db_admin.php';

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    $total = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    $stmt = $pdo->prepare('SELECT id, fullname, email, created_at FROM users ORDER BY created_at DESC LIMIT :l OFFSET :o');
    $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();

    $total_pages = ceil($total / $limit);
} catch (PDOException $e) {
    echo "Database connection failed: " . htmlspecialchars($e->getMessage());
    exit;
}

function formatDate($datetime) {
    return date('M d, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>User Management</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',Tahoma,sans-serif;background:#f4f6f8;color:#333;}
header{background:#444;color:#fff;text-align:center;padding:20px;position:relative;}
header h1{margin:0;font-size:2rem;}
header p{font-size:.95rem;opacity:.85;}

/* Sidebar */
.sidebar{position:fixed;top:0;left:-250px;width:220px;height:100%;background:#333;color:#fff;padding-top:60px;transition:left .3s;z-index:2000;}
.sidebar a{display:block;padding:12px 20px;color:#ddd;text-decoration:none;border-bottom:1px solid #444;}
.sidebar a:hover{background:#555;color:#fff;}
.sidebar.show{left:0;}

/* Hamburger */
.hamburger{position:absolute;left:20px;top:22px;width:28px;height:20px;cursor:pointer;display:flex;flex-direction:column;justify-content:space-between;}
.hamburger span{height:3px;width:100%;background:#fff;border-radius:2px;transition:.3s;}

/* Container */
.container{max-width:1200px;margin:40px auto;padding:30px;background:#fff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,.1);}
h2{text-align:center;color:#444;margin-bottom:20px;font-size:1.8rem;}

/* Table */
.table-wrapper{overflow-x:auto;}
.user-table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;min-width:600px;}
.user-table th, .user-table td{padding:12px;text-align:left;border-bottom:1px solid #ddd;}
.user-table th{background:#555;color:#fff;}
.user-table tr:hover{background:#f9f9f9;}

/* Buttons */
.btn-small{padding:6px 12px;border:none;border-radius:5px;cursor:pointer;font-size:.9rem;background:#666;color:#fff;transition:.3s;margin:2px;}
.btn-small:hover{background:#444;}
.btn-danger{background:#c0392b;}
.btn-danger:hover{background:#a93226;}

/* Pagination */
.pagination{display:flex;justify-content:center;margin:25px 0;gap:8px;flex-wrap:wrap;}
.pagination a{padding:8px 12px;border:1px solid #ccc;background:#eee;color:#333;text-decoration:none;border-radius:4px;}
.pagination a.active,.pagination a:hover{background:#444;color:#fff;}

footer{text-align:center;padding:20px;background:#333;color:#ddd;margin-top:40px;}

/* --- Responsive Styles --- */
@media(max-width:992px){
  .container{margin:20px;padding:20px;}
  header h1{font-size:1.6rem;}
}

@media(max-width:768px){
  body{font-size:0.95rem;}
  .container{padding:20px 16px;}
  header{padding:16px;}
  .sidebar{width:200px;}
  .user-table th, .user-table td{padding:10px 8px;font-size:0.9rem;}
  .btn-small{padding:5px 10px;font-size:0.85rem;}
}

@media(max-width:576px){
  .container{padding:18px 14px;}
  header h1{font-size:1.4rem;}
  header p{font-size:0.9rem;}
  .table-wrapper{overflow-x:auto;padding-bottom:10px;}
  .user-table{font-size:0.9rem;}
  .btn-small{display:block;width:100%;margin:4px 0;}
}
</style>
</head>
<body>
<header>
  <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('show')">
    <span></span><span></span><span></span>
  </div>
  <h1>User Management</h1>
  <p>Manage user accounts</p>
</header>

<div class="sidebar">
  <a href="dashboard.php">Dashboard</a>
  <a href="admin-users.php" class="active">User Management</a>
  <a href="admin-content.php">Career Content</a>
  <a href="admin-certificates.php">Certificates</a>
  <a href="admin-roadmaps.php">Career Roadmaps</a>
  <a href="logout.php">Logout</a>
</div>

<div class="container">
  <h2>Users List</h2>
  <div class="table-wrapper">
    <table class="user-table">
      <thead>
        <tr><th>User</th><th>Email</th><th>Registered On</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php if($users): foreach($users as $user): ?>
        <tr>
          <td><?=htmlspecialchars($user['fullname'])?></td>
          <td><?=htmlspecialchars($user['email'])?></td>
          <td><?=formatDate($user['created_at'])?></td>
          <td>
            <button class="btn-small" onclick="editUser(<?=$user['id']?>)">Edit</button>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="4" style="text-align:center;">No users found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="pagination">
    <?php for($i=1;$i<=$total_pages;$i++): ?>
      <a href="?page=<?=$i?>" class="<?=($i==$page)?'active':''?>"><?=$i?></a>
    <?php endfor; ?>
  </div>
</div>

<footer>&copy; <?=date("Y")?> Career Guidance Admin</footer>

<script>
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
function editUser(id){alert("Edit user "+id);}
</script>
</body>
</html>
