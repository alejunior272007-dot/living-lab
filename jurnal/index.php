<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = 'Jurnal Audit';
$active = 'journal';

$currentUser = current_user();
$isAdmin = $currentUser && $currentUser['role'] === 'admin';

// AMBIL PARAMETER URL DENGAN AMAN
function journal_get_string(string $key): string
{
    $value = $_GET[$key] ?? '';

    return is_string($value) ? trim($value) : '';
}

$search = journal_get_string('search');
$category = journal_get_string('category');
$from = journal_get_string('from');
$to = journal_get_string('to');

// BATASI PANJANG PENCARIAN
if (function_exists('mb_substr')) {
    $search = mb_substr($search, 0, 100);
} else {
    $search = substr($search, 0, 100);
}

// VALIDASI FILTER
if (!in_array($category, source_categories(), true)) {
    $category = '';
}

$validFrom = $from !== '' && valid_date($from);
$validTo = $to !== '' && valid_date($to);

$invalidDate = ($from !== '' && !$validFrom)
    || ($to !== '' && !$validTo);

$dateRangeInvalid = $validFrom && $validTo && $from > $to;

$where = [];
$params = [];

// PENCARIAN
if ($search !== '') {
    $where[] = "(
        audit_code LIKE ?
        OR auditor LIKE ?
        OR location LIKE ?
    )";

    $keyword = '%' . $search . '%';

    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
}

// FILTER KATEGORI
if ($category !== '') {
    $where[] = 'source_category = ?';
    $params[] = $category;
}

// FILTER TANGGAL
if (!$dateRangeInvalid && !$invalidDate) {
    if ($validFrom) {
        $where[] = 'audit_date >= ?';
        $params[] = $from;
    }

    if ($validTo) {
        $where[] = 'audit_date <= ?';
        $params[] = $to;
    }
}

// AMBIL DATA JURNAL
$sql = "
    SELECT
        id,
        audit_code,
        audit_date,
        source_category,
        auditor,
        location,
        total_weight
    FROM audit_logs
";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY audit_date DESC, id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll();

// HITUNG RINGKASAN
$total = array_sum(
    array_map(
        fn($row) => (float) $row['total_weight'],
        $rows
    )
);

// PARAMETER EKSPOR
$exportParams = [];

if ($search !== '') {
    $exportParams['search'] = $search;
}

if ($category !== '') {
    $exportParams['category'] = $category;
}

if ($validFrom && !$dateRangeInvalid && !$invalidDate) {
    $exportParams['from'] = $from;
}

if ($validTo && !$dateRangeInvalid && !$invalidDate) {
    $exportParams['to'] = $to;
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="panel journal-panel">

    <div class="panel-heading journal-heading">
        <div>
            <h3>Riwayat pencatatan</h3>
            <p>
                Cari dan telusuri seluruh data audit yang telah tersimpan.
            </p>
        </div>

        <a class="btn btn-primary"
           href="<?= e(app_url('audit/tambah.php')) ?>">
            ＋ Input audit
        </a>
    </div>

    <form class="filter-bar journal-filter" method="GET">

        <label class="journal-search">
            Cari audit
            <input
                type="search"
                name="search"
                maxlength="100"
                placeholder="Kode audit, petugas, atau lokasi..."
                value="<?= e($search) ?>"
            >
        </label>

        <label>
            Kategori sumber
            <select name="category">
                <option value="">Semua kategori</option>

                <?php foreach (source_categories() as $c): ?>
                    <option
                        value="<?= e($c) ?>"
                        <?= $category === $c ? 'selected' : '' ?>
                    >
                        <?= e($c) ?>
                    </option>
                <?php endforeach; ?>

            </select>
        </label>

        <label>
            Dari tanggal
            <input
                type="date"
                name="from"
                value="<?= e($validFrom ? $from : '') ?>"
            >
        </label>

        <label>
            Sampai tanggal
            <input
                type="date"
                name="to"
                value="<?= e($validTo ? $to : '') ?>"
            >
        </label>

        <button class="btn btn-dark" type="submit">
            Terapkan
        </button>

        <a class="btn btn-light"
           href="<?= e(app_url('jurnal/index.php')) ?>">
            Reset
        </a>

    </form>

    <?php if ($invalidDate): ?>
        <div class="alert error">
            Format tanggal tidak valid. Silakan pilih tanggal kembali.
        </div>
    <?php endif; ?>

    <?php if ($dateRangeInvalid): ?>
        <div class="alert error">
            Tanggal awal tidak boleh melebihi tanggal akhir.
            Periksa kembali rentang tanggal yang dipilih.
        </div>
    <?php endif; ?>

    <div class="journal-summary">

        <span>
            <?= number_format(count($rows)) ?> catatan ditemukan
        </span>

        <b>
            Total berat:
            <?= number_format($total, 3, ',', '.') ?> kg
        </b>

        <?php if ($isAdmin): ?>
            <a
                class="btn btn-light btn-small"
                href="<?= e(
                    app_url(
                        'jurnal/export.php?' .
                        http_build_query($exportParams)
                    )
                ) ?>"
            >
                ↓ Ekspor CSV
            </a>
        <?php endif; ?>

    </div>

    <div class="table-wrap">

        <table class="journal-table">

            <thead>
                <tr>
                    <th>Kode audit</th>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Petugas</th>
                    <th>Lokasi</th>
                    <th>Total berat</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="7" class="empty">
                            <div class="journal-empty">
                                <strong>Data tidak ditemukan</strong>
                                <p>
                                    Tidak ada catatan yang sesuai
                                    dengan pencarian atau filter.
                                </p>

                                <a
                                    class="text-link"
                                    href="<?= e(app_url('jurnal/index.php')) ?>"
                                >
                                    Tampilkan semua data
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $r): ?>
                    <tr>

                        <td>
                            <b><?= e($r['audit_code']) ?></b>
                        </td>

                        <td>
                            <?= e(date(
                                'd/m/Y',
                                strtotime($r['audit_date'])
                            )) ?>
                        </td>

                        <td>
                            <span class="category-chip">
                                <?= e($r['source_category']) ?>
                            </span>
                        </td>

                        <td><?= e($r['auditor']) ?></td>

                        <td><?= e($r['location']) ?></td>

                        <td>
                            <b>
                                <?= number_format(
                                    (float) $r['total_weight'],
                                    3,
                                    ',',
                                    '.'
                                ) ?> kg
                            </b>
                        </td>

                        <td>
                            <a
                                class="row-link"
                                href="<?= e(
                                    app_url(
                                        'audit/detail.php?id=' . (int) $r['id']
                                    )
                                ) ?>"
                            >
                                Detail →
                            </a>
                        </td>

                    </tr>
                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
