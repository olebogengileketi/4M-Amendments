<?php
$host = "127.0.0.1"; // Use IP instead of localhost to avoid socket issues
$dbname = "ame_ypd_amendments";
$username = "root";
$password = ""; // XAMPP default password

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

try {
    // Try XAMPP MySQL first
    $pdo = new PDO("mysql:host=$host;port=3307;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Fallback to standard MySQL port
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } catch (PDOException $e2) {
        die("Connection failed: " . $e2->getMessage());
    }
}

function get_pdo(): PDO
{
    global $pdo;
    return $pdo;
}
