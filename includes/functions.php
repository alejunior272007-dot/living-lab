<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

// ========================================
// SESSION SECURITY
// ========================================

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $isHttps = (
        isset($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== '' &&
        strtolower((string) $_SERVER['HTTPS']) !== 'off'
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// ========================================
// KATEGORI SUMBER
// ========================================

function source_categories(): array {
    return [
        'Rumah Tangga',
        'Rumah Makan',
        'Warung',
        'Pasar',
        'Dapur MBG'
    ];
}

// ========================================
// CSRF PROTECTION
// ========================================

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !is_string($sessionToken) ||
        $sessionToken === '' ||
        !hash_equals($sessionToken, $token)
    ) {
        http_response_code(419);
        exit('Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.');
    }
}

// ========================================
// FLASH MESSAGE
// ========================================

function flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash(): ?array {
    $value = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $value;
}

// ========================================
// REDIRECT
// ========================================

function redirect(string $path): never {
    header('Location: ' . app_url($path));
    exit;
}

// ========================================
// VALIDASI TANGGAL
// ========================================

function valid_date(string $date): bool {
    $d = DateTime::createFromFormat('!Y-m-d', $date);

    return $d !== false && $d->format('Y-m-d') === $date;
}

// ========================================
// NAMA FILE FOTO
// ========================================

function make_photo_name(
    string $project,
    string $session,
    string $date,
    int $seq,
    string $extension
): string {
    $project = strtoupper(
        preg_replace('/[^A-Z0-9]/i', '', $project) ?? ''
    );

    $session = strtoupper(
        preg_replace('/[^A-Z0-9]/i', '', $session) ?? ''
    );

    return sprintf(
        '%s_FOTO_%s_%s_%03d.%s',
        $project,
        $session,
        str_replace('-', '', $date),
        $seq,
        strtolower($extension)
    );
}

// ========================================
// AUTENTIKASI
// ========================================

function is_logged_in(): bool {
    return isset($_SESSION['user']['id']);
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

// ========================================
// LOGOUT
// ========================================

function logout_user(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    session_destroy();
}

// ========================================
// CEK LOGIN DAN STATUS AKUN
// ========================================

function require_login(): void {
    if (!is_logged_in()) {
        flash('error', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }

    $userId = (int) $_SESSION['user']['id'];

    $stmt = $GLOBALS['pdo']->prepare(
        "SELECT
            id,
            full_name,
            username,
            role,
            is_active,
            must_change_password
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || (int) $user['is_active'] !== 1) {
        logout_user();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        flash(
            'error',
            'Akun Anda sudah tidak aktif atau tidak tersedia. Silakan hubungi Admin.'
        );

        redirect('login.php');
    }

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'full_name' => $user['full_name'],
        'username' => $user['username'],
        'role' => $user['role'],
        'must_change_password' => (int) $user['must_change_password'],
    ];

    // Wajib mengganti password sementara sebelum membuka halaman lain.
    $currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');

    if (
        (int) $user['must_change_password'] === 1 &&
        $currentPage !== 'ubah-password.php'
    ) {
        redirect('ubah-password.php');
    }
}

// ========================================
// PEMBATASAN ROLE
// ========================================

function require_role(string ...$roles): void {
    require_login();

    $user = current_user();

    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('Akses ditolak. Anda tidak memiliki izin.');
    }
}

// ========================================
// LOGIN PENGGUNA
// ========================================

function login_user(array $user): void {
    session_regenerate_id(true);

    // Token lama tidak digunakan kembali setelah login.
    unset($_SESSION['csrf_token']);

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'full_name' => $user['full_name'],
        'username' => $user['username'],
        'role' => $user['role'],
        'must_change_password' => (int) ($user['must_change_password'] ?? 0),
    ];
}
