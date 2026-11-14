<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$userId = (int) $user['id'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (
        !is_string($currentPassword) ||
        !is_string($newPassword) ||
        !is_string($confirmPassword)
    ) {
        $error = 'Format input tidak valid.';
    } elseif (
        $currentPassword === '' ||
        strlen($newPassword) < 8 ||
        strlen($newPassword) > 255
    ) {
        $error = 'Password baru minimal 8 karakter dan maksimal 255 karakter.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Konfirmasi password baru tidak cocok.';
    } elseif ($currentPassword === $newPassword) {
        $error = 'Password baru harus berbeda dari password saat ini.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT password
             FROM users
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->execute([$userId]);
        $account = $stmt->fetch();

        if (
            !$account ||
            !password_verify($currentPassword, $account['password'])
        ) {
            $error = 'Password saat ini salah.';
        } else {
            $newHash = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            $update = $pdo->prepare(
                "UPDATE users
                 SET password = ?,
                     must_change_password = 0
                 WHERE id = ?"
            );

            $update->execute([$newHash, $userId]);

            $_SESSION['user']['must_change_password'] = 0;

            flash('success', 'Password berhasil diperbarui.');

            redirect('index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ubah Password - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
</head>
<body class="login-page">

<main class="login-layout">
    <section class="login-form-side">
        <div class="login-card">
            <div class="login-heading">
                <span class="login-welcome">ACCOUNT SECURITY</span>
                <h2>Ubah Password</h2>
                <p>
                    Untuk keamanan akun, buat password baru sebelum
                    melanjutkan ke dashboard.
                </p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="login-error" role="alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <div class="login-field">
                    <label for="current_password">Password Saat Ini</label>
                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        maxlength="255"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="login-field">
                    <label for="new_password">Password Baru</label>
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        minlength="8"
                        maxlength="255"
                        autocomplete="new-password"
                        required
                    >
                    <small>Minimal 8 karakter.</small>
                </div>

                <div class="login-field">
                    <label for="confirm_password">Konfirmasi Password Baru</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        minlength="8"
                        maxlength="255"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button type="submit" class="login-submit">
                    Simpan Password Baru
                </button>
            </form>

            <div class="login-security-note">
                <span>🔒</span>
                <span>Password baru Anda disimpan dalam bentuk terenkripsi (hash).</span>
            </div>
        </div>
    </section>
</main>

</body>
</html>
