<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

// KHUSUS ADMIN
require_role('admin');

// HANYA BOLEH POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

// VALIDASI CSRF
verify_csrf();

// VALIDASI ID
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID audit tidak valid.');
}

$transactionStarted = false;
$photoFiles = [];

try {
    $pdo->beginTransaction();
    $transactionStarted = true;

    // KUNCI DAN PASTIKAN DATA AUDIT ADA
    $stmt = $pdo->prepare("
        SELECT id
        FROM audit_logs
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->execute([$id]);

    if (!$stmt->fetch()) {
        $pdo->rollBack();
        $transactionStarted = false;

        http_response_code(404);
        exit('Data audit tidak ditemukan.');
    }

    // AMBIL NAMA FOTO YANG TERKAIT DENGAN AUDIT
    $stmt = $pdo->prepare("
        SELECT stored_name
        FROM audit_photos
        WHERE audit_id = ?
    ");

    $stmt->execute([$id]);
    $photos = $stmt->fetchAll();

    foreach ($photos as $photo) {
        $storedName = basename(
            str_replace('\\', '/', (string) $photo['stored_name'])
        );

        if ($storedName !== '' && $storedName !== '.' && $storedName !== '..') {
            $photoFiles[] = UPLOAD_DIR . '/' . $storedName;
        }
    }

    // HAPUS DATA FOTO DARI DATABASE
    $stmt = $pdo->prepare("
        DELETE FROM audit_photos
        WHERE audit_id = ?
    ");
    $stmt->execute([$id]);

    // HAPUS RINCIAN LIMBAH
    $stmt = $pdo->prepare("
        DELETE FROM waste_details
        WHERE audit_id = ?
    ");
    $stmt->execute([$id]);

    // HAPUS DATA UTAMA AUDIT
    $stmt = $pdo->prepare("
        DELETE FROM audit_logs
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Data audit gagal dihapus.');
    }

    // TRANSAKSI DATABASE BERHASIL
    $pdo->commit();
    $transactionStarted = false;

    // HAPUS FILE FOTO FISIK SETELAH COMMIT
    foreach ($photoFiles as $file) {
        if (is_file($file) && !unlink($file)) {
            error_log('Gagal menghapus foto audit: ' . $file);
        }
    }

    flash(
        'success',
        'Data audit beserta rincian limbah dan dokumentasi berhasil dihapus.'
    );

    redirect('jurnal/index.php');

} catch (Throwable $e) {

    if ($transactionStarted && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Audit delete error: ' . $e->getMessage());

    flash(
        'error',
        'Data audit gagal dihapus. Silakan coba kembali atau hubungi Admin.'
    );

    redirect('audit/detail.php?id=' . $id);
}
