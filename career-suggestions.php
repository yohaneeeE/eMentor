<?php
session_start();

include 'db_connect.php';

// --- Session vars for sidebar ---
$isLoggedIn = $_SESSION['logged_in'] ?? false;
$fullName   = $_SESSION['full_name'] ?? '';

// --- Handle Save Results POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'storeResult') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $careerPrediction = $_POST['careerPrediction'] ?? '';
    $subjects = isset($_POST['subjects']) ? json_decode($_POST['subjects'], true) : [];

    try {
        // Insert student
        $stmt = $pdo->prepare("INSERT INTO students (name, email, careerPrediction) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $careerPrediction]);
        $studentId = $pdo->lastInsertId();

        // Insert subjects
        if (!empty($subjects) && is_array($subjects)) {
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

// -----------------------------
// Get API response (from POST or fallback to sessionStorage via JS)
// -----------------------------
$input = json_decode(file_get_contents('php://input'), true);

$careerPrediction = $input['careerPrediction'] ?? '';
$careerOptions    = $input['careerOptions'] ?? [];
$rawSubjects      = $input['rawSubjects'] ?? [];
$mappedSkills     = $input['mappedSkills'] ?? [];
$certificates     = $input['certificates'] ?? [];

// Collect career titles for DB lookup
$careerTitles = [];
foreach ($careerOptions as $c) {
    if (is_array($c)) {
        if (isset($c['career'])) $careerTitles[] = $c['career'];
        elseif (isset($c['title'])) $careerTitles[] = $c['title'];
    } else {
        $careerTitles[] = $c;
    }
}
$careerTitles = array_unique($careerTitles);

// Lookup descriptions from DB
$careersData = [];
if (!empty($careerTitles)) {
    $placeholders = rtrim(str_repeat('?,', count($careerTitles)), ',');
    $sql = "SELECT title, category, description FROM careers WHERE title IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($careerTitles);
    $careersData = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// If DB has no matches, still show predictions
if (!$careersData && !empty($careerTitles)) {
    foreach ($careerTitles as $career) {
        $careersData[] = [
            'title' => $career,
            'category' => 'N/A',
            'description' => 'No description available from database.'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Career Suggestions</title>
  <style>
/* Base */
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
header h1 {
  margin: 0;
  font-size: 2.4rem;
  letter-spacing: 1px;
}
header p {
  margin: 5px 0 0;
  font-size: 1rem;
}

/* Back button */
.back-btn {
  display: block;
  margin: 20px auto;
  text-align: center;
}
.back-btn a {
  display: inline-block;
  padding: 10px 20px;
  background: #555;
  color: grey;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  transition: background 0.3s ease, transform 0.2s ease;
}
.back-btn a:hover {
  background: #777;
  transform: translateY(-2px);
}

/* Main container */
.container {
  flex: 1;
  max-width: 1000px;
  margin: 20px auto;
  padding: 30px;
  background: #fff;
  border-radius: 15px;
  box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}

/* Section headings */
h2 {
  text-align: center;
  color: #444;
  margin-bottom: 25px;
  font-size: 1.8rem;
}

/* Card/Box style */
.box {
  background: #fafafa;
  border: 1px solid #ddd;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 25px;
  transition: box-shadow 0.3s ease;
}
.box:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.box h3 {
  margin-top: 0;
  color: #444;
  font-size: 1.2rem;
  border-bottom: 1px solid #e0e0e0;
  padding-bottom: 8px;
  margin-bottom: 15px;
}

/* Tables */
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
  border-radius: 10px;
  overflow: hidden;
}
table th, table td {
  border: 1px solid #ccc;
  padding: 10px;
  text-align: center;
}
table th {
  background: #555;
  color: #fff;
  font-weight: 600;
}

/* Career sub-box */
.sub-career {
  border: 1px solid #ddd;
  border-radius: 12px;
  padding: 15px;
  margin: 15px 0;
  background: #f9f9f9;
  transition: transform 0.2s ease;
}
.sub-career:hover {
  transform: scale(1.01);
}
.sub-career h4 {
  margin: 0 0 8px;
  color: #444;
}

/* Certificates */
.cert-list {
  margin: 8px 0 0 20px;
  color: #444;
  font-size: 0.95rem;
}

/* Footer sticky */
footer {
  text-align: center;
  padding: 20px;
  background: linear-gradient(135deg, #333, #555);
  color: #fff;
  margin-top: auto;
  font-size: 0.9rem;
  box-shadow: 0 -3px 10px rgba(0, 0, 0, 0.15);
}

/* ===================== */
/* Sidebar + Hamburger   */
/* ===================== */
.sidebar {
  position: fixed;
  top: 0;
  left: -260px; /* hidden by default */
  width: 260px;
  height: 100%;
  background: #333;
  color: #fff;
  padding-top: 70px;
  transition: left 0.3s ease;
  z-index: 1000;
  overflow-y: auto;
}

/* Sidebar open */
.sidebar.active {
  left: 0;
}

/* Sidebar links */
.sidebar a {
  display: block;
  padding: 14px 20px;
  color: #fff;
  text-decoration: none;
  font-weight: 500;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  transition: background 0.2s ease;
}
.sidebar a:hover {
  background: rgba(255,255,255,0.1);
}

/* User info */
.sidebar .user-info {
  padding: 15px 20px;
  font-size: 0.9rem;
  border-top: 1px solid rgba(255,255,255,0.2);
  margin-top: 10px;
  background: rgba(255,255,255,0.05);
  border-radius: 6px;
}

/* Overlay */
.overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  display: none;
  z-index: 999;
}
.overlay.active {
  display: block;
}

/* Hamburger button */
.hamburger {
  position: fixed;
  top: 18px;
  left: 20px;
  width: 30px;
  height: 22px;
  cursor: pointer;
  z-index: 1100;
}
.hamburger span {
  display: block;
  width: 100%;
  height: 4px;
  margin: 4px 0;
  background: #fff;
  border-radius: 2px;
  transition: 0.3s;
}

/* Animate hamburger into X */
.hamburger.active span:nth-child(1) {
  transform: rotate(45deg) translate(5px, 6px);
}
.hamburger.active span:nth-child(2) {
  opacity: 0;
}
.hamburger.active span:nth-child(3) {
  transform: rotate(-45deg) translate(6px, -6px);
}

/* Push content when sidebar is open */
body.sidebar-open .container,
body.sidebar-open header,
body.sidebar-open footer {
  margin-left: 260px;
  transition: margin-left 0.3s ease;
}

/* Responsive */
@media (max-width: 768px) {
  body.sidebar-open .container,
  body.sidebar-open header,
  body.sidebar-open footer {
    margin-left: 0; /* don't push content on mobile */
  }
}

  </style>
</head>
<body>

<!-- Hamburger -->
<div class="hamburger" id="hamburger">
  <span></span><span></span><span></span>
</div>

<header>
  <h1>eMentor</h1>
  <p>Your digital career guidance</p>
</header>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <a href="dashboard.php">Home</a>
    <a href="career-guidance.php">Career Guidance</a>
    <a href="careerpath.php">Career Path</a>
    <a href="about.php">About</a>
    <hr style="border: 1px solid rgba(255,255,255,0.2);">
    <?php if ($isLoggedIn): ?>
        <a href="settings.php">Settings</a>
        <a href="logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
        <div class="user-info">
            Logged in as <br><strong><?php echo htmlspecialchars($fullName); ?></strong>
        </div>
    <?php else: ?>
        <a href="login.php">Login</a>
    <?php endif; ?>
</div>

<div class="overlay" id="overlay"></div>

<div class="container">
  <h2>Career Suggestions Based on Your Transcript</h2>

  <!-- Subjects -->
  <div class="box">
    <h3>📄 All Subjects (Scanned Transcript)</h3>
    <table><thead><tr><th>Subject</th><th>Grade</th></tr></thead><tbody id="rawTableBody"></tbody></table>
  </div>

  <!-- Skills -->
  <div class="box">
    <h3>🧠 Skill Mapping</h3>
    <table><thead><tr><th>Skill</th><th>Level</th></tr></thead><tbody id="skillsTableBody"></tbody></table>
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
    <button id="printBtn" style="padding:12px 20px;background:grey;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:bold;">
       🖨️ Print Results
    </button>
    <p id="printMsg" style="margin-top:10px;color:green;display:none;"></p>
  </div>


<footer>
  <p>&copy; 2025 Mapping The Future System. All rights reserved.</p>
</footer>

<script>
// --------- Fallback to sessionStorage if POST data was empty ----------

let rawSubjects   = <?= json_encode($rawSubjects) ?>;
let mappedSkills  = <?= json_encode($mappedSkills) ?>;
let careerOptions = <?= json_encode($careerOptions) ?>;
let certificates  = <?= json_encode($certificates) ?>;

// Debug
console.log("Raw Subjects:", rawSubjects);
console.log("Mapped Skills:", mappedSkills);
console.log("Career Options:", careerOptions);
console.log("Certificates:", certificates);

if ((!rawSubjects || rawSubjects.length === 0) && sessionStorage.apiResult) {
  try {
    const apiResult = JSON.parse(sessionStorage.apiResult);
    rawSubjects   = apiResult.rawSubjects   || [];
    mappedSkills  = apiResult.mappedSkills  || {};
    careerOptions = apiResult.careerOptions || [];
    certificates  = apiResult.certificates  || [];
  } catch (e) {
    console.warn("Failed to parse apiResult from sessionStorage", e);
  }
}

// ---------- SUBJECTS ----------
const rawTableBody = document.getElementById("rawTableBody");
rawTableBody.innerHTML = "";

if (rawSubjects && typeof rawSubjects === "object") {
  if (Array.isArray(rawSubjects)) {
    rawSubjects.forEach(([subject, grade]) => {
      const cleanSubject = subject.trim();
      const numGrade = parseFloat(grade);
      const isValidGrade = !isNaN(numGrade) && numGrade > 0 && numGrade <= 100;
      const looksLikeSubject = cleanSubject.length > 3 && /[a-zA-Z]/.test(cleanSubject);
      if (isValidGrade && looksLikeSubject) {
        rawTableBody.innerHTML += `<tr><td>${cleanSubject}</td><td>${numGrade.toFixed(2)}</td></tr>`;
      }
    });
  } else {
    Object.entries(rawSubjects).forEach(([subject, grade]) => {
      rawTableBody.innerHTML += `<tr><td>${subject}</td><td>${isNaN(grade) ? grade : Number(grade).toFixed(2)}</td></tr>`;
    });
  }
} else {
  rawTableBody.innerHTML = `<tr><td colspan="2">No subjects available.</td></tr>`;
}

// ---------- SKILLS ----------
const skillsTableBody = document.getElementById("skillsTableBody");
skillsTableBody.innerHTML = "";
if (Object.keys(mappedSkills).length > 0) {
  Object.entries(mappedSkills).forEach(([skill, level]) => {
    const cleanSkill = skill.trim();
    const looksLikeSkill = cleanSkill.length > 3 && /[a-zA-Z]/.test(cleanSkill);
    const hasValidLevel = level && level.toLowerCase() !== "null";
    if (looksLikeSkill && hasValidLevel) {
      skillsTableBody.innerHTML += `<tr><td>${cleanSkill}</td><td>${level}</td></tr>`;
    }
  });
} else {
  skillsTableBody.innerHTML = `<tr><td colspan="2">No skills detected.</td></tr>`;
}

// ---------- CAREER MATCHES ----------
const careerMatchesBox = document.getElementById("careerMatchesBox");
const careerMatchesList = document.getElementById("careerMatchesList");
const suggestBox = document.getElementById("suggestBox");
const suggestList = document.getElementById("suggestList");

if (Array.isArray(careerOptions) && careerOptions.length > 0) {
  careerMatchesBox.style.display = "block";
  suggestBox.style.display = "block";

  let suggestionSet = new Set();
  careerMatchesList.innerHTML = careerOptions.map(c => {
    if (c.suggestion) suggestionSet.add(c.suggestion);
    return `<li><strong>${c.career}</strong> - Confidence: ${c.confidence ? c.confidence.toFixed(1) : "N/A"}%<br><em>${c.suggestion || ""}</em></li>`;
  }).join("");

  suggestList.innerHTML = [...suggestionSet].map(s => `<li>${s}</li>`).join("");
}

// ---------- CERTIFICATES ----------
document.querySelectorAll(".cert-list").forEach(listEl => {
  const careerName = listEl.dataset.career;
  listEl.innerHTML = "";
  const careerBased = (careerOptions.find(c => c.career === careerName) || {}).certificates || [];
  if (Array.isArray(careerBased) && careerBased.length > 0) {
    careerBased.forEach(cert => {
      listEl.innerHTML += `<li>🎓 ${cert}</li>`;
    });
  }
  if (Array.isArray(certificates) && certificates.length > 0) {
    certificates.forEach(cert => {
      if (Array.isArray(cert.suggestions)) {
        cert.suggestions.forEach(s => {
          listEl.innerHTML += `<li>📄 ${cert.file ? cert.file + ": " : ""}${s}</li>`;
        });
      }
    });
  }
  if (!listEl.innerHTML) {
    listEl.innerHTML = `<li>No certificate suggestions.</li>`;
  }
});

// ---------- FINAL CAREER BOX ----------
if (careerOptions.length > 0) {
  document.getElementById("careerPathBox").style.display = "block";
}

document.getElementById("saveBtn").addEventListener("click", async () => {
  const payload = {
    action: "storeResult",
    name: "Carlo Test",
    email: "carlo@example.com",
    careerPrediction: (careerOptions[0] && careerOptions[0].career) || "N/A",
    subjects: JSON.stringify(rawSubjects.map(([subject, grade]) => ({ subject, grade })))
  };

  const res = await fetch(window.location.href, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(payload)
  });

  const result = await res.json();
  const msg = document.getElementById("saveMsg");
  if (result.status === "success") {
    msg.style.display = "block";
    msg.style.color = "green";
    msg.textContent = "Results saved successfully!";
  } else {
    msg.style.display = "block";
    msg.style.color = "red";
    msg.textContent = " Failed to save: " + result.message;
  }
});

  const hamburger = document.querySelector(".hamburger");
  const sidebar   = document.querySelector(".sidebar");
  const overlay   = document.querySelector(".overlay");
  const body      = document.body;

  hamburger.addEventListener("click", () => {
    hamburger.classList.toggle("active");
    sidebar.classList.toggle("active");
    overlay.classList.toggle("active");
    body.classList.toggle("sidebar-open");
  });

  overlay.addEventListener("click", () => {
    hamburger.classList.remove("active");
    sidebar.classList.remove("active");
    overlay.classList.remove("active");
    body.classList.remove("sidebar-open");
  });
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
// ---------- PRINT FUNCTION ----------
document.getElementById("printBtn").addEventListener("click", async () => {
  const container = document.querySelector(".container");
  const msg = document.getElementById("printMsg");

  msg.style.display = "block";
  msg.textContent = "Generating printable version...";
  msg.style.color = "gray";

  // Hide sidebar and hamburger for clean print
  document.querySelector(".sidebar").style.display = "none";
  document.querySelector(".hamburger").style.display = "none";

  try {
    const canvas = await html2canvas(container, {
      scale: 2,
      backgroundColor: "#fff"
    });
    const imgData = canvas.toDataURL("image/png");

    // Open printable image in new tab
    const newWindow = window.open("", "_blank");
    newWindow.document.write(`
      <html>
      <head><title>Career Suggestions</title></head>
      <body style="text-align:center;font-family:sans-serif;">
        <h2>Career Suggestions Snapshot</h2>
        <img src="${imgData}" style="max-width:100%;height:auto;border:1px solid #ccc;border-radius:10px;"/>
        <script>window.print();<\/script>
      </body>
      </html>
    `);
    newWindow.document.close();

    msg.textContent = "Printable version opened successfully!";
    msg.style.color = "green";
  } catch (err) {
    console.error(err);
    msg.textContent = "Failed to generate print preview.";
    msg.style.color = "red";
  } finally {
    // Restore UI
    document.querySelector(".sidebar").style.display = "";
    document.querySelector(".hamburger").style.display = "";
  }
});
</script>

</script>
</body>
</html>
