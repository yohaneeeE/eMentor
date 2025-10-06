<?php
// DB Connection
include 'db_admin.php';
$options=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC];
try{$pdo=new PDO($dsn,$user,$pass,$options);}catch(PDOException $e){die("DB fail:".$e->getMessage());}

// Handle updates
if($_SERVER['REQUEST_METHOD']==='POST'){
  // Update
  if(isset($_POST['edit_id'])){
    $id=(int)$_POST['edit_id'];
    $career_id=$_POST['career_id']??''; $step_number=$_POST['step_number']??''; 
    $step_title=$_POST['step_title']??''; $step_detail=$_POST['step_detail']??'';
    if($career_id && $step_number && $step_title){
      $stmt=$pdo->prepare("UPDATE career_roadmaps SET career_id=?, step_number=?, step_title=?, step_detail=? WHERE id=?");
      $stmt->execute([$career_id,$step_number,$step_title,$step_detail,$id]);
      $message="Roadmap ID $id updated.";
    } else { $error="Required: career_id, step_number, step_title."; }
  }

  // Add new
  if(isset($_POST['add_new'])){
    $career_id=$_POST['career_id']??''; $step_number=$_POST['step_number']??''; 
    $step_title=$_POST['step_title']??''; $step_detail=$_POST['step_detail']??'';
    if($career_id && $step_number && $step_title){
      $stmt=$pdo->prepare("INSERT INTO career_roadmaps (career_id, step_number, step_title, step_detail) VALUES (?,?,?,?)");
      $stmt->execute([$career_id,$step_number,$step_title,$step_detail]);
      $message="New roadmap step added.";
    } else { $error="Required: career_id, step_number, step_title."; }
  }

  // Delete
  if(isset($_POST['delete_id'])){
    $id=(int)$_POST['delete_id'];
    $stmt=$pdo->prepare("DELETE FROM career_roadmaps WHERE id=?");
    $stmt->execute([$id]);
    $message="Roadmap ID $id deleted.";
  }
}

// Pagination
$limit=10;
$page=isset($_GET['page'])?(int)$_GET['page']:1;
$offset=($page-1)*$limit;

$total=$pdo->query("SELECT COUNT(*) FROM career_roadmaps")->fetchColumn();
$stmt=$pdo->prepare("SELECT * FROM career_roadmaps ORDER BY career_id, step_number LIMIT :l OFFSET :o");
$stmt->bindValue(':l',$limit,PDO::PARAM_INT);
$stmt->bindValue(':o',$offset,PDO::PARAM_INT);
$stmt->execute();
$rows=$stmt->fetchAll();
$total_pages=ceil($total/$limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Roadmap Management</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{font-family:'Segoe UI',Tahoma,Verdana,sans-serif;background:#e9ecef;color:#333;}
    header{background:linear-gradient(135deg,#444,#222);color:#fff;text-align:center;padding:25px 0;position:relative;}
    header h1{margin:0;font-size:2rem;}

    /* Sidebar */
    .sidebar{position:fixed;top:0;left:-250px;width:250px;height:100%;background:#2f2f2f;color:#fff;padding-top:60px;transition:.3s;z-index:2000;overflow:auto;}
    .sidebar a{display:block;padding:14px 20px;color:#fff;text-decoration:none;transition:.3s;}
    .sidebar a:hover,.sidebar a.active{background:#444;}
    .sidebar.show{left:0;}
    .hamburger{position:absolute;left:20px;top:22px;width:28px;height:20px;cursor:pointer;display:flex;flex-direction:column;justify-content:space-between;}
    .hamburger span{height:3px;width:100%;background:#fff;border-radius:2px;}

    /* Container */
    .container{max-width:1200px;margin:40px auto;padding:30px;background:#fff;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,0.08);}
    h2{text-align:center;color:#444;margin-bottom:20px;}

    /* Table */
    table{width:100%;border-collapse:collapse;margin-top:20px;border-radius:10px;overflow:hidden;background:#fff;}
    th{background:#555;color:#fff;padding:12px;text-align:left;}
    td{padding:12px;border-bottom:1px solid #ddd;vertical-align:top;}
    tr:hover{background:#f1f1f1;}

    /* Buttons */
    .btn{padding:6px 12px;border:none;border-radius:6px;cursor:pointer;font-size:.9rem;margin:2px;color:#fff;}
    .btn-edit{background:#6c757d;}
    .btn-edit:hover{background:#5a6268;}
    .btn-save{background:#28a745;}
    .btn-save:hover{background:#218838;}
    .btn-cancel{background:#dc3545;}
    .btn-cancel:hover{background:#a71d2a;}
    .btn-delete{background:#c0392b;}
    .btn-delete:hover{background:#922b21;}
     .btn-add {background:#444;color:#fff;margin-bottom:15px;padding:10px 16px;border-radius:6px;}

    textarea,input[type=text],input[type=number]{width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;margin-top:4px;}

    /* Messages */
    .message{padding:12px 15px;margin:20px 0;border-radius:8px;font-weight:600;color:#fff;}
    .success{background:#28a745;}
    .error{background:#dc3545;}

    /* Pagination */
    .pagination{display:flex;justify-content:center;margin:25px 0;gap:8px;}
    .pagination a{padding:8px 14px;border-radius:6px;background:#f1f1f1;color:#333;text-decoration:none;font-weight:500;}
    .pagination a:hover{background:#ccc;}
    .pagination a.active{background:#444;color:#fff;}

    /* Modal */
    .modal{display:none;position:fixed;z-index:3000;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,.5);}
    .modal-content{background:#fff;margin:10% auto;padding:20px;border-radius:10px;max-width:500px;position:relative;box-shadow:0 5px 20px rgba(0,0,0,0.2);}
    .modal h3{margin-top:0;}
    .close{color:#aaa;position:absolute;right:15px;top:10px;font-size:24px;cursor:pointer;}
    .close:hover{color:#000;}

    @media(max-width:768px){
      table{font-size:0.9rem;}
    }
  </style>
</head>
<body>
<header>
  <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('show')">
    <span></span><span></span><span></span>
  </div>
  <h1>Career Roadmap Management</h1>
</header>

<div class="sidebar">
  <a href="dashboard.php">Dashboard</a>
  <a href="admin-users.php">User Management</a>
  <a href="admin-content.php">Career Content</a>
  <a href="admin-certificates.php">Certificates</a>
  <a href="admin-roadmaps.php" class="active">Career Roadmaps</a>
  <a href="logout.php">Logout</a>
</div>

<div class="container">
  <h2>Career Roadmaps</h2>
  <?php if(!empty($message)):?><div class="message success"><?=$message?></div><?php endif;?>
  <?php if(!empty($error)):?><div class="message error"><?=$error?></div><?php endif;?>

  <button class="btn btn-add" onclick="document.getElementById('addModal').style.display='block'">+ Add New Roadmap Step</button>

  <table>
    <thead><tr><th>ID</th><th>Career ID</th><th>Step #</th><th>Title</th><th>Detail</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r):?>
    <tr>
      <form method="POST">
        <td><?=$r['id']?><input type="hidden" name="edit_id" value="<?=$r['id']?>"></td>
        <td><span class="view-mode"><?=$r['career_id']?></span><input class="edit-mode" type="number" name="career_id" value="<?=$r['career_id']?>" style="display:none;"></td>
        <td><span class="view-mode"><?=$r['step_number']?></span><input class="edit-mode" type="number" name="step_number" value="<?=$r['step_number']?>" style="display:none;"></td>
        <td><span class="view-mode"><?=htmlspecialchars($r['step_title'])?></span><input class="edit-mode" type="text" name="step_title" value="<?=htmlspecialchars($r['step_title'])?>" style="display:none;"></td>
        <td><span class="view-mode"><?=nl2br(htmlspecialchars($r['step_detail']))?></span><textarea class="edit-mode" name="step_detail" style="display:none;"><?=htmlspecialchars($r['step_detail'])?></textarea></td>
        <td>
          <button type="button" class="btn btn-edit view-mode" onclick="startEdit(this)">Edit</button>
          <button type="submit" class="btn btn-save edit-mode" style="display:none;">Save</button>
          <button type="button" class="btn btn-cancel edit-mode" style="display:none;" onclick="cancelEdit(this)">Cancel</button>
      </form>
      <form method="POST" style="display:inline;">
        <input type="hidden" name="delete_id" value="<?=$r['id']?>">
        <button type="submit" class="btn btn-delete" onclick="return confirm('Delete this roadmap step?');">Delete</button>
      </form>
        </td>
    </tr>
    <?php endforeach;?>
    </tbody>
  </table>

  <div class="pagination">
    <?php for($i=1;$i<=$total_pages;$i++):?>
      <a href="?page=<?=$i?>" class="<?=($i==$page)?'active':''?>"><?=$i?></a>
    <?php endfor;?>
  </div>
</div>

<!-- Modal -->
<div id="addModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
    <h3>Add New Roadmap Step</h3>
    <form method="POST">
      <input type="hidden" name="add_new" value="1">
      <label>Career ID:</label><input type="number" name="career_id" required>
      <label>Step #:</label><input type="number" name="step_number" required>
      <label>Title:</label><input type="text" name="step_title" required>
      <label>Detail:</label><textarea name="step_detail"></textarea>
      <button type="submit" class="btn btn-save">Add Step</button>
    </form>
  </div>
</div>

<script>
function startEdit(btn){toggle(btn.closest("tr"),true);}
function cancelEdit(btn){toggle(btn.closest("tr"),false);}
function toggle(row,edit){
  row.querySelectorAll(".view-mode").forEach(el=>el.style.display=edit?"none":"");
  row.querySelectorAll(".edit-mode").forEach(el=>el.style.display=edit?"":"none");
}
// Close modal if clicked outside
window.onclick=function(e){
  const modal=document.getElementById('addModal');
  if(e.target==modal){modal.style.display="none";}
}
</script>
</body>
</html>
