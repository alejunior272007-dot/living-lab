<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    $user = current_user();

    if ($user && (int) ($user['must_change_password'] ?? 0) === 1) {
        redirect('ubah-password.php');
    }

    redirect('index.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $inputUsername = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!is_string($inputUsername) || !is_string($password)) {
        $error = 'Format input tidak valid.';
    } else {
        $username = trim($inputUsername);

        if (
            $username === '' ||
            $password === '' ||
            strlen($username) > 50 ||
            strlen($password) > 255
        ) {
            $error = 'Username atau password tidak valid.';
        } else {
            $stmt = $pdo->prepare(
                "SELECT
                    id,
                    full_name,
                    username,
                    password,
                    role,
                    is_active,
                    must_change_password
                 FROM users
                 WHERE username = :username
                 LIMIT 1"
            );

            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if (
                $user &&
                (int) $user['is_active'] === 1 &&
                password_verify($password, $user['password'])
            ) {
                login_user($user);

                if ((int) $user['must_change_password'] === 1) {
                    flash(
                        'success',
                        'Silakan buat password baru sebelum melanjutkan.'
                    );

                    redirect('ubah-password.php');
                }

                flash('success', 'Login berhasil. Selamat datang!');
                redirect('index.php');
            }

            $error = 'Username atau password salah, atau akun tidak aktif.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#10291f">
    <title>Login - <?= e(APP_NAME) ?></title>
    <link
    rel="stylesheet"
    href="<?= e(app_url('assets/css/style.css')) ?>?v=20261110"
>
</head>
<body class="login-page">

<main class="login-layout">

    <section class="login-showcase">
        <a href="<?= e(app_url('login.php')) ?>" class="login-brand">
            <span class="login-brand-icon">♻</span>
            <span>
                <strong>Living Lab</strong>
                <small>SUPPORT SYSTEM</small>
            </span>
        </a>

        <div class="login-showcase-content">
            <span class="login-eyebrow">WASTE MANAGEMENT SYSTEM</span>

            <h1>Manage Waste.<br>Build a Better<br><span>Environment.</span></h1>

            <p>
                Platform pendukung monitoring dan audit pengelolaan
                limbah untuk menciptakan lingkungan yang lebih baik.
            </p>

            <div class="login-showcase-info">
                <span class="login-info-icon">✓</span>
                <span>
                    <strong>Integrated Monitoring</strong>
                    <small>Pencatatan dan dokumentasi audit dalam satu sistem.</small>
                </span>
            </div>
        </div>

        <div class="login-showcase-footer">
            <span>Living Lab Support</span>
            <span>©ale junior <?= date('Y') ?></span>
        </div>
    </section>

    <section class="login-form-side">
        <div class="login-card">

            <div class="login-mobile-brand">
                <span class="login-brand-icon">♻</span>
                <strong>Living Lab Support</strong>
            </div>

            <div class="login-heading">
                <span class="login-welcome">WELCOME BACK</span>
                <h2>Selamat Datang!</h2>
                <p>Masuk ke akun Anda untuk melanjutkan.</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="login-error" role="alert">
                    <span class="login-error-icon">!</span>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= e(app_url('login.php')) ?>" class="login-form">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <div class="login-field">
                    <label for="username">Username</label>
                    <div class="login-input-wrap">
                        <span class="login-input-icon">♙</span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Masukkan username"
                            maxlength="50"
                            required
                            autocomplete="username"
                            autofocus
                            value="<?= e($username) ?>"
                        >
                    </div>
                </div>

                <div class="login-field">
                    <label for="password">Password</label>
                    <div class="login-input-wrap">
                        <span class="login-input-icon">♧</span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan password"
                            maxlength="255"
                            required
                            autocomplete="current-password"
                        >
                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            aria-label="Tampilkan password"
                            aria-pressed="false"
                        >Lihat</button>
                    </div>
                </div>

                <div class="login-help">
                    <span>Pastikan username dan password Anda benar.</span>
                </div>

                <button type="submit" class="login-submit">
                    <span>Masuk ke Dashboard</span>
                    <span aria-hidden="true">→</span>
                </button>
            </form>

            <div class="login-security-note">
                <span>🔒</span>
                <span>Akses hanya untuk pengguna yang terdaftar.</span>
            </div>

            <div class="login-bottom">
                Lupa password?
                <a href="<?= e(app_url('lupa-password.php')) ?>">
                    Ajukan reset password
                </a>
                <br>
                Belum memiliki akun?
                <strong>Hubungi administrator</strong> untuk dibuatkan akun.
            </div>

        </div>
    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const toggleButton = document.getElementById('togglePassword');

    if (!passwordInput || !toggleButton) {
        return;
    }

    toggleButton.addEventListener('click', function () {
        const isPassword = passwordInput.type === 'password';

        passwordInput.type = isPassword ? 'text' : 'password';
        toggleButton.textContent = isPassword ? 'Sembunyikan' : 'Lihat';
        toggleButton.setAttribute('aria-pressed', String(isPassword));
        toggleButton.setAttribute(
            'aria-label',
            isPassword ? 'Sembunyikan password' : 'Tampilkan password'
        );
    });
});
</script>

</body>
</html>
