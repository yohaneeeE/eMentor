<?php
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "em_mentor";

/* Connect to MySQL server (no DB selected) */
$conn = new mysqli($servername, $dbusername, $dbpassword);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* Create database if it doesn't exist */
$sql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === FALSE) {
    die("Error creating database: " . $conn->error);
}

/* Select the newly created database and set charset */
if (!$conn->select_db($dbname) || !$conn->set_charset("utf8mb4")) {
    die("Error selecting database or setting charset: " . $conn->error);
}

/* Ready to use $conn for further queries */
?>