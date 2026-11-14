<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$host = "localhost";
$user = "root";
$pass = "adminale@272007";
$db   = "living_lab"; // Sesuaikan dengan nama database lokal di Laragon kamu
$dbCharset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$dbCharset}";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $exception) {
    http_response_code(500);
    exit('DETAIL ERROR: ' . htmlspecialchars($exception->getMessage()));
}
