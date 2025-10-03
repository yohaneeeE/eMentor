<?php
$host = 'localhost';
$port = '3307';
$dbname = 'careerguidance';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'storeResult') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $careerPrediction = $_POST['careerPrediction'] ?? '';
    $subjects = $_POST['subjects'] ?? [];

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
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f4f7fb; margin:0; }
    header { background:linear-gradient(135deg,#004080,#0066cc);color:#fff;text-align:center;padding:25px 0; }
    header h1 { margin:0;font-size:2.2rem; }
    .back-btn { display:block; margin:20px auto; text-align:center; }
    .back-btn a { display:inline-block; padding:10px 18px; background:#004080; color:#fff; border-radius:8px; text-decoration:none; font-weight:bold; }
    .back-btn a:hover { background:#0066cc; }
    .container { max-width:1000px;margin:20px auto;padding:25px;background:#fff;border-radius:15px;box-shadow:0 5px 20px rgba(0,0,0,0.1); }
    h2 { text-align:center;color:#004080;margin-bottom:20px; }
    .box { background:#fdfdfd;border:2px solid #e0e9ff;border-radius:12px;padding:20px;margin-bottom:25px; }
    .box h3 { margin-top:0;color:#004080;font-size:1.2rem; }
    table { width:100%;border-collapse:collapse;margin-top:10px; }
    table th, table td { border:1px solid #d0dcff;padding:8px;text-align:center; }
    table th { background:#004080;color:#fff; }
    .sub-career { border:2px solid #e0e9ff;border-radius:12px;padding:15px;margin:12px 0;background:#fafcff; }
    .sub-career h4 { margin:0 0 8px;color:#004080; }
    .cert-list { margin:8px 0 0 20px; color:#333; }
    footer { text-align:center;padding:20px;background:linear-gradient(135deg,#003060,#004080);color:#fff;margin-top:50px; }
  </style>
</head>
<body>
<header>
  <h1>eMentor</h1>
  <p>Your digital career guidance</p>
</header>

<div class="back-btn">
  <a href="careerpath.php">⬅ Back to Career Path</a>
</div>

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
  <button id="saveBtn" style="padding:12px 20px;background:#004080;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:bold;">
    💾 Save Results
  </button>
  <p id="saveMsg" style="margin-top:10px;color:green;display:none;"></p>
</div>

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

// 🔍 DEBUG FRONTEND DATA
console.log("=== FRONTEND DEBUG START ===");
console.log("Raw Subjects:", rawSubjects);
console.log("Mapped Skills:", mappedSkills);
console.log("Career Options:", careerOptions);
console.log("Certificates:", certificates);
console.log("=== FRONTEND DEBUG END ===");

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
    // Case 1: rawSubjects is an array of [subject, grade]
rawSubjects.forEach(([subject, grade]) => {
  // Clean subject name
  const cleanSubject = subject.trim();

  // Parse grade
  const numGrade = parseFloat(grade);

  // Filtering rules
  const isValidGrade = !isNaN(numGrade) && numGrade > 0 && numGrade <= 100;
  const looksLikeSubject = cleanSubject.length > 3 && /[a-zA-Z]/.test(cleanSubject);

  if (isValidGrade && looksLikeSubject) {
    rawTableBody.innerHTML += `<tr>
      <td>${cleanSubject}</td>
      <td>${numGrade.toFixed(2)}</td>
    </tr>`;
  }
});

  } else {
    // Case 2: rawSubjects is an object/dict
    Object.entries(rawSubjects).forEach(([subject, grade]) => {
      rawTableBody.innerHTML += `<tr>
        <td>${subject}</td>
        <td>${isNaN(grade) ? grade : Number(grade).toFixed(2)}</td>
      </tr>`;
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

  // Filtering rules
  const looksLikeSkill = cleanSkill.length > 3 && /[a-zA-Z]/.test(cleanSkill);
  const hasValidLevel = level && level.toLowerCase() !== "null";

  if (looksLikeSkill && hasValidLevel) {
    skillsTableBody.innerHTML += `<tr>
      <td>${cleanSkill}</td>
      <td>${level}</td>
    </tr>`;
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
    return `<li>
      <strong>${c.career}</strong> - Confidence: ${c.confidence ? c.confidence.toFixed(1) : "N/A"}%<br>
      <em>${c.suggestion || ""}</em>
    </li>`;
  }).join("");

  suggestList.innerHTML = [...suggestionSet].map(s => `<li>${s}</li>`).join("");
}

// ---------- CERTIFICATES ----------
document.querySelectorAll(".cert-list").forEach(listEl => {
  const careerName = listEl.dataset.career;
  listEl.innerHTML = "";

  // 1. Career-based recommendations
  const careerBased = (careerOptions.find(c => c.career === careerName) || {}).certificates || [];
  if (Array.isArray(careerBased) && careerBased.length > 0) {
    careerBased.forEach(cert => {
      listEl.innerHTML += `<li>🎓 ${cert}</li>`;
    });
  }

  // 2. Uploaded certificate matches
  if (Array.isArray(certificates) && certificates.length > 0) {
    certificates.forEach(cert => {
      cert.suggestions.forEach(s => {
        listEl.innerHTML += `<li>📄 ${cert.file ? cert.file + ": " : ""}${s}</li>`;
      });
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
    name: "Carlo Test",  // you can replace with $_SESSION['user'] later
    email: "carlo@example.com",
    careerPrediction: (careerOptions[0] && careerOptions[0].career) || "N/A",
    subjects: rawSubjects.map(([subject, grade]) => ({ subject, grade }))
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

</script>
</body>
</html>
