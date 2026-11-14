<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$dbHost = 'localhost';
$dbName = 'u634365967_livinglab';
$dbUser = 'u634365967_llusr';
$dbPass = 'LivingLab2026';
$dbCharset = 'utf8mb4';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $exception) {
    http_response_code(500);
    exit('DETAIL ERROR: ' . htmlspecialchars($exception->getMessage()));
}
