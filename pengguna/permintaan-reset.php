<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$pageTitle = 'Permintaan Reset Password';
$active = 'users';

$admin = current_user();
$adminId = (int) $admin['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $requestId = filter_var(
        $_POST['request_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $action = $_POST['action'] ?? '';
    $note = $_POST['admin_note'] ?? '';

    if (
        !$requestId ||
        !is_string($action) ||
        !in_array($action, ['approve', 'reject'], true) ||
        !is_string($note)
    ) {
        flash('error', 'Data permintaan tidak valid.');
        redirect('pengguna/permintaan-reset.php');
    }

    $note = trim($note);

    if (strlen($note) > 1000) {
        flash('error', 'Catatan Admin maksimal 1000 karakter.');
        redirect('pengguna/permintaan-reset.php');
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "SELECT
                r.id,
                r.user_id,
                r.status,
                u.role,
                u.is_active
             FROM password_reset_requests r
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.id = ?
             FOR UPDATE"
        );

        $stmt->execute([$requestId]);
        $request = $stmt->fetch();

        if (!$request || $request['status'] !== 'pending') {
            throw new RuntimeException(
                'Permintaan tidak ditemukan atau sudah diproses.'
            );
        }

        if (
            $request['role'] !== 'petugas' ||
            (int) $request['is_active'] !== 1
        ) {
            throw new RuntimeException(
                'Akun Petugas tidak aktif atau tidak memenuhi syarat.'
            );
        }

        if ($action === 'approve') {
            $temporaryPassword = $_POST['temporary_password'] ?? '';

            if (
                !is_string($temporaryPassword) ||
                strlen($temporaryPassword) < 8 ||
                strlen($temporaryPassword) > 255
            ) {
                throw new RuntimeException(
                    'Password sementara harus terdiri dari 8 sampai 255 karakter.'
                );
            }

            $passwordHash = password_hash(
                $temporaryPassword,
                PASSWORD_DEFAULT
            );

            $updateUser = $pdo->prepare(
                "UPDATE users
                 SET password = ?,
                     must_change_password = 1
                 WHERE id = ?"
            );

            $updateUser->execute([
                $passwordHash,
                $request['user_id']
            ]);

            $status = 'approved';
        } else {
            $status = 'rejected';
        }

        $updateRequest = $pdo->prepare(
            "UPDATE password_reset_requests
             SET status = ?,
                 admin_note = ?,
                 processed_at = NOW(),
                 processed_by = ?
             WHERE id = ?
               AND status = 'pending'"
        );

        $updateRequest->execute([
            $status,
            $note !== '' ? $note : null,
            $adminId,
            $requestId
        ]);

        $pdo->commit();

        if ($action === 'approve') {
            flash(
                'success',
                'Permintaan disetujui. Sampaikan password sementara kepada Petugas melalui jalur komunikasi langsung.'
            );
        } else {
            flash('success', 'Permintaan reset password berhasil ditolak.');
        }

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Password reset request error: ' . $e->getMessage());

        flash(
            'error',
            $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Terjadi kesalahan saat memproses permintaan.'
        );
    }

    redirect('pengguna/permintaan-reset.php');
}

$stmt = $pdo->query(
    "SELECT
        r.id,
        r.reason,
        r.status,
        r.admin_note,
        r.requested_at,
        r.processed_at,
        u.full_name,
        u.username
     FROM password_reset_requests r
     INNER JOIN users u ON u.id = r.user_id
     ORDER BY
        CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END,
        r.requested_at DESC"
);

$requests = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="content-section">
    <h2>Permintaan Reset Password</h2>
    <p>
        Periksa permintaan Petugas sebelum menyetujui atau menolaknya.
    </p>

    <p>
        <a href="<?= e(app_url('pengguna/index.php')) ?>">
            ← Kembali ke Manajemen Pengguna
        </a>
    </p>

    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Petugas</th>
                    <th>Username</th>
                    <th>Alasan</th>
                    <th>Status</th>
                    <th>Tanggal Pengajuan</th>
                    <th>Proses</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($requests as $item): ?>
                    <tr>
                        <td><?= e($item['full_name']) ?></td>
                        <td><?= e($item['username']) ?></td>
                        <td><?= nl2br(e($item['reason'])) ?></td>
                        <td><?= e(ucfirst($item['status'])) ?></td>
                        <td><?= e($item['requested_at']) ?></td>
                        <td>
                            <?php if ($item['status'] === 'pending'): ?>
                                <details>
                                    <summary>Proses Permintaan</summary>

                                    <form method="POST">
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrf_token()) ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?= (int) $item['id'] ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="action"
                                            value="approve"
                                        >

                                        <p>
                                            <label>Password Sementara</label><br>
                                            <input
                                                type="password"
                                                name="temporary_password"
                                                minlength="8"
                                                maxlength="255"
                                                autocomplete="new-password"
                                                required
                                            >
                                        </p>

                                        <p>
                                            <label>Catatan Admin (opsional)</label><br>
                                            <textarea
                                                name="admin_note"
                                                rows="2"
                                                maxlength="1000"
                                            ></textarea>
                                        </p>

                                        <button type="submit">
                                            Setujui & Reset Password
                                        </button>
                                    </form>

                                    <hr>

                                    <form
                                        method="POST"
                                        onsubmit="return confirm('Tolak permintaan reset ini?')"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrf_token()) ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?= (int) $item['id'] ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="action"
                                            value="reject"
                                        >

                                        <p>
                                            <label>Alasan Penolakan (opsional)</label><br>
                                            <textarea
                                                name="admin_note"
                                                rows="2"
                                                maxlength="1000"
                                            ></textarea>
                                        </p>

                                        <button type="submit">
                                            Tolak Permintaan
                                        </button>
                                    </form>
                                </details>
                            <?php else: ?>
                                <?= e($item['admin_note'] ?? '-') ?>
                                <br>
                                <small>
                                    Diproses:
                                    <?= e($item['processed_at'] ?? '-') ?>
                                </small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$requests): ?>
                    <tr>
                        <td colspan="6">
                            Belum ada permintaan reset password.
                        </td>
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
