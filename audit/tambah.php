<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = 'Input Audit Baru';
$active = 'new';

require_once __DIR__ . '/../includes/header.php';
?>

<form
    class="audit-form"
    action="<?= e(app_url('audit/simpan.php')) ?>"
    method="post"
    enctype="multipart/form-data"
    id="auditForm"
>

    <input
        type="hidden"
        name="csrf_token"
        value="<?= e(csrf_token()) ?>"
    >

    <!-- 01 IDENTITAS AUDIT -->
    <section class="panel form-panel">

        <div class="form-section-heading">
            <span class="step">01</span>

            <div>
                <h3>Identitas audit</h3>
                <p>Informasi dasar pelaksanaan audit limbah.</p>
            </div>
        </div>

        <div class="form-grid">

            <label>
                Kode proyek
                <input
                    name="project_code"
                    value="P01"
                    maxlength="12"
                    pattern="[A-Za-z0-9]{2,12}"
                    required
                >
                <small>Contoh: P01</small>
            </label>

            <label>
                Kode sesi/sumber
                <input
                    name="session_code"
                    value="S04"
                    maxlength="12"
                    pattern="[A-Za-z0-9]{2,12}"
                    required
                >
                <small>Contoh: S04</small>
            </label>

            <label>
                Kode audit
                <input
                    name="audit_code"
                    placeholder="AUD-2026-001"
                    maxlength="40"
                    required
                >
            </label>

            <label>
                Tanggal audit
                <input
                    type="date"
                    name="audit_date"
                    value="<?= e(date('Y-m-d')) ?>"
                    required
                >
            </label>

            <label>
                Kategori sumber

                <select name="source_category" required>
                    <option value="">Pilih kategori</option>

                    <?php foreach (source_categories() as $category): ?>
                        <option value="<?= e($category) ?>">
                            <?= e($category) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </label>

            <label>
                Nama petugas / auditor
                <input
                    name="auditor"
                    required
                    maxlength="120"
                    placeholder="Nama petugas"
                >
            </label>

            <label class="full">
                Lokasi sumber limbah
                <input
                    name="location"
                    required
                    maxlength="180"
                    placeholder="Alamat, RT/RW, nama warung, atau unit dapur"
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
                <p>Catat konteks audit agar data dapat ditelusuri.</p>
            </div>
        </div>

        <div class="form-grid">

            <label class="full">
                WHAT · Jenis dan kondisi limbah
                <textarea
                    name="what_description"
                    required
                    rows="2"
                    placeholder="Contoh: sisa sayuran, kulit buah, nasi sisa dalam kondisi basah..."
                ></textarea>
            </label>

            <label class="full">
                WHY · Tujuan / alasan audit
                <textarea
                    name="why_description"
                    required
                    rows="2"
                    placeholder="Mengapa sumber ini diaudit?"
                ></textarea>
            </label>

            <label class="full">
                HOW · Metode pengumpulan dan penimbangan
                <textarea
                    name="how_description"
                    required
                    rows="2"
                    placeholder="Contoh: dipilah, dimasukkan ke wadah, lalu ditimbang menggunakan timbangan digital."
                ></textarea>
            </label>

            <label class="full">
                Catatan tambahan
                <textarea
                    name="notes"
                    rows="2"
                    placeholder="Kendala, kondisi khusus, atau catatan lapangan (opsional)"
                ></textarea>
            </label>

        </div>

        <div class="who-when-where">
            <span><b>WHO</b> Petugas diisi pada identitas audit</span>
            <span><b>WHEN</b> Tanggal audit diisi pada identitas audit</span>
            <span><b>WHERE</b> Lokasi diisi pada identitas audit</span>
        </div>

    </section>

    <!-- 03 RINCIAN LIMBAH -->
    <section class="panel form-panel">

        <div class="form-section-heading">
            <span class="step">03</span>

            <div>
                <h3>Rincian berat limbah</h3>
                <p>
                    Tambahkan satu atau beberapa jenis limbah.
                    Total dihitung otomatis.
                </p>
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

                    <tr class="waste-row">

                        <td>
                            <input
                                name="waste_type[]"
                                required
                                placeholder="Mis. sisa sayuran"
                            >
                        </td>

                        <td>
                            <input
                                name="weight[]"
                                type="number"
                                min="0.001"
                                step="0.001"
                                required
                                class="weight-input"
                                placeholder="0.000"
                            >
                        </td>

                        <td>
                            <input
                                name="waste_note[]"
                                placeholder="Opsional"
                            >
                        </td>

                        <td>
                            <button
                                type="button"
                                class="icon-button remove-row"
                                aria-label="Hapus baris"
                            >
                                ×
                            </button>
                        </td>

                    </tr>

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

    <!-- 04 DOKUMENTASI FOTO -->
    <section class="panel form-panel">

        <div class="form-section-heading">
            <span class="step">04</span>

            <div>
                <h3>Dokumentasi foto</h3>
                <p>
                    Foto bukti audit. Format nama otomatis mengikuti
                    kode proyek, sesi, dan tanggal.
                </p>
            </div>
        </div>

        <div class="upload-hint">
            <b>Standar nama:</b>
            P01_FOTO_S04_YYYYMMDD_001.jpg

            <span>
                JPG, PNG, atau WEBP · Maks. 5 MB/foto · Maks. 10 foto
            </span>
        </div>

        <label class="upload-zone" for="photos">

            <span class="upload-symbol">↑</span>

            <b>Pilih foto dokumentasi</b>

            <small>
                Anda dapat memilih maksimal 10 foto sekaligus
            </small>

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
                href="<?= e(app_url('index.php')) ?>"
            >
                Batal
            </a>

            <button class="btn btn-primary" type="submit">
                Simpan data audit →
            </button>

        </div>

    </section>

</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
