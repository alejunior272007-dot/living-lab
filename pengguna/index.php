<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$pageTitle = 'Manajemen Pengguna';
$active = 'users';

$currentUser = current_user();
$currentId = (int) $currentUser['id'];

function user_post_string(string $key): string {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';
    $action = is_string($action) ? $action : '';

    $targetId = filter_var(
        $_POST['user_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    // AKTIFKAN ATAU NONAKTIFKAN PENGGUNA
    if ($action === 'toggle') {
        if (!$targetId || $targetId === $currentId) {
            flash('error', 'Aksi tidak valid atau tidak boleh dilakukan pada akun sendiri.');
            redirect('pengguna/index.php');
        }

        $stmt = $pdo->prepare(
            "SELECT id, role, is_active
             FROM users
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->execute([$targetId]);
        $target = $stmt->fetch();

        if (!$target) {
            flash('error', 'Pengguna tidak ditemukan.');
            redirect('pengguna/index.php');
        }

        if (
            $target['role'] === 'admin' &&
            (int) $target['is_active'] === 1
        ) {
            $count = $pdo->query(
                "SELECT COUNT(*)
                 FROM users
                 WHERE role = 'admin' AND is_active = 1"
            )->fetchColumn();

            if ((int) $count <= 1) {
                flash('error', 'Admin aktif terakhir tidak boleh dinonaktifkan.');
                redirect('pengguna/index.php');
            }
        }

        $newStatus = (int) $target['is_active'] === 1 ? 0 : 1;

        $stmt = $pdo->prepare(
            "UPDATE users SET is_active = ? WHERE id = ?"
        );
        $stmt->execute([$newStatus, $targetId]);

        flash('success', 'Status pengguna berhasil diperbarui.');
        redirect('pengguna/index.php');
    }

    // HAPUS PENGGUNA
    if ($action === 'delete') {
        if (!$targetId || $targetId === $currentId) {
            flash('error', 'Aksi tidak valid atau tidak boleh dilakukan pada akun sendiri.');
            redirect('pengguna/index.php');
        }

        $stmt = $pdo->prepare(
            "SELECT id, role, is_active
             FROM users
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->execute([$targetId]);
        $target = $stmt->fetch();

        if (!$target) {
            flash('error', 'Pengguna tidak ditemukan.');
            redirect('pengguna/index.php');
        }

        if (
            $target['role'] === 'admin' &&
            (int) $target['is_active'] === 1
        ) {
            $count = $pdo->query(
                "SELECT COUNT(*)
                 FROM users
                 WHERE role = 'admin' AND is_active = 1"
            )->fetchColumn();

            if ((int) $count <= 1) {
                flash('error', 'Admin aktif terakhir tidak boleh dihapus.');
                redirect('pengguna/index.php');
            }
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$targetId]);

        flash('success', 'Pengguna berhasil dihapus.');
        redirect('pengguna/index.php');
    }

    // EDIT DATA PENGGUNA
    if ($action === 'edit') {
        if (!$targetId || $targetId === $currentId) {
            flash('error', 'Anda tidak dapat mengubah akun sendiri melalui fitur ini.');
            redirect('pengguna/index.php');
        }

        $fullName = user_post_string('full_name');
        $username = user_post_string('username');
        $role = user_post_string('role');

        if (
            $fullName === '' ||
            $username === '' ||
            strlen($fullName) > 100 ||
            strlen($username) > 50 ||
            !in_array($role, ['admin', 'petugas'], true)
        ) {
            flash('error', 'Periksa kembali nama, username, dan role.');
            redirect('pengguna/index.php');
        }

        $stmt = $pdo->prepare(
            "SELECT id, role, is_active
             FROM users
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->execute([$targetId]);
        $target = $stmt->fetch();

        if (!$target) {
            flash('error', 'Pengguna tidak ditemukan.');
            redirect('pengguna/index.php');
        }

        if (
            $target['role'] === 'admin' &&
            $role !== 'admin' &&
            (int) $target['is_active'] === 1
        ) {
            $count = $pdo->query(
                "SELECT COUNT(*)
                 FROM users
                 WHERE role = 'admin' AND is_active = 1"
            )->fetchColumn();

            if ((int) $count <= 1) {
                flash('error', 'Admin aktif terakhir tidak boleh diubah menjadi Petugas.');
                redirect('pengguna/index.php');
            }
        }

        try {
            $stmt = $pdo->prepare(
                "UPDATE users
                 SET full_name = ?,
                     username = ?,
                     role = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $fullName,
                $username,
                $role,
                $targetId
            ]);

            flash('success', 'Data pengguna berhasil diperbarui.');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                flash('error', 'Username sudah digunakan pengguna lain.');
            } else {
                error_log('User edit error: ' . $e->getMessage());
                flash('error', 'Gagal memperbarui data pengguna.');
            }
        }

        redirect('pengguna/index.php');
    }

    // RESET PASSWORD MANUAL OLEH ADMIN
    if ($action === 'reset_password') {
        if (!$targetId || $targetId === $currentId) {
            flash('error', 'Anda tidak dapat mereset password sendiri melalui fitur ini.');
            redirect('pengguna/index.php');
        }

        $password = $_POST['new_password'] ?? '';

        if (
            !is_string($password) ||
            strlen($password) < 8 ||
            strlen($password) > 255
        ) {
            flash('error', 'Password sementara harus 8 sampai 255 karakter.');
            redirect('pengguna/index.php');
        }

        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$targetId]);

        if (!$stmt->fetch()) {
            flash('error', 'Pengguna tidak ditemukan.');
            redirect('pengguna/index.php');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "UPDATE users
                 SET password = ?,
                     must_change_password = 1
                 WHERE id = ?"
            );
            $stmt->execute([$passwordHash, $targetId]);

            // Permintaan reset yang masih pending tidak lagi diperlukan.
            $stmt = $pdo->prepare(
                "UPDATE password_reset_requests
                 SET status = 'rejected',
                     admin_note = 'Ditangani melalui reset password manual oleh Admin.',
                     processed_at = NOW(),
                     processed_by = ?
                 WHERE user_id = ?
                   AND status = 'pending'"
            );
            $stmt->execute([$currentId, $targetId]);

            $pdo->commit();

            flash(
                'success',
                'Password sementara berhasil dibuat. Pengguna wajib menggantinya saat login.'
            );
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Manual password reset error: ' . $e->getMessage());
            flash('error', 'Gagal mereset password pengguna.');
        }

        redirect('pengguna/index.php');
    }

    // TAMBAH AKUN
    if ($action === 'create') {
        $fullName = user_post_string('full_name');
        $username = user_post_string('username');
        $password = $_POST['password'] ?? '';
        $role = user_post_string('role');

        if (
            $fullName === '' ||
            $username === '' ||
            !in_array($role, ['admin', 'petugas'], true) ||
            !is_string($password) ||
            strlen($password) < 8 ||
            strlen($password) > 255
        ) {
            flash('error', 'Periksa kembali data. Password awal harus 8 sampai 255 karakter.');
            redirect('pengguna/index.php');
        }

        if (
            strlen($username) > 50 ||
            strlen($fullName) > 100
        ) {
            flash('error', 'Nama maksimal 100 karakter dan username maksimal 50 karakter.');
            redirect('pengguna/index.php');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users (
                    full_name,
                    username,
                    password,
                    role,
                    must_change_password
                 ) VALUES (?, ?, ?, ?, 1)"
            );

            $stmt->execute([
                $fullName,
                $username,
                $passwordHash,
                $role
            ]);

            flash(
                'success',
                'Akun berhasil dibuat. Pengguna wajib mengganti password saat login pertama.'
            );
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                flash('error', 'Username sudah digunakan.');
            } else {
                error_log('User create error: ' . $e->getMessage());
                flash('error', 'Gagal membuat akun.');
            }
        }

        redirect('pengguna/index.php');
    }

    flash('error', 'Aksi tidak dikenal.');
    redirect('pengguna/index.php');
}

// AMBIL DAFTAR PENGGUNA
$stmt = $pdo->query(
    "SELECT
        id,
        full_name,
        username,
        role,
        is_active,
        must_change_password,
        created_at
     FROM users
     ORDER BY created_at DESC"
);
$users = $stmt->fetchAll();

// JUMLAH PERMINTAAN RESET YANG MENUNGGU
$pendingRequests = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM password_reset_requests
     WHERE status = 'pending'"
)->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="content-section">
    <h2>Manajemen Pengguna</h2>
    <p>Kelola akun Admin dan Petugas Living Lab Support.</p>

    <p>
        <a href="<?= e(app_url('pengguna/permintaan-reset.php')) ?>">
            Permintaan Reset Password
            (<?= $pendingRequests ?> menunggu)
        </a>
    </p>
</section>

<hr>

<section class="content-section">
    <h2>Tambah Pengguna</h2>
    <p>Buat akun Admin tambahan atau akun Petugas.</p>

    <form method="POST">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >
        <input type="hidden" name="action" value="create">

        <p>
            <label for="full_name">Nama Lengkap</label><br>
            <input
                type="text"
                id="full_name"
                name="full_name"
                maxlength="100"
                required
            >
        </p>

        <p>
            <label for="username">Username</label><br>
            <input
                type="text"
                id="username"
                name="username"
                maxlength="50"
                required
            >
        </p>

        <p>
            <label for="password">Password Awal</label><br>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                maxlength="255"
                autocomplete="new-password"
                required
            >
            <small>Minimal 8 karakter. Pengguna wajib menggantinya saat login pertama.</small>
        </p>

        <p>
            <label for="role">Jenis Akun</label><br>
            <select id="role" name="role" required>
                <option value="petugas">Petugas</option>
                <option value="admin">Admin</option>
            </select>
        </p>

        <button type="submit">Buat Akun</button>
    </form>
</section>

<hr>

<section class="content-section">
    <h2>Daftar Pengguna</h2>
    <p>Total pengguna: <?= count($users) ?></p>

    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Password</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= e($user['full_name']) ?></td>
                        <td><?= e($user['username']) ?></td>
                        <td><?= e(ucfirst($user['role'])) ?></td>
                        <td>
                            <?= (int) $user['is_active'] === 1
                                ? 'Aktif'
                                : 'Nonaktif' ?>
                        </td>
                        <td>
                            <?= (int) $user['must_change_password'] === 1
                                ? 'Wajib diganti'
                                : 'Normal' ?>
                        </td>
                        <td><?= e($user['created_at']) ?></td>

                        <td>
                            <?php if ((int) $user['id'] !== $currentId): ?>

                                <details>
                                    <summary>Edit Data</summary>

                                    <form method="POST">
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrf_token()) ?>"
                                        >
                                        <input type="hidden" name="action" value="edit">
                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= (int) $user['id'] ?>"
                                        >

                                        <p>
                                            <label>Nama Lengkap</label><br>
                                            <input
                                                type="text"
                                                name="full_name"
                                                maxlength="100"
                                                value="<?= e($user['full_name']) ?>"
                                                required
                                            >
                                        </p>

                                        <p>
                                            <label>Username</label><br>
                                            <input
                                                type="text"
                                                name="username"
                                                maxlength="50"
                                                value="<?= e($user['username']) ?>"
                                                required
                                            >
                                        </p>

                                        <p>
                                            <label>Role</label><br>
                                            <select name="role" required>
                                                <option
                                                    value="petugas"
                                                    <?= $user['role'] === 'petugas' ? 'selected' : '' ?>
                                                >Petugas</option>

                                                <option
                                                    value="admin"
                                                    <?= $user['role'] === 'admin' ? 'selected' : '' ?>
                                                >Admin</option>
                                            </select>
                                        </p>

                                        <button type="submit">Simpan Perubahan</button>
                                    </form>
                                </details>

                                <details>
                                    <summary>Reset Password</summary>

                                    <form
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin mereset password pengguna ini?')"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrf_token()) ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="action"
                                            value="reset_password"
                                        >
                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= (int) $user['id'] ?>"
                                        >

                                        <p>
                                            <label>Password Sementara</label><br>
                                            <input
                                                type="password"
                                                name="new_password"
                                                minlength="8"
                                                maxlength="255"
                                                autocomplete="new-password"
                                                required
                                            >
                                        </p>

                                        <small>
                                            Pengguna wajib mengganti password ini saat login.
                                        </small>

                                        <p>
                                            <button type="submit">
                                                Reset Password
                                            </button>
                                        </p>
                                    </form>
                                </details>

                                <form method="POST" style="display:inline;">
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrf_token()) ?>"
                                    >
                                    <input type="hidden" name="action" value="toggle">
                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?= (int) $user['id'] ?>"
                                    >

                                    <button type="submit">
                                        <?= (int) $user['is_active'] === 1
                                            ? 'Nonaktifkan'
                                            : 'Aktifkan' ?>
                                    </button>
                                </form>

                                <form
                                    method="POST"
                                    style="display:inline;"
                                    onsubmit="return confirm('Yakin ingin menghapus akun ini?')"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrf_token()) ?>"
                                    >
                                    <input type="hidden" name="action" value="delete">
                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?= (int) $user['id'] ?>"
                                    >

                                    <button type="submit">Hapus</button>
                                </form>

                            <?php else: ?>
                                <small>Akun sendiri</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$users): ?>
                    <tr>
                        <td colspan="7">Belum ada pengguna.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

</main>
</div>
</body>
</html>
