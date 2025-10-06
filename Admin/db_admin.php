<?php
// DB configuration (set these variables)
$host = 'localhost';
$dbname = 'em_mentor';
$user = 'root';
$pass = '';
$charset = 'utf8';

// PDO DSNs and options
$dsnNoDb = "mysql:host=$host;charset=$charset";
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Connect to MySQL server (no DB selected) to create DB if needed
    $pdo = new PDO($dsnNoDb, $user, $pass, $options);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET $charset COLLATE {$charset}_general_ci");

    // Reconnect selecting the created database
    $pdo = new PDO($dsn, $user, $pass, $options);

    // $pdo is now ready for further queries
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>