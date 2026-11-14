<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

// VALIDASI ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID audit tidak valid.');
}

// AMBIL DATA AUDIT
$stmt = $pdo->prepare("
    SELECT *
    FROM audit_logs
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$audit = $stmt->fetch();

if (!$audit) {
    http_response_code(404);
    exit('Data audit tidak ditemukan.');
}

// AMBIL RINCIAN LIMBAH
$stmt = $pdo->prepare("
    SELECT *
    FROM waste_details
    WHERE audit_id = ?
    ORDER BY id ASC
");
$stmt->execute([$id]);
$details = $stmt->fetchAll();

// AMBIL FOTO
$stmt = $pdo->prepare("
    SELECT *
    FROM audit_photos
    WHERE audit_id = ?
    ORDER BY id ASC
");
$stmt->execute([$id]);
$photos = $stmt->fetchAll();

$pageTitle = 'Edit Audit';
$active = 'journal';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="detail-actions">
    <a
        class="btn btn-light"
        href="<?= e(app_url('audit/detail.php?id=' . $id)) ?>"
    >
        ← Kembali ke detail
    </a>

    <span class="category-chip">Edit Audit</span>
</div>

<form
    class="audit-form"
    action="<?= e(app_url('audit/update.php')) ?>"
    method="post"
    enctype="multipart/form-data"
    id="auditForm"
>

    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= e((string) $id) ?>">

    <!-- 01 IDENTITAS AUDIT -->
    <section class="panel form-panel">

        <div class="form-section-heading">
            <span class="step">01</span>
            <div>
                <h3>Identitas audit</h3>
                <p>Ubah informasi dasar pelaksanaan audit.</p>
            </div>
        </div>

        <div class="form-grid">

            <label>
                Kode proyek
                <input
                    name="project_code"
                    value="<?= e($audit['project_code']) ?>"
                    maxlength="12"
                    pattern="[A-Za-z0-9]{2,12}"
                    required
                >
            </label>

            <label>
                Kode sesi/sumber
                <input
                    name="session_code"
                    value="<?= e($audit['session_code']) ?>"
                    maxlength="12"
                    pattern="[A-Za-z0-9]{2,12}"
                    required
                >
            </label>

            <label>
                Kode audit
                <input
                    name="audit_code"
                    value="<?= e($audit['audit_code']) ?>"
                    maxlength="40"
                    required
                >
            </label>

            <label>
                Tanggal audit
                <input
                    type="date"
                    name="audit_date"
                    value="<?= e($audit['audit_date']) ?>"
                    required
                >
            </label>

            <label>
                Kategori sumber
                <select name="source_category" required>
                    <option value="">Pilih kategori</option>

                    <?php foreach (source_categories() as $category): ?>
                        <option
                            value="<?= e($category) ?>"
                            <?= $audit['source_category'] === $category ? 'selected' : '' ?>
                        >
                            <?= e($category) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Nama petugas / auditor
                <input
                    name="auditor"
                    value="<?= e($audit['auditor']) ?>"
                    maxlength="120"
                    required
                >
            </label>

            <label class="full">
                Lokasi sumber limbah
                <input
                    name="location"
                    value="<?= e($audit['location']) ?>"
                    maxlength="180"
                    required
                >
            </label>

        </div>
    </section>

    <!-- 02 DOKUMENTASI 5W1H -->
    <section class="panel form-panel">

        <div class="form-section-heading">
            <span class="step">02</span>
            <div>
                <h3>Dokumentasi 5W1H</h3>
                <p>Ubah konteks dan informasi pelaksanaan audit.</p>
            </div>
        </div>

        <div class="form-grid">

            <label class="full">
                WHAT · Jenis dan kondisi limbah
                <textarea
                    name="what_description"
                    rows="2"
                    required
                ><?= e($audit['what_description']) ?></textarea>
            </label>

            <label class="full">
                WHY · Tujuan / alasan audit
                <textarea
                    name="why_description"
                    rows="2"
                    required
                ><?= e($audit['why_description']) ?></textarea>
            </label>

            <label class="full">
                HOW · Metode pengumpulan dan penimbangan
                <textarea
                    name="how_description"
                    rows="2"
                    required
                ><?= e($audit['how_description']) ?></textarea>
            </label>

            <label class="full">
                Catatan tambahan
                <textarea
                    name="notes"
                    rows="2"
                ><?= e($audit['notes'] ?? '') ?></textarea>
            </label>

        </div>
    </section>

    <!-- 03 RINCIAN LIMBAH -->
    <section class="panel form-panel">

        <div class="form-section-heading">
            <span class="step">03</span>
            <div>
                <h3>Rincian berat limbah</h3>
                <p>Ubah, hapus, atau tambahkan jenis limbah.</p>
            </div>
        </div>

        <div class="detail-table-wrap">
            <table class="detail-table">
                <thead>
                    <tr>
                        <th>Jenis limbah</th>
                        <th>Berat (kg)</th>
                        <th>Keterangan</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody id="wasteRows">

                    <?php foreach ($details as $item): ?>
                        <tr class="waste-row">
                            <td>
                                <input
                                    name="waste_type[]"
                                    value="<?= e($item['waste_type']) ?>"
                                    required
                                >
                            </td>

                            <td>
                                <input
                                    name="weight[]"
                                    type="number"
                                    min="0.001"
                                    step="0.001"
                                    value="<?= e((string) $item['weight_kg']) ?>"
                                    required
                                    class="weight-input"
                                >
                            </td>

                            <td>
                                <input
                                    name="waste_note[]"
                                    value="<?= e($item['note'] ?? '') ?>"
                                    placeholder="Opsional"
                                >
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="icon-button remove-row"
                                    aria-label="Hapus baris"
                                >×</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$details): ?>
                        <tr class="waste-row">
                            <td><input name="waste_type[]" required></td>
                            <td>
                                <input
                                    name="weight[]"
                                    type="number"
                                    min="0.001"
                                    step="0.001"
                                    required
                                    class="weight-input"
                                >
                            </td>
                            <td><input name="waste_note[]" placeholder="Opsional"></td>
                            <td>
                                <button
                                    type="button"
                                    class="icon-button remove-row"
                                    aria-label="Hapus baris"
                                >×</button>
                            </td>
                        </tr>
                    <?php endif; ?>

                </tbody>
            </table>
        </div>

        <button
            type="button"
            class="btn btn-light btn-small"
            id="addWasteRow"
        >
            ＋ Tambah jenis limbah
        </button>

        <div class="total-box">
            <span>Total berat limbah</span>
            <strong>
                <span id="totalWeight">0,000</span> kg
            </strong>
        </div>

    </section>

    <!-- 04 FOTO LAMA -->
    <section class="panel form-panel">

        <div class="form-section-heading">
            <span class="step">04</span>
            <div>
                <h3>Foto dokumentasi tersimpan</h3>
                <p>
                    Centang foto yang ingin dihapus.
                    Foto yang tidak dicentang akan tetap disimpan.
                </p>
            </div>
        </div>

        <?php if ($photos): ?>

            <div class="photo-grid saved-photos">

                <?php foreach ($photos as $photo): ?>
                    <?php
                    $storedName = basename(
                        str_replace('\\', '/', (string) $photo['stored_name'])
                    );

                    $photoUrl = app_url(
                        'uploads/dokumentasi/' . rawurlencode($storedName)
                    );
                    ?>

                    <label class="saved-photo">

                        <img
                            src="<?= e($photoUrl) ?>"
                            alt="Dokumentasi audit"
                            loading="lazy"
                        >

                        <span><?= e($storedName) ?></span>

                        <span>
                            <input
                                type="checkbox"
                                name="remove_photos[]"
                                value="<?= (int) $photo['id'] ?>"
                            >
                            Hapus foto ini
                        </span>

                    </label>
                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <p class="muted">Belum ada foto dokumentasi.</p>

        <?php endif; ?>

    </section>

    <!-- 05 FOTO BARU -->
    <section class="panel form-panel">

        <div class="form-section-heading">
            <span class="step">05</span>
            <div>
                <h3>Tambah foto baru</h3>
                <p>
                    Foto lama yang tidak dihapus akan tetap tersimpan.
                </p>
            </div>
        </div>

        <div class="upload-hint">
            <b>Format:</b> JPG, PNG, atau WEBP
            <span>Maks. 5 MB/foto · Maks. 10 foto keseluruhan</span>
        </div>

        <label class="upload-zone" for="photos">

            <span class="upload-symbol">↑</span>
            <b>Pilih foto dokumentasi baru</b>
            <small>Pilih satu atau beberapa foto</small>

            <input
                id="photos"
                type="file"
                name="photos[]"
                accept="image/jpeg,image/png,image/webp"
                multiple
            >

        </label>

        <div id="photoPreview" class="photo-grid"></div>

        <div class="form-footer">

            <a
                class="btn btn-light"
                href="<?= e(app_url('audit/detail.php?id=' . $id)) ?>"
            >
                Batal
            </a>

            <button class="btn btn-primary" type="submit">
                Simpan Perubahan →
            </button>

        </div>

    </section>

</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
