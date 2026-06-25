<?php
$hostname = "127.0.0.1";
$database = "createProjectDB"; // Must match the SQL file name exactly!
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>