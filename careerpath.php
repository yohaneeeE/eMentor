<?php
session_start();

include 'db_connect.php'; 

// Check login state
$isLoggedIn = isset($_SESSION['fullName']);
$fullName   = $isLoggedIn ? $_SESSION['fullName'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Career Path Input</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f9f9f9; color: #333; line-height: 1.6; }
    header { background: linear-gradient(135deg, #004080, #0066cc); color: white; padding: 25px 0; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); position: relative; }
    header h1 { font-size: 2.5rem; margin-bottom: 10px; }
    header p { font-size: 1.1rem; opacity: 0.9; }
    .logout-btn { position: absolute; top: 20px; right: 30px; background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.3); padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600; transition: all 0.3s ease; text-decoration: none; }
    .logout-btn:hover { background: rgba(255,255,255,0.3); border-color: rgba(255,255,255,0.5); transform: translateY(-1px); }
    nav { background-color: #003060; padding: 15px 0; position: sticky; top: 0; z-index: 100; }
    nav ul { list-style: none; display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; }
    nav ul li a { color: white; text-decoration: none; font-weight: 600; transition: all 0.3s ease; padding: 8px 15px; border-radius: 5px; }
    nav ul li a:hover { color: #ffcc00; background-color: rgba(255,255,255,0.1); }
    .container { max-width: 1200px; margin: 40px auto; padding: 30px; background: white; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    h2 { color: #004080; margin-bottom: 25px; text-align: center; font-size: 2rem; position: relative; padding-bottom: 15px; }
    h2::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 100px; height: 3px; background: linear-gradient(90deg, #004080, #ffcc00); border-radius: 3px; }
    .intro-text { font-size: 1.1em; margin-bottom: 40px; text-align: center; color: #555; max-width: 800px; margin-left: auto; margin-right: auto; }
    .certificate-card { display: flex; align-items: center; gap: 10px; background: #f1f5f9; padding: 10px 15px; margin: 8px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: transform 0.2s ease; }
    .certificate-card:hover { transform: translateY(-2px); }
    .remove-btn { background: #ff4d4d; color: white; border: none; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: background 0.3s; }
    .remove-btn:hover { background: #cc0000; }
    .preview-container { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 10px; }
    .preview-item { border: 1px solid #ddd; padding: 8px; border-radius: 8px; background: #fff; text-align: center; font-size: 0.9rem; width: 120px; }
    .preview-item img { max-width: 100%; max-height: 100px; border-radius: 4px; }
    .preview-pdf { font-size: 2rem; color: #d32f2f; }
    footer { text-align: center; padding: 30px 0; background: linear-gradient(135deg, #003060, #004080); color: white; font-size: 0.95em; margin-top: 60px; }
    .footer-links { display: flex; justify-content: center; gap: 20px; margin-bottom: 15px; }
    .footer-links a { color: #ffcc00; text-decoration: none; transition: color 0.3s ease; }
    .footer-links a:hover { color: white; }
    button { padding:10px 20px; background-color:#004080; color:white; border:none; border-radius:6px; font-weight:bold; cursor:pointer; }
    button:hover { background-color:#0066cc; }
    #resultBox { margin-top:20px; padding:15px; border-radius:8px; background:#f1f5f9; font-size:0.95rem; max-height:250px; overflow-y:auto; }
    .progress { color:#0066cc; margin-bottom:8px; }
    .error { color:red; }
  </style>
</head>
<body>

<header>
  <h1>eMentor</h1>
  <p>Upload your Academic Grades & Certificates to get personalized career recommendations</p>
  <?php if ($isLoggedIn && $fullName !== 'Guest'): ?>
    <a class="logout-btn" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">🚪 Logout (<?= htmlspecialchars($fullName) ?>)</a>
  <?php else: ?>
    <a class="logout-btn" href="login.php">🔐 Login</a>
  <?php endif; ?>
</header>

<nav>
  <ul>
    <li><a href="dashboard.php">Home</a></li>
    <li><a href="career-guidance.php">Career Guidance</a></li>
    <li><a href="careerpath.php" style="background-color:#ffcc00;color:#004080;font-weight:700;">Career Path</a></li>
    <li><a href="about.php">About</a></li>
  </ul>
</nav>

<div class="container">
  <h2>Career Path Assessment</h2>
  <p class="intro-text">Please upload your Academic Grades and any certificates to receive personalized career suggestions.</p>

  <form id="careerForm" enctype="multipart/form-data">
    <!-- TOR Upload -->
    <label for="torInput">Academic Grades:</label><br/>
    <input type="file" id="torInput" name="torFile" accept="image/*,application/pdf"><br/>
    <div id="torPreview" class="preview-container"></div><br/>

    <!-- Certificates Upload -->
    <div id="certificatesSection">
      <label>Certificates:</label><br/>
      <div id="certContainer"></div>
      <button type="button" id="addCertBtn">➕ Add Certificate</button><br/><br/>
      <div id="certPreview" class="preview-container"></div>
    </div>

    <button type="button" id="submitTorBtn" <?php if (!$isLoggedIn): ?> title="You need to be logged in to submit"<?php endif; ?>>Submit</button>
  </form>

  <div id="resultBox"></div>
</div>

<footer>
  <div class="footer-links">
    <a href="privacy.html">Privacy Policy</a>
    <a href="terms.html">Terms of Service</a>
    <a href="contact.html">Contact Us</a>
  </div>
  <p>&copy; 2025 eMentor. All rights reserved.</p>
  <p>Bulacan State University - Bustos Campus</p>
</footer>

<script>
  const isLoggedIn   = <?php echo json_encode($isLoggedIn); ?>;
  const torInput     = document.getElementById("torInput");
  const torPreview   = document.getElementById("torPreview");
  const certContainer= document.getElementById("certContainer");
  const certPreview  = document.getElementById("certPreview");
  const addCertBtn   = document.getElementById("addCertBtn");
  const resultBox    = document.getElementById("resultBox");
  const submitButton = document.getElementById("submitTorBtn");

  function previewFile(file, container) {
    const item = document.createElement("div");
    item.className = "preview-item";
    if (file.type.startsWith("image/")) {
      const img = document.createElement("img");
      img.src = URL.createObjectURL(file);
      item.appendChild(img);
    } else {
      const icon = document.createElement("div");
      icon.className = "preview-pdf";
      icon.textContent = "📑";
      item.appendChild(icon);
    }
    const label = document.createElement("p");
    label.textContent = file.name;
    item.appendChild(label);
    container.appendChild(item);
  }

  torInput.addEventListener("change", () => {
    torPreview.innerHTML = "";
    if (torInput.files[0]) previewFile(torInput.files[0], torPreview);
  });

  addCertBtn.addEventListener("click", () => {
    const certDiv = document.createElement("div");
    certDiv.className = "certificate-card";
    certDiv.innerHTML = `
      <input type="file" name="certificateFiles[]" accept="image/*,application/pdf">
      <button type="button" class="remove-btn">✖ Remove</button>
    `;
    const fileInput = certDiv.querySelector("input");

    fileInput.addEventListener("change", () => {
      if (fileInput.files[0]) previewFile(fileInput.files[0], certPreview);
    });

    certDiv.querySelector(".remove-btn").addEventListener("click", () => certDiv.remove());
    certContainer.appendChild(certDiv);
  });

  submitButton.addEventListener("click", async () => {
    if (!isLoggedIn) {
      alert("❗ You must be logged in to submit.");
      window.location.href = "login.php";
      return;
    }

    const file = torInput.files[0];
    if (!file) {
      alert("❗ Please upload a TOR (image or PDF).");
      return;
    }

    resultBox.innerHTML = "<p class='progress'>⏳ Uploading and processing your transcript & certificates...</p>";

    try {
      const formData = new FormData();
      formData.append("file", file);

      document.querySelectorAll('input[name="certificateFiles[]"]').forEach((input) => {
        if (input.files[0]) formData.append("certificateFiles[]", input.files[0]);
      });

      const response = await fetch("http://127.0.0.1:8000/ocrPredict", {
        method: "POST",
        body: formData
      });

      if (!response.ok) throw new Error("❌ API request failed.");

      const msg = await response.json();
      if (msg.error) {
        resultBox.innerHTML = `<p class="error">❌ Error: ${msg.error}</p>`;
        return;
      }

      // ✅ Pass API JSON directly via POST (sessionStorage for redirect)
      sessionStorage.setItem("apiResult", JSON.stringify(msg));

      resultBox.innerHTML = `<p class="progress">✅ Done! Redirecting...</p>`;
      setTimeout(() => window.location.href = "career-suggestions.php", 1000);

    } catch (err) {
      console.error("🚨 Fetch error:", err);
      resultBox.innerHTML = `<p class="error">❌ Network or Server Error: ${err.message}</p>`;
    }
  });
</script>

</body>
</html>
