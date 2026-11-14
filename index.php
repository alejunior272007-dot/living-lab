<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'Dashboard Monitoring';
$active = 'dashboard';

$currentUser = current_user();
$isAdmin = $currentUser && $currentUser['role'] === 'admin';

// STATISTIK UTAMA
$stats = $pdo->query("
    SELECT
        COUNT(*) AS audit_count,
        COALESCE(SUM(total_weight), 0) AS total_weight,
        SUM(
            CASE
                WHEN audit_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                 AND audit_date < DATE_ADD(
                    LAST_DAY(CURDATE()),
                    INTERVAL 1 DAY
                 )
                THEN 1
                ELSE 0
            END
        ) AS month_count
    FROM audit_logs
")->fetch();

$photoCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM audit_photos"
)->fetchColumn();

// JUMLAH PENGGUNA KHUSUS ADMIN
$userCount = 0;

if ($isAdmin) {
    $userCount = (int) $pdo->query(
        "SELECT COUNT(*) FROM users"
    )->fetchColumn();
}

// DISTRIBUSI BERDASARKAN KATEGORI SUMBER
$categoryTotals = array_fill_keys(source_categories(), 0.0);

$categoryRows = $pdo->query("
    SELECT
        source_category,
        COALESCE(SUM(total_weight), 0) AS weight
    FROM audit_logs
    GROUP BY source_category
")->fetchAll();

foreach ($categoryRows as $row) {
    $category = (string) $row['source_category'];

    if (!array_key_exists($category, $categoryTotals)) {
        $categoryTotals[$category] = 0.0;
    }

    $categoryTotals[$category] = (float) $row['weight'];
}

$grandTotalWeight = (float) $stats['total_weight'];

// ENAM AKTIVITAS TERBARU
$recent = $pdo->query("
    SELECT
        id,
        audit_code,
        audit_date,
        source_category,
        location,
        total_weight
    FROM audit_logs
    ORDER BY audit_date DESC, id DESC
    LIMIT 6
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="welcome-row">
    <div>
        <h2>Selamat datang di pusat monitoring.</h2>
        <p>
            Pantau pencatatan, dokumentasi, dan total limbah organik
            dalam satu tempat.
        </p>
    </div>

    <a class="btn btn-primary"
       href="<?= e(app_url('audit/tambah.php')) ?>">
        ＋ Buat audit baru
    </a>
</section>

<!-- STATISTIK -->
<section class="stats-grid">

    <article class="stat-card dashboard-stat-card">
        <div class="stat-top">
            <span>Total berat limbah</span>
            <span class="stat-icon green">⚖</span>
        </div>

        <strong>
            <?= number_format($grandTotalWeight, 2, ',', '.') ?>
            <small>kg</small>
        </strong>

        <p>Akumulasi seluruh audit</p>
    </article>

    <article class="stat-card dashboard-stat-card">
        <div class="stat-top">
            <span>Total audit</span>
            <span class="stat-icon blue">▤</span>
        </div>

        <strong>
            <?= number_format((int) $stats['audit_count']) ?>
        </strong>

        <p>Seluruh catatan audit tersimpan</p>
    </article>

    <article class="stat-card dashboard-stat-card">
        <div class="stat-top">
            <span>Audit bulan ini</span>
            <span class="stat-icon purple">◷</span>
        </div>

        <strong>
            <?= number_format((int) $stats['month_count']) ?>
        </strong>

        <p>Catatan pada bulan berjalan</p>
    </article>

    <article class="stat-card dashboard-stat-card">
        <div class="stat-top">
            <span>Dokumentasi foto</span>
            <span class="stat-icon amber">▧</span>
        </div>

        <strong>
            <?= number_format($photoCount) ?>
        </strong>

        <p>Total foto bukti audit</p>
    </article>

    <?php if ($isAdmin): ?>
        <article class="stat-card dashboard-stat-card">
            <div class="stat-top">
                <span>Total pengguna</span>
                <span class="stat-icon green">♙</span>
            </div>

            <strong>
                <?= number_format($userCount) ?>
            </strong>

            <p>Seluruh akun terdaftar</p>
        </article>
    <?php endif; ?>

</section>

<!-- DISTRIBUSI DAN AKSI CEPAT -->
<section class="content-grid">

    <article class="panel category-panel">

        <div class="panel-heading">
            <div>
                <h3>Distribusi sumber limbah</h3>
                <p>Total berat berdasarkan kategori sumber</p>
            </div>

            <span class="pill">
                <?= count($categoryTotals) ?> kategori
            </span>
        </div>

        <?php if ($grandTotalWeight <= 0): ?>

            <div class="empty">
                Belum ada data berat limbah untuk ditampilkan.
            </div>

        <?php else: ?>

            <?php foreach ($categoryTotals as $category => $weight): ?>

                <?php
                $percentage = $grandTotalWeight > 0
                    ? ($weight / $grandTotalWeight) * 100
                    : 0;

                $width = min(100, max(0, $percentage));
                ?>

                <div class="category-item">

                    <div class="category-label">
                        <strong><?= e($category) ?></strong>

                        <span>
                            <?= number_format($weight, 2, ',', '.') ?> kg
                            <em><?= number_format($percentage, 1, ',', '.') ?>%</em>
                        </span>
                    </div>

                    <div class="progress-track">
                        <div
                            class="progress-fill"
                            style="width: <?= e((string) $width) ?>%"
                        ></div>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

        <div class="category-note">
            Persentase dihitung dari total berat seluruh audit yang tersimpan.
        </div>

    </article>

    <article class="panel quick-panel">

        <div class="panel-heading">
            <div>
                <h3>Aksi cepat</h3>
                <p>Akses fitur utama aplikasi</p>
            </div>
        </div>

        <a class="quick-link"
           href="<?= e(app_url('audit/tambah.php')) ?>">

            <span class="quick-icon green">＋</span>

            <span>
                <b>Catat audit baru</b>
                <small>Input data 5W1H dan berat limbah</small>
            </span>

            <span class="arrow">→</span>
        </a>

        <a class="quick-link"
           href="<?= e(app_url('jurnal/index.php')) ?>">

            <span class="quick-icon blue">▤</span>

            <span>
                <b>Buka jurnal audit</b>
                <small>Telusuri catatan audit</small>
            </span>

            <span class="arrow">→</span>
        </a>

        <?php if ($isAdmin): ?>

            <a class="quick-link"
               href="<?= e(app_url('pengguna/index.php')) ?>">

                <span class="quick-icon purple">♙</span>

                <span>
                    <b>Manajemen pengguna</b>
                    <small>Kelola akun Admin dan Petugas</small>
                </span>

                <span class="arrow">→</span>
            </a>

        <?php endif; ?>

    </article>

</section>

<!-- AKTIVITAS TERBARU -->
<section class="panel table-panel">

    <div class="panel-heading">
        <div>
            <h3>Aktivitas audit terbaru</h3>
            <p>Enam catatan terakhir yang tersimpan</p>
        </div>

        <a class="text-link"
           href="<?= e(app_url('jurnal/index.php')) ?>">
            Lihat semua →
        </a>
    </div>

    <div class="table-wrap">

        <table>
            <thead>
                <tr>
                    <th>Kode audit</th>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Lokasi</th>
                    <th>Total berat</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!$recent): ?>

                    <tr>
                        <td colspan="6" class="empty">
                            Belum ada data audit.
                            Mulai dengan membuat audit baru.
                        </td>
                    </tr>

                <?php endif; ?>

                <?php foreach ($recent as $r): ?>

                    <tr>
                        <td>
                            <b><?= e($r['audit_code']) ?></b>
                        </td>

                        <td>
                            <?= date(
                                'd/m/Y',
                                strtotime($r['audit_date'])
                            ) ?>
                        </td>

                        <td>
                            <span class="category-chip">
                                <?= e($r['source_category']) ?>
                            </span>
                        </td>

                        <td><?= e($r['location']) ?></td>

                        <td>
                            <b>
                                <?= number_format(
                                    (float) $r['total_weight'],
                                    2,
                                    ',',
                                    '.'
                                ) ?> kg
                            </b>
                        </td>

                        <td>
                            <a class="row-link"
                               href="<?= e(app_url(
                                   'audit/detail.php?id=' . (int) $r['id']
                               )) ?>">
                                Detail →
                            </a>
                        </td>
                    </tr>

                <?php endforeach; ?>

            </tbody>
        </table>

    </div>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>