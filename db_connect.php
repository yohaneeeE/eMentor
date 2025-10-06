<?php
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "em_mentor";

// use the default MySQL port (3306) for XAMPP
$port = 3306;

// make mysqli throw exceptions so we can catch them
mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ALL);

try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $dbname, $port);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // Log the real error for the server admin and show a generic message to the user
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
?>
