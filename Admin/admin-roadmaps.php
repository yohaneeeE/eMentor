<?php
// DB Connection
$host='localhost';$port=3307;$db='careerguidance';$user='root';$pass='';$charset='utf8mb4';
$dsn="mysql:host=$host;port=$port;dbname=$db;charset=$charset";
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

// Fetch all
$stmt=$pdo->query("SELECT * FROM career_roadmaps ORDER BY career_id, step_number");
$rows=$stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8"><title>Roadmap Management</title>
  <style>
    body{font-family:Arial;background:#f9f9f9;margin:0;}
    header{background:linear-gradient(135deg,#004080,#0066cc);color:#fff;text-align:center;padding:20px;}
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
    .container{max-width:1200px;margin:30px auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.1);}
    table{width:100%;border-collapse:collapse;}th,td{padding:10px;border-bottom:1px solid #ddd;}th{background:#004080;color:#fff;text-align:left;}
    .btn{padding:6px 12px;border:none;border-radius:5px;cursor:pointer;}
    .btn-edit{background:#0066cc;color:#fff;}
    .btn-save{background:#28a745;color:#fff;}
    .btn-cancel{background:#dc3545;color:#fff;}
    .btn-delete{background:#dc3545;color:#fff;}
    textarea,input[type=text],input[type=number]{width:100%;padding:6px;}
    .message{padding:10px;margin:15px 0;border-radius:5px;}
    .success{background:#28a745;color:#fff;}.error{background:#dc3545;color:#fff;}
    h3{margin-top:40px;}
  </style>
</head>
<body>
<header><h1>Career Roadmap Management</h1></header>
<nav>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="admin-users.php">User Management</a></li>
        <li><a href="admin-content.php">Career Content</a></li>
        <li><a href="admin-certificates.php">Certificates</a></li>
        <li><a href="admin-roadmaps.php" class="active">Career Roadmaps</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<div class="container">
<h2>Career Roadmaps</h2>
<?php if(!empty($message)):?><div class="message success"><?=$message?></div><?php endif;?>
<?php if(!empty($error)):?><div class="message error"><?=$error?></div><?php endif;?>

<!-- Add new roadmap form -->
<h3>Add New Roadmap Step</h3>
<form method="POST">
  <input type="hidden" name="add_new" value="1">
  <label>Career ID:</label><input type="number" name="career_id" required>
  <label>Step #:</label><input type="number" name="step_number" required>
  <label>Title:</label><input type="text" name="step_title" required>
  <label>Detail:</label><textarea name="step_detail"></textarea>
  <button type="submit" class="btn btn-save">Add Step</button>
</form>

<hr>

<table>
<thead><tr><th>ID</th><th>Career ID</th><th>Step #</th><th>Title</th><th>Detail</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($rows as $r):?>
<tr>
<form method="POST">
<td><?=$r['id']?><input type="hidden" name="edit_id" value="<?=$r['id']?>"></td>
<td><span class="view-mode"><?=$r['career_id']?></span>
<input class="edit-mode" type="text" name="career_id" value="<?=$r['career_id']?>" style="display:none;"></td>
<td><span class="view-mode"><?=$r['step_number']?></span>
<input class="edit-mode" type="number" name="step_number" value="<?=$r['step_number']?>" style="display:none;"></td>
<td><span class="view-mode"><?=htmlspecialchars($r['step_title'])?></span>
<input class="edit-mode" type="text" name="step_title" value="<?=htmlspecialchars($r['step_title'])?>" style="display:none;"></td>
<td><span class="view-mode"><?=nl2br(htmlspecialchars($r['step_detail']))?></span>
<textarea class="edit-mode" name="step_detail" style="display:none;"><?=htmlspecialchars($r['step_detail'])?></textarea></td>
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
</div>

<script>
function startEdit(btn){toggle(btn.closest("tr"),true);}
function cancelEdit(btn){toggle(btn.closest("tr"),false);}
function toggle(row,edit){
  row.querySelectorAll(".view-mode").forEach(el=>el.style.display=edit?"none":"");
  row.querySelectorAll(".edit-mode").forEach(el=>el.style.display=edit?"":"none");
}
</script>
</body>
</html>
