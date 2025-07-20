<?php
// Database credentials
$host = 'localhost';
$db   = 'pinkbugtracker';
$user = 'root';      // Change if your MySQL username is different
$pass = '';          // Change if your MySQL password is set

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    // Set error mode to exceptions
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
