<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

verify_csrf();

function edit_post_text(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function fail_edit(int $id, string $message): never
{
    flash('error', $message);
    redirect('audit/edit.php?id=' . $id);
}

// VALIDASI ID
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID audit tidak valid.');
}

// PASTIKAN AUDIT ADA
$stmt = $pdo->prepare("
    SELECT id
    FROM audit_logs
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$id]);

if (!$stmt->fetch()) {
    http_response_code(404);
    exit('Data audit tidak ditemukan.');
}

// VALIDASI DATA UTAMA
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
    if (edit_post_text($key) === '') {
        fail_edit($id, 'Semua kolom wajib harus diisi.');
    }
}

$project = strtoupper(edit_post_text('project_code'));
$session = strtoupper(edit_post_text('session_code'));
$auditCode = edit_post_text('audit_code');
$date = edit_post_text('audit_date');
$category = edit_post_text('source_category');
$auditor = edit_post_text('auditor');
$location = edit_post_text('location');
$what = edit_post_text('what_description');
$why = edit_post_text('why_description');
$how = edit_post_text('how_description');
$notes = edit_post_text('notes');

if (
    !preg_match('/^[A-Z0-9]{2,12}$/', $project) ||
    !preg_match('/^[A-Z0-9]{2,12}$/', $session)
) {
    fail_edit($id, 'Kode proyek dan sesi hanya boleh berisi huruf dan angka (2–12 karakter).');
}

if (strlen($auditCode) > 40) {
    fail_edit($id, 'Kode audit maksimal 40 karakter.');
}

if (!valid_date($date)) {
    fail_edit($id, 'Tanggal audit tidak valid.');
}

if (!in_array($category, source_categories(), true)) {
    fail_edit($id, 'Kategori sumber tidak valid.');
}

if (
    strlen($auditor) > 120 ||
    strlen($location) > 180
) {
    fail_edit($id, 'Nama petugas atau lokasi terlalu panjang.');
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
    fail_edit($id, 'Rincian limbah tidak valid. Minimal satu jenis limbah harus diisi.');
}

$total = 0.0;
$details = [];

foreach ($types as $i => $type) {
    if (
        !is_string($type) ||
        !isset($weights[$i], $wasteNotes[$i]) ||
        !is_string($weights[$i]) ||
        !is_string($wasteNotes[$i])
    ) {
        fail_edit($id, 'Data rincian limbah tidak valid.');
    }

    $type = trim($type);
    $weight = filter_var($weights[$i], FILTER_VALIDATE_FLOAT);
    $note = trim($wasteNotes[$i]);

    if (
        $type === '' ||
        strlen($type) > 150 ||
        strlen($note) > 255 ||
        $weight === false ||
        !is_finite((float) $weight) ||
        (float) $weight <= 0
    ) {
        fail_edit($id, 'Jenis limbah, berat, atau keterangan tidak valid.');
    }

    $weight = (float) $weight;
    $total += $weight;

    if (
        !is_finite($total) ||
        $total > 999999999.999
    ) {
        fail_edit($id, 'Total berat limbah tidak valid atau terlalu besar.');
    }

    $details[] = [
        $type,
        $weight,
        $note
    ];
}

// AMBIL FOTO LAMA
$stmt = $pdo->prepare("
    SELECT id, stored_name
    FROM audit_photos
    WHERE audit_id = ?
");
$stmt->execute([$id]);
$existingPhotos = $stmt->fetchAll();

$existingPhotoIds = [];
$existingPhotoNames = [];

foreach ($existingPhotos as $photo) {
    $photoId = (int) $photo['id'];
    $existingPhotoIds[] = $photoId;

    $existingPhotoNames[$photoId] = basename(
        str_replace('\\', '/', (string) $photo['stored_name'])
    );
}

// VALIDASI FOTO YANG AKAN DIHAPUS
$removeInput = $_POST['remove_photos'] ?? [];

if (!is_array($removeInput)) {
    fail_edit($id, 'Data pilihan foto tidak valid.');
}

$removeIds = [];

foreach ($removeInput as $value) {
    if (
        !is_string($value) &&
        !is_int($value)
    ) {
        fail_edit($id, 'Pilihan foto tidak valid.');
    }

    $photoId = filter_var($value, FILTER_VALIDATE_INT);

    if (
        $photoId === false ||
        !in_array((int) $photoId, $existingPhotoIds, true)
    ) {
        fail_edit($id, 'Foto yang dipilih tidak sesuai dengan audit ini.');
    }

    $removeIds[] = (int) $photoId;
}

$removeIds = array_values(array_unique($removeIds));

$retainedPhotoCount = count($existingPhotoIds) - count($removeIds);

// VALIDASI FOTO BARU
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
        fail_edit($id, 'Format upload foto tidak valid.');
    }

    $fileCount = count($files['name']);

    if (
        count($files['tmp_name']) !== $fileCount ||
        count($files['error']) !== $fileCount ||
        count($files['size']) !== $fileCount
    ) {
        fail_edit($id, 'Data upload foto tidak lengkap.');
    }

    foreach ($files['error'] as $i => $error) {
        if (
            !isset($files['name'][$i], $files['tmp_name'][$i], $files['size'][$i]) ||
            !is_string($files['name'][$i]) ||
            !is_string($files['tmp_name'][$i]) ||
            !is_int($error) ||
            !is_int($files['size'][$i])
        ) {
            fail_edit($id, 'Struktur data foto tidak valid.');
        }

        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($error !== UPLOAD_ERR_OK) {
            fail_edit($id, 'Upload foto gagal. Periksa ukuran dan kondisi file.');
        }

        $photoIndexes[] = $i;
    }
}

if ($retainedPhotoCount + count($photoIndexes) > 10) {
    fail_edit($id, 'Maksimal 10 foto untuk setiap audit.');
}

// PERSIAPAN FOLDER FOTO
if (
    !is_dir(UPLOAD_DIR) &&
    !mkdir(UPLOAD_DIR, 0755, true) &&
    !is_dir(UPLOAD_DIR)
) {
    fail_edit($id, 'Folder dokumentasi tidak dapat dibuat.');
}

if (!is_writable(UPLOAD_DIR)) {
    fail_edit($id, 'Folder dokumentasi tidak memiliki izin penyimpanan.');
}

$newUploadedFiles = [];
$oldFilesToDelete = [];
$transactionStarted = false;

try {
    $pdo->beginTransaction();
    $transactionStarted = true;

    // UPDATE DATA AUDIT
    $stmt = $pdo->prepare("
        UPDATE audit_logs SET
            audit_code = ?,
            project_code = ?,
            session_code = ?,
            audit_date = ?,
            source_category = ?,
            auditor = ?,
            location = ?,
            what_description = ?,
            why_description = ?,
            how_description = ?,
            notes = ?,
            total_weight = ?
        WHERE id = ?
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
        $total,
        $id
    ]);

    // GANTI SELURUH RINCIAN LIMBAH
    $stmt = $pdo->prepare("
        DELETE FROM waste_details
        WHERE audit_id = ?
    ");
    $stmt->execute([$id]);

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
            $id,
            $detail[0],
            $detail[1],
            $detail[2]
        ]);
    }

    // HAPUS FOTO YANG DIPILIH DARI DATABASE
    if ($removeIds) {
        $deletePhoto = $pdo->prepare("
            DELETE FROM audit_photos
            WHERE id = ? AND audit_id = ?
        ");

        foreach ($removeIds as $photoId) {
            $deletePhoto->execute([$photoId, $id]);

            if (isset($existingPhotoNames[$photoId])) {
                $oldFilesToDelete[] = UPLOAD_DIR . '/' .
                    $existingPhotoNames[$photoId];
            }
        }
    }

    // TAMBAHKAN FOTO BARU
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $photoStmt = $pdo->prepare("
        INSERT INTO audit_photos (
            audit_id,
            original_name,
            stored_name,
            mime_type,
            file_size
        ) VALUES (?, ?, ?, ?, ?)
    ");

    $seq = 1;

    foreach ($photoIndexes as $i) {
        $tmp = $files['tmp_name'][$i];

        if (!is_uploaded_file($tmp)) {
            throw new RuntimeException('File foto tidak valid.');
        }

        $actualSize = filesize($tmp);

        if (
            $actualSize === false ||
            $actualSize <= 0 ||
            $actualSize > MAX_UPLOAD_BYTES
        ) {
            throw new RuntimeException('Ukuran foto tidak valid atau melebihi 5 MB.');
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
            throw new RuntimeException('Gagal menyimpan foto baru.');
        }

        $newUploadedFiles[] = $destination;

        $originalName = basename(
            str_replace('\\', '/', $files['name'][$i])
        );

        $photoStmt->execute([
            $id,
            $originalName,
            $stored,
            $mime,
            $actualSize
        ]);
    }

    // TRANSAKSI BERHASIL
    $pdo->commit();
    $transactionStarted = false;

    // HAPUS FILE FOTO LAMA SETELAH COMMIT
    foreach ($oldFilesToDelete as $file) {
        if (is_file($file) && !unlink($file)) {
            error_log('Gagal menghapus foto lama: ' . $file);
        }
    }

    flash('success', 'Data audit berhasil diperbarui.');

    redirect('audit/detail.php?id=' . $id);

} catch (Throwable $e) {

    if ($transactionStarted && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // BERSIHKAN FOTO BARU JIKA UPDATE GAGAL
    foreach ($newUploadedFiles as $file) {
        if (is_file($file) && !unlink($file)) {
            error_log('Gagal membersihkan foto baru: ' . $file);
        }
    }

    error_log('Audit update error: ' . $e->getMessage());

    flash(
        'error',
        'Perubahan audit gagal disimpan. Periksa kembali data dan foto, lalu coba lagi.'
    );

    redirect('audit/edit.php?id=' . $id);
}
