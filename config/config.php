<?php
declare(strict_types=1);

define('APP_NAME', 'Living Lab Support');
   define('BASE_URL', '');
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/dokumentasi');
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);

date_default_timezone_set('Asia/Jakarta');

function app_url(string $path = ''): string {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
