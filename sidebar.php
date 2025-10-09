<?php
// sidebar.php
session_start
$isLoggedIn = isset($_SESSION['fullName']);
$fullName = $isLoggedIn ? $_SESSION['fullName'] : null;
?>
<div class="sidebar" id="sidebar">
    <a href="dashboard.php">Home</a>
    <a href="career-guidance.php">Career Guidance</a>
    <a href="careerpath.php">Career Path</a>
    <a href="about.php">About</a>
    <hr style="border: 1px solid rgba(255,255,255,0.2);">
    <?php if ($isLoggedIn): ?>
        <a href="settings.php">Settings</a>
        <a href="logout.php" onclick="return confirm('Logout?');">Logout</a>
        <div class="user-info">
            Logged in as <br><strong><?php echo htmlspecialchars($fullName); ?></strong>
        </div>
    <?php else: ?>
        <a href="login.php">Login</a>
    <?php endif; ?>
</div>
<div class="overlay" id="overlay"></div>

<style>
.sidebar { position: fixed; top:0; left:-250px; width:250px; height:100%; background:#333; color:white; padding:60px 20px; display:flex; flex-direction:column; gap:20px; transition:left 0.3s ease; z-index:200; }
.sidebar.active { left:0; }
.sidebar a { color:white; text-decoration:none; padding:8px 0; display:block; }
.sidebar a:hover { color:#ffcc00; transform:translateX(5px); }
.user-info { margin-top:auto; padding-top:15px; border-top:1px solid rgba(255,255,255,0.2); text-align:center; color:#ffcc00; }
.overlay { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); opacity:0; visibility:hidden; transition:opacity 0.3s ease; z-index:100; }
.overlay.active { opacity:1; visibility:visible; }
</style>

<script>
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
function toggleSidebar(){
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}
overlay.addEventListener('click', ()=>{
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
});
</script>
