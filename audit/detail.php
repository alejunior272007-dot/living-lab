<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_login();

// IDENTITAS DAN HAK AKSES
$currentUser = current_user();
$isAdmin = $currentUser && ($currentUser['role'] ?? '') === 'admin';

// VALIDASI ID AUDIT
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID audit tidak valid.');
}

// AMBIL DATA AUDIT
$stmt = $pdo->prepare(
    "SELECT * FROM audit_logs WHERE id = ? LIMIT 1"
);
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

// AMBIL DOKUMENTASI FOTO
$stmt = $pdo->prepare("
    SELECT *
    FROM audit_photos
    WHERE audit_id = ?
    ORDER BY id ASC
");
$stmt->execute([$id]);
$photos = $stmt->fetchAll();

$pageTitle = 'Detail Audit';
$active = 'journal';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="detail-actions">

    <a class="btn btn-light"
       href="<?= e(app_url('jurnal/index.php')) ?>">
        ← Kembali ke jurnal
    </a>

    <span class="category-chip">
        <?= e($audit['source_category']) ?>
    </span>

</div>

<?php if ($isAdmin): ?>

    <div class="detail-actions">

        <a
            class="btn btn-primary"
            href="<?= e(app_url('audit/edit.php?id=' . $id)) ?>"
        >
            ✎ Edit Audit
        </a>

        <form
            action="<?= e(app_url('audit/hapus.php')) ?>"
            method="post"
            onsubmit="return confirm('Yakin ingin menghapus audit ini? Seluruh rincian dan foto dokumentasinya juga akan dihapus. Tindakan ini tidak dapat dibatalkan.');"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <input
                type="hidden"
                name="id"
                value="<?= e((string) $id) ?>"
            >

            <button type="submit" class="btn btn-light">
                Hapus Audit
            </button>

        </form>

    </div>

<?php endif; ?>

<section class="panel detail-panel">

    <div class="detail-title">

        <div>
            <span class="eyebrow">KODE AUDIT</span>

            <h2><?= e($audit['audit_code']) ?></h2>

            <p>
                <?= e(date(
                    'd F Y',
                    strtotime($audit['audit_date'])
                )) ?>
                · <?= e($audit['location']) ?>
            </p>
        </div>

        <div class="detail-total">
            <strong>
                <?= number_format(
                    (float) $audit['total_weight'],
                    3,
                    ',',
                    '.'
                ) ?>
            </strong>

            <span>kg total</span>
        </div>

    </div>

    <div class="detail-grid">

        <div>
            <small>WHO · Petugas</small>
            <b><?= e($audit['auditor']) ?></b>
        </div>

        <div>
            <small>WHERE · Lokasi</small>
            <b><?= e($audit['location']) ?></b>
        </div>

        <div>
            <small>WHEN · Tanggal</small>
            <b>
                <?= e(date(
                    'd/m/Y',
                    strtotime($audit['audit_date'])
                )) ?>
            </b>
        </div>

        <div>
            <small>Kategori sumber</small>
            <b><?= e($audit['source_category']) ?></b>
        </div>

        <div>
            <small>Kode proyek</small>
            <b><?= e($audit['project_code']) ?></b>
        </div>

        <div>
            <small>Kode sesi/sumber</small>
            <b><?= e($audit['session_code']) ?></b>
        </div>

    </div>

    <?php
    $descriptions = [
        ['WHAT', 'Jenis dan kondisi limbah', 'what_description'],
        ['WHY', 'Tujuan / alasan audit', 'why_description'],
        ['HOW', 'Metode pengumpulan dan penimbangan', 'how_description']
    ];
    ?>

    <?php foreach ($descriptions as [$tag, $label, $field]): ?>

        <div class="detail-description">

            <small><?= e($tag) ?> · <?= e($label) ?></small>

            <p><?= nl2br(e($audit[$field])) ?></p>

        </div>

    <?php endforeach; ?>

    <?php if (trim((string) $audit['notes']) !== ''): ?>

        <div class="detail-description">
            <small>CATATAN TAMBAHAN</small>
            <p><?= nl2br(e($audit['notes'])) ?></p>
        </div>

    <?php endif; ?>

</section>

<section class="panel form-panel">

    <div class="panel-heading">
        <div>
            <h3>Rincian limbah</h3>
            <p>Jenis dan berat yang dicatat dalam audit ini</p>
        </div>

        <span class="pill">
            <?= count($details) ?> jenis
        </span>
    </div>

    <div class="table-wrap">

        <table>
            <thead>
                <tr>
                    <th>Jenis limbah</th>
                    <th>Berat</th>
                    <th>Keterangan</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!$details): ?>
                    <tr>
                        <td colspan="3" class="empty">
                            Belum ada rincian limbah.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($details as $item): ?>
                    <tr>
                        <td><?= e($item['waste_type']) ?></td>

                        <td>
                            <b>
                                <?= number_format(
                                    (float) $item['weight_kg'],
                                    3,
                                    ',',
                                    '.'
                                ) ?> kg
                            </b>
                        </td>

                        <td><?= e($item['note']) ?></td>
                    </tr>
                <?php endforeach; ?>

            </tbody>
        </table>

    </div>

</section>

<section class="panel form-panel">

    <div class="panel-heading">
        <div>
            <h3>Galeri dokumentasi</h3>
            <p>Foto bukti pelaksanaan audit</p>
        </div>

        <span class="pill">
            <?= count($photos) ?> foto
        </span>
    </div>

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

            <a
                class="saved-photo"
                href="<?= e($photoUrl) ?>"
                target="_blank"
                rel="noopener noreferrer"
            >

                <img
                    src="<?= e($photoUrl) ?>"
                    alt="Dokumentasi audit"
                    loading="lazy"
                >

                <span>
                    <?= e($storedName) ?>
                </span>

            </a>

        <?php endforeach; ?>

        <?php if (!$photos): ?>
            <p class="muted">
                Belum ada foto dokumentasi untuk audit ini.
            </p>
        <?php endif; ?>

    </div>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
```