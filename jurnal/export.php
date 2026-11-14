<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

// HANYA ADMIN YANG BOLEH EKSPOR
require_role('admin');

// AMBIL FILTER
$search = trim((string) ($_GET['search'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$from = trim((string) ($_GET['from'] ?? ''));
$to = trim((string) ($_GET['to'] ?? ''));

$search = mb_substr($search, 0, 100);

// VALIDASI FILTER
if (!in_array($category, source_categories(), true)) {
    $category = '';
}

$validFrom = $from !== '' && valid_date($from);
$validTo = $to !== '' && valid_date($to);

// CEK RENTANG TANGGAL
if ($validFrom && $validTo && $from > $to) {
    flash('error', 'Tanggal awal tidak boleh melebihi tanggal akhir.');
    redirect('jurnal/index.php');
}

// SUSUN QUERY
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
if ($validFrom) {
    $where[] = 'audit_date >= ?';
    $params[] = $from;
}

if ($validTo) {
    $where[] = 'audit_date <= ?';
    $params[] = $to;
}

// AMBIL DATA
$sql = "
    SELECT
        audit_code,
        audit_date,
        source_category,
        auditor,
        location,
        what_description,
        why_description,
        how_description,
        total_weight,
        notes
    FROM audit_logs
";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY audit_date DESC, id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// SIAPKAN FILE CSV
$filename = 'jurnal-limbah-' . date('Ymd-His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header(
    'Content-Disposition: attachment; filename="' . $filename . '"'
);
header('Cache-Control: no-store, no-cache, must-revalidate');

$out = fopen('php://output', 'w');

// BOM AGAR KARAKTER INDONESIA TERBACA DI EXCEL
fwrite($out, "\xEF\xBB\xBF");

// HEADER CSV
fputcsv($out, [
    'Kode Audit',
    'Tanggal',
    'Kategori Sumber',
    'Petugas',
    'Lokasi',
    'WHAT',
    'WHY',
    'HOW',
    'Total Berat (kg)',
    'Catatan'
], ',', '"', '');

// CEGAH FORMULA INJECTION PADA TEKS CSV
$safeText = static function ($value): string {
    $value = (string) ($value ?? '');

    if (preg_match('/^[\s]*[=+\-@]/u', $value)) {
        return "'" . $value;
    }

    return $value;
};

// TULIS DATA
while ($row = $stmt->fetch()) {
    fputcsv($out, [
        $safeText($row['audit_code']),
        $safeText($row['audit_date']),
        $safeText($row['source_category']),
        $safeText($row['auditor']),
        $safeText($row['location']),
        $safeText($row['what_description']),
        $safeText($row['why_description']),
        $safeText($row['how_description']),
        (float) $row['total_weight'],
        $safeText($row['notes'])
    ], ',', '"', '');
}

fclose($out);
exit;
