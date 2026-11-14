<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

verify_csrf();

function post_text(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function fail_audit(string $message): never
{
    flash('error', $message);
    redirect('audit/tambah.php');
}

// VALIDASI IDENTITAS DAN 5W1H
$required = [
    'project_code',
    'session_code',
    'audit_code',
    'audit_date',
    'source_category',
    'auditor',
    'location',
    'what_description',
    'why_description',
    'how_description'
];

foreach ($required as $key) {
    if (post_text($key) === '') {
        fail_audit('Semua kolom wajib harus diisi.');
    }
}

$project = strtoupper(post_text('project_code'));
$session = strtoupper(post_text('session_code'));
$auditCode = post_text('audit_code');
$date = post_text('audit_date');
$category = post_text('source_category');
$auditor = post_text('auditor');
$location = post_text('location');
$what = post_text('what_description');
$why = post_text('why_description');
$how = post_text('how_description');
$notes = post_text('notes');

if (
    !preg_match('/^[A-Z0-9]{2,12}$/', $project) ||
    !preg_match('/^[A-Z0-9]{2,12}$/', $session)
) {
    fail_audit('Kode proyek dan sesi hanya boleh berisi huruf dan angka (2–12 karakter).');
}

if (strlen($auditCode) > 40) {
    fail_audit('Kode audit maksimal 40 karakter.');
}

if (!valid_date($date)) {
    fail_audit('Tanggal audit tidak valid.');
}

if (!in_array($category, source_categories(), true)) {
    fail_audit('Kategori sumber tidak valid.');
}

// VALIDASI RINCIAN LIMBAH
$types = $_POST['waste_type'] ?? [];
$weights = $_POST['weight'] ?? [];
$wasteNotes = $_POST['waste_note'] ?? [];

if (
    !is_array($types) ||
    !is_array($weights) ||
    !is_array($wasteNotes) ||
    count($types) < 1 ||
    count($types) > 100 ||
    count($types) !== count($weights) ||
    count($types) !== count($wasteNotes)
) {
    fail_audit('Rincian limbah tidak valid. Minimal satu jenis limbah harus diisi.');
}

$total = 0.0;
$details = [];

foreach ($types as $i => $type) {
    if (
        !is_string($type) ||
        !isset($weights[$i]) ||
        !is_string($weights[$i]) ||
        !isset($wasteNotes[$i]) ||
        !is_string($wasteNotes[$i])
    ) {
        fail_audit('Data rincian limbah tidak valid.');
    }

    $type = trim($type);
    $weight = filter_var($weights[$i], FILTER_VALIDATE_FLOAT);
    $note = trim($wasteNotes[$i]);

    if (
        $type === '' ||
        $weight === false ||
        !is_finite((float) $weight) ||
        (float) $weight <= 0
    ) {
        fail_audit('Jenis limbah dan berat harus diisi dengan benar.');
    }

    $weight = (float) $weight;
    $total += $weight;

    if (!is_finite($total)) {
        fail_audit('Total berat limbah tidak valid.');
    }

    $details[] = [
        $type,
        $weight,
        $note
    ];
}

// VALIDASI STRUKTUR FOTO
$files = $_FILES['photos'] ?? null;
$photoIndexes = [];

if ($files !== null) {
    if (
        !is_array($files) ||
        !isset(
            $files['name'],
            $files['tmp_name'],
            $files['error'],
            $files['size']
        ) ||
        !is_array($files['name']) ||
        !is_array($files['tmp_name']) ||
        !is_array($files['error']) ||
        !is_array($files['size'])
    ) {
        fail_audit('Format upload foto tidak valid.');
    }

    $fileCount = count($files['name']);

    if (
        count($files['tmp_name']) !== $fileCount ||
        count($files['error']) !== $fileCount ||
        count($files['size']) !== $fileCount
    ) {
        fail_audit('Data upload foto tidak lengkap.');
    }

    foreach ($files['error'] as $i => $error) {
        if (
            !isset($files['name'][$i], $files['tmp_name'][$i], $files['size'][$i]) ||
            !is_string($files['name'][$i]) ||
            !is_string($files['tmp_name'][$i]) ||
            !is_int($error) ||
            !is_int($files['size'][$i])
        ) {
            fail_audit('Struktur data foto tidak valid.');
        }

        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($error !== UPLOAD_ERR_OK) {
            fail_audit('Upload foto gagal. Periksa ukuran dan kondisi file.');
        }

        $photoIndexes[] = $i;
    }

    if (count($photoIndexes) > 10) {
        fail_audit('Maksimal 10 foto untuk setiap audit.');
    }
}

// PERSIAPAN FOLDER
if (
    !is_dir(UPLOAD_DIR) &&
    !mkdir(UPLOAD_DIR, 0755, true) &&
    !is_dir(UPLOAD_DIR)
) {
    fail_audit('Folder dokumentasi tidak dapat dibuat.');
}

if (!is_writable(UPLOAD_DIR)) {
    fail_audit('Folder dokumentasi tidak memiliki izin penyimpanan.');
}

$uploadedFiles = [];
$transactionStarted = false;

try {
    $pdo->beginTransaction();
    $transactionStarted = true;

    // SIMPAN DATA AUDIT
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (
            audit_code,
            project_code,
            session_code,
            audit_date,
            source_category,
            auditor,
            location,
            what_description,
            why_description,
            how_description,
            notes,
            total_weight
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $auditCode,
        $project,
        $session,
        $date,
        $category,
        $auditor,
        $location,
        $what,
        $why,
        $how,
        $notes,
        $total
    ]);

    $auditId = (int) $pdo->lastInsertId();

    // SIMPAN RINCIAN LIMBAH
    $detailStmt = $pdo->prepare("
        INSERT INTO waste_details (
            audit_id,
            waste_type,
            weight_kg,
            note
        ) VALUES (?, ?, ?, ?)
    ");

    foreach ($details as $detail) {
        $detailStmt->execute([
            $auditId,
            $detail[0],
            $detail[1],
            $detail[2]
        ]);
    }

    // SIAPKAN PENYIMPANAN FOTO
    $photoStmt = $pdo->prepare("
        INSERT INTO audit_photos (
            audit_id,
            original_name,
            stored_name,
            mime_type,
            file_size
        ) VALUES (?, ?, ?, ?, ?)
    ");

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $seq = 1;

    foreach ($photoIndexes as $i) {
        $tmp = $files['tmp_name'][$i];

        if (!is_uploaded_file($tmp)) {
            throw new RuntimeException('File foto tidak valid.');
        }

        $actualSize = filesize($tmp);

        if ($actualSize === false || $actualSize <= 0) {
            throw new RuntimeException('Ukuran foto tidak valid.');
        }

        if ($actualSize > MAX_UPLOAD_BYTES) {
            throw new RuntimeException('Ukuran foto melebihi 5 MB.');
        }

        $mime = $finfo->file($tmp);

        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Foto harus berupa JPG, PNG, atau WEBP.');
        }

        $imageInfo = getimagesize($tmp);

        if (
            $imageInfo === false ||
            ($imageInfo['mime'] ?? '') !== $mime
        ) {
            throw new RuntimeException('Isi file bukan gambar yang valid.');
        }

        // CEGAH NAMA FOTO BENTROK
        do {
            $stored = make_photo_name(
                $project,
                $session,
                $date,
                $seq++,
                $allowed[$mime]
            );

            $destination = UPLOAD_DIR . '/' . $stored;

        } while (file_exists($destination));

        if (!move_uploaded_file($tmp, $destination)) {
            throw new RuntimeException('Gagal menyimpan foto ke server.');
        }

        $uploadedFiles[] = $destination;

        $originalName = basename(
            str_replace('\\', '/', $files['name'][$i])
        );

        $photoStmt->execute([
            $auditId,
            $originalName,
            $stored,
            $mime,
            $actualSize
        ]);
    }

    $pdo->commit();
    $transactionStarted = false;

    flash(
        'success',
        'Data audit berhasil disimpan beserta rincian limbah dan dokumentasi foto.'
    );

    redirect('audit/detail.php?id=' . $auditId);

} catch (Throwable $e) {

    if ($transactionStarted && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // HAPUS FOTO JIKA TRANSAKSI GAGAL
    foreach ($uploadedFiles as $file) {
        if (is_file($file) && !unlink($file)) {
            error_log('Gagal menghapus foto sementara: ' . $file);
        }
    }

    error_log('Audit save error: ' . $e->getMessage());

    flash(
        'error',
        'Data gagal disimpan. Periksa kembali isian dan dokumentasi foto, lalu coba lagi.'
    );

    redirect('audit/tambah.php');
}
