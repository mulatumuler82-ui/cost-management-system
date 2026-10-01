<?php
$host = 'localhost';
$db   = 'cost_management';
$user = 'root'; // Change according to your local DB user
$pass = '';     // Change according to your local DB password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    // Set error mode to exception for easier debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>