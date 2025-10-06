<?php
session_start();

include 'db_connect.php';

// Simple server-side proxy: when this file receives a POST with files, forward them to the external Python API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_FILES['file']) || isset($_FILES['certificateFiles']))) {
    header('Content-Type: application/json');

    // Ensure user is logged in before forwarding
    if (!isset($_SESSION['fullName'])) {
        echo json_encode(['error' => 'Authentication required.']);
        exit;
    }

    // Target Python API (keep same host as before)
    $apiUrl = 'https://python-api-k98f.onrender.com';

    $postFields = [];

    // Main TOR file
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $postFields['file'] = new CURLFile($_FILES['file']['tmp_name'], $_FILES['file']['type'], $_FILES['file']['name']);
    }

    // Certificates (may be multiple)
    if (isset($_FILES['certificateFiles'])) {
        // PHP organizes multiple files in arrays; normalize handling
        $certFiles = $_FILES['certificateFiles'];
        // If multiple uploads, iterate
        if (is_array($certFiles['name'])) {
            for ($i = 0; $i < count($certFiles['name']); $i++) {
                if ($certFiles['error'][$i] === UPLOAD_ERR_OK) {
                    // Use unique key names expected by remote API; append [] style
                    $postFields['certificateFiles[' . $i . ']'] = new CURLFile($certFiles['tmp_name'][$i], $certFiles['type'][$i], $certFiles['name'][$i]);
                }
            }
        } else {
            // Single file field
            if ($certFiles['error'] === UPLOAD_ERR_OK) {
                $postFields['certificateFiles[]'] = new CURLFile($certFiles['tmp_name'], $certFiles['type'], $certFiles['name']);
            }
        }
    }

    // Initialize cURL to forward multipart/form-data
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    // Optionally, set a reasonable timeout
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        echo json_encode(['error' => 'Forwarding failed: ' . $curlErr]);
        exit;
    }

    // If the remote returned a non-2xx, forward that as an error
    if ($httpCode < 200 || $httpCode >= 300) {
        // Try to include remote body if available
        $body = $response ?: 'No response body';
        echo json_encode(['error' => "Remote API returned HTTP $httpCode", 'details' => $body]);
        exit;
    }

    // Assume remote returns JSON; forward it directly
    // If remote returns something else, wrap it
    $decoded = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo json_encode($decoded);
    } else {
        echo json_encode(['result' => $response]);
    }
    exit;
}

// Check login state for page rendering
$isLoggedIn = isset($_SESSION['fullName']);
$fullName   = $isLoggedIn ? $_SESSION['fullName'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Career Path Input - eMentor</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #fff;
        color: #333;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
        .sidebar .user-info {
        margin-top:auto; /* push to bottom */
        padding-top:15px;
        border-top:1px solid rgba(255,255,255,0.2);
        font-size:0.95rem;
        color:#ffcc00;
        text-align:center;
    }

    header {
        background: linear-gradient(135deg, #3a3a3a, #1e1e1e);
        color: white;
        padding: 20px;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        position: relative;
    }
    header h1 { font-size: 2rem; margin-bottom: 5px; }
    header p { font-size: 1rem; opacity: 0.9; }

    /* Hamburger */
    .hamburger {
        position: absolute;
        top: 20px;
        left: 20px;
        width: 30px;
        height: 22px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        cursor: pointer;
        z-index: 300;
    }
    .hamburger span {
        height: 4px;
        background: white;
        border-radius: 2px;
        transition: all 0.3s ease;
    }
    .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
    .hamburger.active span:nth-child(2) { opacity: 0; }
    .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(6px, -6px); }

    /* Sidebar Menu */
    .sidebar {
        position: fixed; top: 0; left: -250px;
        width: 250px; height: 100%;
        background: #333;
        color: white; padding: 60px 20px;
        display: flex; flex-direction: column;
        gap: 20px;
        z-index: 200;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        transition: left 0.3s ease;
    }
    .sidebar.active { left: 0; }
    .sidebar a {
        color: white;
        text-decoration: none;
        font-size: 1.1rem;
        padding: 8px 0;
        display: block;
        transition: color 0.3s ease, transform 0.2s ease;
    }
    .sidebar a:hover { color: #ffcc00; transform: translateX(5px); }
    hr.sidebar-separator { border: 1px solid rgba(255,255,255,0.2); margin: 10px 0; }

    /* Overlay */
    .overlay {
        position: fixed; top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.4);
        opacity: 0; visibility: hidden;
        transition: opacity 0.3s ease;
        z-index: 100;
    }
    .overlay.active { opacity: 1; visibility: visible; }

    .container {
        flex: 1;
        max-width: 900px;
        margin: 40px auto;
        padding: 30px;
        background: #f7f7f7;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    h2 {
        color: #333;
        margin-bottom: 20px;
        text-align: center;
        font-size: 1.8rem;
        position: relative;
        padding-bottom: 10px;
    }
    h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: #ffcc00;
        border-radius: 3px;
    }

    .intro-text { text-align: center; margin-bottom: 30px; color: #666; }

    .certificate-card {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #eaeaea;
        padding: 10px;
        margin: 8px 0;
        border-radius: 8px;
    }
    .remove-btn {
        background: #ff4d4d;
        border: none;
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.85rem;
    }
    .remove-btn:hover { background: #cc0000; }

    .preview-container {
        margin-top: 10px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .preview-item {
        border: 1px solid #ddd;
        padding: 8px;
        border-radius: 8px;
        background: #fff;
        text-align: center;
        font-size: 0.9rem;
        width: 120px;
        color: #333;
    }
    .preview-item img { max-width: 100%; max-height: 100px; border-radius: 4px; }
    .preview-pdf { font-size: 2rem; color: #d32f2f; }

    button {
        padding: 10px 20px;
        background-color: #ffcc00;
        color: #333;
        border: none;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        transition: transform 0.2s, background 0.3s;
    }
    button:hover { transform: scale(1.05); background-color: #ffdb4d; }

    #resultBox {
        margin-top:20px;
        padding:15px;
        border-radius:8px;
        background:#f1f1f1;
        font-size:0.95rem;
        max-height:250px;
        overflow-y:auto;
        color:#444;
    }
    .progress { color:#0066cc; }
    .error { color:#cc0000; }

    footer {
        text-align: center;
        padding: 20px;
        background: #1e1e1e;
        color: #aaa;
        font-size: 0.9rem;
        margin-top: auto;
    }
    .footer-links {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-bottom: 10px;
    }
    .footer-links a { color: #ffcc00; text-decoration: none; }
    .footer-links a:hover { color: white; }
</style>
</head>
<body>

<!-- Hamburger -->
<div class="hamburger" id="hamburger">
  <span></span><span></span><span></span>
</div>

<header>
  <h1>eMentor</h1>
  <p>Upload your Academic Grades & Certificates to get personalized career recommendations</p>
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

<!-- Overlay -->
<div class="overlay" id="overlay"></div>

<div class="container">
  <h2>Career Path Assessment</h2>
  <p class="intro-text">Please upload your Academic Grades and any certificates to receive personalized career suggestions.</p>

  <form id="careerForm" enctype="multipart/form-data">
    <label for="torInput">Academic Grades:</label><br/>
    <input type="file" id="torInput" name="torFile" accept="image/*,application/pdf"><br/>
    <div id="torPreview" class="preview-container"></div><br/>

    <div id="certificatesSection">
      <label>Certificates:</label><br/>
      <div id="certContainer"></div>
      <button type="button" id="addCertBtn">Add Certificate</button><br/><br/>
      <div id="certPreview" class="preview-container"></div>
    </div>

    <button type="button" id="submitTorBtn">Submit</button>
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
</footer>

<script>
        // Post to the local PHP proxy in the same file which forwards to the external Python API
        const response = await fetch("careerpath.php", { method: "POST", body: formData });
        if (!response.ok) {
            // attempt to include any text body returned for debugging
            const text = await response.text().catch(() => '');
            throw new Error("API request failed: " + response.status + " " + text);
        }
        const msg = await response.json();

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
        icon.textContent = "PDF";
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
        <button type="button" class="remove-btn">Remove</button>
    `;
    const fileInput = certDiv.querySelector("input");
    fileInput.addEventListener("change", () => {
        if (fileInput.files[0]) previewFile(fileInput.files[0], certPreview);
    });
    certDiv.querySelector(".remove-btn").addEventListener("click", () => certDiv.remove());
    certContainer.appendChild(certDiv);
});

submitButton.addEventListener("click", async () => {
    if (!isLoggedIn) { alert("You must be logged in to submit."); window.location.href = "login.php"; return; }
    const file = torInput.files[0];
    if (!file) { alert("Please upload a TOR (image or PDF)."); return; }

    resultBox.innerHTML = "<p class='progress'>Uploading and processing...</p>";

    try {
        const formData = new FormData();
        formData.append("file", file);
        document.querySelectorAll('input[name="certificateFiles[]"]').forEach(input => {
            if (input.files[0]) formData.append("certificateFiles[]", input.files[0]);
        });

        const response = await fetch("https://python-api-k98f.onrender.com", { method: "POST", body: formData });
        if (!response.ok) throw new Error("API request failed.");
        const msg = await response.json();

        if (msg.error) { resultBox.innerHTML = `<p class="error">Error: ${msg.error}</p>`; return; }
        sessionStorage.setItem("apiResult", JSON.stringify(msg));
        resultBox.innerHTML = `<p class="progress">Done! Redirecting...</p>`;
        setTimeout(() => window.location.href = "career-suggestions.php", 1000);

    } catch (err) {
        resultBox.innerHTML = `<p class="error">Network or Server Error: ${err.message}</p>`;
    }
});
</script>

</body>
</html>
