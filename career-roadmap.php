<?php
session_start();

// DB connection (adjust as needed)
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "careerguidance";

$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname, 3307);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get career_id from GET, validate
if (!isset($_GET['career_id']) || !is_numeric($_GET['career_id'])) {
    die("Invalid career ID.");
}
$career_id = intval($_GET['career_id']);

// Fetch career info
$stmt = $conn->prepare("SELECT title, category, description FROM careers WHERE id = ?");
$stmt->bind_param("i", $career_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Career not found.");
}
$career = $result->fetch_assoc();
$stmt->close();

// Fetch roadmap steps ordered by step_number
$stmt = $conn->prepare("SELECT step_number, step_title, step_detail FROM career_roadmaps WHERE career_id = ? ORDER BY step_number ASC");
$stmt->bind_param("i", $career_id);
$stmt->execute();
$roadmap_result = $stmt->get_result();

$roadmap_steps = [];
while ($row = $roadmap_result->fetch_assoc()) {
    $roadmap_steps[] = $row;
}
$stmt->close();

// Fetch certificates for this career
$stmt = $conn->prepare("SELECT certificate_title, provider, description, skills FROM certificates WHERE career_id = ?");
$stmt->bind_param("i", $career_id);
$stmt->execute();
$cert_result = $stmt->get_result();

$certificates = [];
while ($row = $cert_result->fetch_assoc()) {
    $certificates[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Roadmap for <?= htmlspecialchars($career['title']) ?></title>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f9f9f9;
        color: #333;
        margin: 0;
        padding: 0 20px 40px;
    }
    header {
        background: linear-gradient(135deg, #004080, #0066cc);
        color: white;
        padding: 25px 0;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    header h1 {
        margin: 0;
        font-size: 2rem;
    }
    main {
        max-width: 900px;
        margin: 40px auto;
        background: white;
        border-radius: 15px;
        padding: 30px 40px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    h2 {
        color: #004080;
        font-size: 2rem;
        margin-bottom: 5px;
    }
    .category {
        color: #ffcc00;
        font-weight: 600;
        margin-bottom: 25px;
        background: #004080;
        display: inline-block;
        padding: 6px 14px;
        border-radius: 15px;
        font-size: 1rem;
    }
    .description {
        margin-bottom: 35px;
        font-size: 1.1rem;
        color: #555;
    }
    .roadmap-step {
        background: #f0f8ff;
        border-left: 6px solid #004080;
        padding: 20px 25px;
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .step-number {
        font-weight: 700;
        color: #004080;
        font-size: 1.2rem;
        margin-bottom: 6px;
    }
    .step-title {
        font-weight: 600;
        font-size: 1.3rem;
        margin-bottom: 8px;
    }
    .step-description {
        font-size: 1rem;
        color: #333;
        line-height: 1.4;
    }
    .certificate {
        background: #fffbea;
        border-left: 6px solid #ffcc00;
        padding: 20px 25px;
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .cert-title {
        font-weight: 600;
        font-size: 1.2rem;
        color: #cc8800;
        margin-bottom: 6px;
    }
    .cert-provider {
        font-size: 0.95rem;
        font-style: italic;
        margin-bottom: 8px;
    }
    .cert-description {
        margin-bottom: 6px;
    }
    .cert-skills {
        font-size: 0.95rem;
        color: #444;
    }
    a.back-link {
        display: inline-block;
        margin-top: 30px;
        background-color: #004080;
        color: white;
        padding: 10px 18px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: background-color 0.3s ease;
    }
    a.back-link:hover {
        background-color: #0066cc;
    }
</style>
</head>
<body>

<header>
  <h1>Career Roadmap</h1>
</header>

<main>
  <h2><?= htmlspecialchars($career['title']) ?></h2>
  <div class="category"><?= htmlspecialchars($career['category']) ?></div>
  <div class="description"><?= nl2br(htmlspecialchars($career['description'])) ?></div>

  <h3>📍 Roadmap</h3>
  <?php if (empty($roadmap_steps)): ?>
    <p style="color:#999; font-style: italic;">No roadmap steps available yet for this career.</p>
  <?php else: ?>
    <?php foreach ($roadmap_steps as $step): ?>
      <div class="roadmap-step">
        <div class="step-number">Step <?= $step['step_number'] ?></div>
        <div class="step-title"><?= htmlspecialchars($step['step_title']) ?></div>
        <div class="step-description"><?= nl2br(htmlspecialchars($step['step_detail'])) ?></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <h3>🎓 Recommended Certificates</h3>
  <?php if (empty($certificates)): ?>
    <p style="color:#999; font-style: italic;">No certificates available yet for this career.</p>
  <?php else: ?>
    <?php foreach ($certificates as $cert): ?>
      <div class="certificate">
        <div class="cert-title"><?= htmlspecialchars($cert['certificate_title']) ?></div>
        <div class="cert-provider">Offered by <?= htmlspecialchars($cert['provider']) ?></div>
        <div class="cert-description"><?= nl2br(htmlspecialchars($cert['description'])) ?></div>
        <div class="cert-skills"><strong>Skills:</strong> <?= htmlspecialchars($cert['skills']) ?></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <a class="back-link" href="career-guidance.php">&larr; Back to Careers</a>
</main>

</body>
</html>
