<?php
$host = "localhost";
$dbname = "extrusion";
$user = "root";
$pass = "";  // XAMPP のデフォルトは空

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    exit("DB Connection failed: " . $e->getMessage());
}
