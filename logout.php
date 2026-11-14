<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

verify_csrf();

logout_user();
redirect('login.php');
