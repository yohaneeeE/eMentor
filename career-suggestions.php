<?php
session_start();
include 'db_connect.php';

// --- Session vars for sidebar ---
$isLoggedIn = $_SESSION['logged_in'] ?? false;
$fullName   = $_SESSION['full_name'] ?? '';

// --- Handle Save Results POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'storeResult') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $careerPrediction = $_POST['careerPrediction'] ?? '';
    $subjects = isset($_POST['subjects']) ? json_decode($_POST['subjects'], true) : [];

    try {
        $stmt = $pdo->prepare("INSERT INTO students (name, email, careerPrediction) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $careerPrediction]);
        $studentId = $pdo->lastInsertId();

        if (!empty($subjects)) {
            $stmtSub = $pdo->prepare("INSERT INTO subjects (student_id, description, grade) VALUES (?, ?, ?)");
            foreach ($subjects as $s) {
                $desc = $s['subject'] ?? '';
                $grade = $s['grade'] ?? '';
                $stmtSub->execute([$studentId, $desc, $grade]);
            }
        }

        echo json_encode(["status" => "success", "studentId" => $studentId]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// --- Get JSON data from API or JS fetch ---
$input = json_decode(file_get_contents('php://input'), true);

$careerPrediction = $input['careerPrediction'] ?? '';
$careerOptions    = $input['careerOptions'] ?? [];
$rawSubjects      = $input['rawSubjects'] ?? [];
$mappedSkills     = $input['mappedSkills'] ?? [];
$certificates     = $input['certificates'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Career Suggestions</title>
<style>
/* --- Base Styling --- */
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: #f4f6f9;
  margin: 0;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* Header */
header {
  background: linear-gradient(135deg, #444, #666);
  color: #fff;
  text-align: center;
  padding: 25px 0;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
}
header h1 { margin: 0; font-size: 2.4rem; }
header p { margin: 5px 0 0; font-size: 1rem; }

/* Sidebar */
.sidebar {
  position: fixed; top: 0; left: -260px;
  width: 260px; height: 100%;
  background: #333; color: #fff;
  transition: left 0.3s ease; z-index: 1000;
  padding-top: 70px;
}
.sidebar.active { left: 0; }
.sidebar a {
  display: block; padding: 14px 20px;
  color: #fff; text-decoration: none;
  border-bottom: 1px solid rgba(255,255,255,0.1);
}
.sidebar a:hover { background: rgba(255,255,255,0.1); }

/* User info */
.user-info { padding: 15px 20px; font-size: 0.9rem; }

/* Hamburger */
.hamburger {
  position: fixed; top: 18px; left: 20px;
  width: 30px; height: 22px; cursor: pointer; z-index: 1100;
}
.hamburger span {
  display: block; width: 100%; height: 4px;
  margin: 4px 0; background: #fff; border-radius: 2px;
  transition: 0.3s;
}
.hamburger.active span:nth-child(1) {
  transform: rotate(45deg) translate(5px, 6px);
}
.hamburger.active span:nth-child(2) { opacity: 0; }
.hamburger.active span:nth-child(3) {
  transform: rotate(-45deg) translate(6px, -6px);
}
.overlay {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.5); display: none; z-index: 999;
}
.overlay.active { display: block; }

/* Content */
.container {
  flex: 1; max-width: 1000px;
  margin: 20px auto; padding: 30px;
  background: #fff; border-radius: 15px;
  box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}

/* Boxes */
.box {
  background: #fafafa; border: 1px solid #ddd;
  border-radius: 12px; padding: 20px; margin-bottom: 25px;
}
h3 { margin-top: 0; color: #444; }

/* Table */
table {
  width: 100%; border-collapse: collapse;
}
th, td {
  border: 1px solid #ccc; padding: 10px;
  text-align: center;
}
th { background: #555; color: #fff; }

/* Footer */
footer {
  text-align: center; padding: 20px;
  background: linear-gradient(135deg, #333, #555);
  color: #fff; margin-top: auto;
}
</style>
</head>
<body>

<!-- Hamburger -->
<div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>

<header>
  <h1>eMentor</h1>
  <p>Your Digital Career Guidance</p>
</header>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <a href="dashboard.php">Home</a>
  <a href="career-guidance.php">Career Guidance</a>
  <a href="careerpath.php">Career Path</a>
  <a href="about.php">About</a>
  <hr style="border:1px solid rgba(255,255,255,0.2);">
  <?php if ($isLoggedIn): ?>
    <a href="settings.php">Settings</a>
    <a href="logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
    <div class="user-info">Logged in as <strong><?= htmlspecialchars($fullName) ?></strong></div>
  <?php else: ?>
    <a href="login.php">Login</a>
  <?php endif; ?>
</div>

<div class="overlay" id="overlay"></div>

<div class="container">
  <h2>Career Suggestions Based on Your Transcript</h2>

  <!-- Subjects -->
  <div class="box">
    <h3>📄 All Subjects</h3>
    <table><thead><tr><th>Subject</th><th>Grade</th></tr></thead>
    <tbody id="rawTableBody"></tbody></table>
  </div>

  <!-- Skills -->
  <div class="box">
    <h3>🧠 Skill Mapping</h3>
    <table><thead><tr><th>Skill</th><th>Level</th></tr></thead>
    <tbody id="skillsTableBody"></tbody></table>
  </div>

  <!-- Suggestions -->
  <div class="box" id="suggestBox" style="display:none;">
    <h3>💡 Suggestions</h3>
    <ul id="suggestList"></ul>
  </div>

  <!-- Career Matches -->
  <div class="box" id="careerMatchesBox" style="display:none;">
    <h3>🏆 Top Career Matches</h3>
    <ul id="careerMatchesList"></ul>
  </div>

  <div class="box" style="text-align:center;">
    <button id="saveBtn" style="padding:12px 20px;background:#333;color:#fff;border:none;border-radius:8px;cursor:pointer;">
      💾 Save Results
    </button>
    <button id="printBtn" style="padding:12px 20px;background:grey;color:#fff;border:none;border-radius:8px;cursor:pointer;margin-left:10px;">
      🖨️ Print Results
    </button>
    <p id="saveMsg" style="margin-top:10px;display:none;"></p>
    <p id="printMsg" style="margin-top:10px;display:none;"></p>
  </div>
</div>

<footer>
  <p>&copy; 2025 Mapping The Future System. All rights reserved.</p>
</footer>

<script>
// ----------- INITIAL DATA -----------
let rawSubjects   = <?= json_encode($rawSubjects) ?>;
let mappedSkills  = <?= json_encode($mappedSkills) ?>;
let careerOptions = <?= json_encode($careerOptions) ?>;
let certificates  = <?= json_encode($certificates) ?>;

console.log("✅ Received Data:", { rawSubjects, mappedSkills, careerOptions, certificates });

// Fallback from sessionStorage
if ((!rawSubjects || rawSubjects.length === 0) && sessionStorage.apiResult) {
  const apiResult = JSON.parse(sessionStorage.apiResult);
  rawSubjects   = apiResult.rawSubjects   || [];
  mappedSkills  = apiResult.mappedSkills  || {};
  careerOptions = apiResult.careerOptions || [];
}

// ----------- Render Subjects -----------
const rawTableBody = document.getElementById("rawTableBody");
if (Array.isArray(rawSubjects) && rawSubjects.length > 0) {
  rawSubjects.forEach(([subject, grade]) => {
    rawTableBody.innerHTML += `<tr><td>${subject}</td><td>${grade}</td></tr>`;
  });
} else {
  rawTableBody.innerHTML = "<tr><td colspan='2'>No subjects detected.</td></tr>";
}

// ----------- Render Skills -----------
const skillsTableBody = document.getElementById("skillsTableBody");
if (Object.keys(mappedSkills).length > 0) {
  for (const [skill, level] of Object.entries(mappedSkills)) {
    skillsTableBody.innerHTML += `<tr><td>${skill}</td><td>${level}</td></tr>`;
  }
} else {
  skillsTableBody.innerHTML = "<tr><td colspan='2'>No skills detected.</td></tr>";
}

// ----------- Render Career Matches -----------
const careerBox = document.getElementById("careerMatchesBox");
const careerList = document.getElementById("careerMatchesList");
const suggestBox = document.getElementById("suggestBox");
const suggestList = document.getElementById("suggestList");

if (Array.isArray(careerOptions) && careerOptions.length > 0) {
  careerBox.style.display = "block";
  suggestBox.style.display = "block";
  let suggestionSet = new Set();

  careerList.innerHTML = careerOptions.map(c => {
    if (c.suggestion) suggestionSet.add(c.suggestion);
    return `<li><strong>${c.career}</strong> - Confidence: ${c.confidence || "N/A"}%<br><em>${c.suggestion || ""}</em></li>`;
  }).join("");

  suggestList.innerHTML = [...suggestionSet].map(s => `<li>${s}</li>`).join("");
}

// ----------- Save Button -----------
document.getElementById("saveBtn").addEventListener("click", async () => {
  const payload = {
    action: "storeResult",
    name: "Student User",
    email: "student@example.com",
    careerPrediction: (careerOptions[0]?.career) || "N/A",
    subjects: JSON.stringify(rawSubjects.map(([subject, grade]) => ({ subject, grade })))
  };

  const res = await fetch(window.location.href, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(payload)
  });

  const result = await res.json();
  const msg = document.getElementById("saveMsg");
  msg.style.display = "block";
  msg.textContent = result.status === "success" ? "✅ Results saved successfully!" : "❌ " + result.message;
  msg.style.color = result.status === "success" ? "green" : "red";
});

// ----------- Print Button -----------
document.getElementById("printBtn").addEventListener("click", () => window.print());

// ----------- Sidebar Toggle -----------
const hamburger = document.getElementById("hamburger");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");

hamburger.addEventListener("click", () => {
  hamburger.classList.toggle("active");
  sidebar.classList.toggle("active");
  overlay.classList.toggle("active");
});
overlay.addEventListener("click", () => {
  hamburger.classList.remove("active");
  sidebar.classList.remove("active");
  overlay.classList.remove("active");
});
</script>
</body>
</html>
