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

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = $_POST['username'] ?? '';
    $reason = $_POST['reason'] ?? '';

    if (!is_string($username) || !is_string($reason)) {
        $error = 'Format input tidak valid.';
    } else {
        $username = trim($username);
        $reason = trim($reason);

        if ($username === '' || $reason === '') {
            $error = 'Username dan alasan permintaan wajib diisi.';
        } elseif (
            strlen($username) > 50 ||
            strlen($reason) < 10 ||
            strlen($reason) > 1000
        ) {
            $error = 'Alasan permintaan harus 10 sampai 1000 karakter.';
        } else {
            $stmt = $pdo->prepare(
                "SELECT id
                 FROM users
                 WHERE username = ?
                   AND role = 'petugas'
                   AND is_active = 1
                 LIMIT 1"
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user) {
                $check = $pdo->prepare(
                    "SELECT id
                     FROM password_reset_requests
                     WHERE user_id = ?
                       AND status = 'pending'
                     LIMIT 1"
                );
                $check->execute([$user['id']]);

                if (!$check->fetch()) {
                    $insert = $pdo->prepare(
                        "INSERT INTO password_reset_requests (user_id, reason)
                         VALUES (?, ?)"
                    );
                    $insert->execute([$user['id'], $reason]);
                }
            }

            $message = 'Jika akun Petugas ditemukan dan memenuhi syarat, permintaan akan diteruskan kepada Admin. Silakan hubungi Admin untuk informasi selanjutnya.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
</head>
<body class="login-page">

<main class="login-layout">
    <section class="login-form-side">
        <div class="login-card">
            <div class="login-heading">
                <span class="login-welcome">ACCOUNT RECOVERY</span>
                <h2>Lupa Password?</h2>
                <p>Ajukan permintaan reset password kepada Admin.</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="login-error" role="alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($message !== ''): ?>
                <div class="alert alert-success" role="status">
                    <?= e($message) ?>
                </div>
            <?php else: ?>
                <form method="POST" class="login-form">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrf_token()) ?>"
                    >

                    <div class="login-field">
                        <label for="username">Username</label>
                        <div class="login-input-wrap">
                            <input
                                type="text"
                                id="username"
                                name="username"
                                maxlength="50"
                                required
                                autocomplete="username"
                            >
                        </div>
                    </div>

                    <div class="login-field">
                        <label for="reason">Alasan permintaan</label>
                        <textarea
                            id="reason"
                            name="reason"
                            rows="5"
                            minlength="10"
                            maxlength="1000"
                            required
                            placeholder="Jelaskan alasan Anda meminta reset password..."
                        ></textarea>
                    </div>

                    <button type="submit" class="login-submit">
                        Kirim Permintaan
                    </button>
                </form>
            <?php endif; ?>

            <div class="login-bottom">
                <a href="<?= e(app_url('login.php')) ?>">
                    Kembali ke halaman login
                </a>
            </div>
        </div>
    </section>
</main>

</body>
</html>
