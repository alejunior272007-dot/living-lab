<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$active = $active ?? '';
$flashMessage = get_flash();
$currentUser = current_user();
$isAdmin = $currentUser && $currentUser['role'] === 'admin';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
</head>
<body>
<div class="app-shell">
<aside class="sidebar">
  <a class="brand" href="<?= e(app_url('index.php')) ?>">
    <span class="brand-mark">LL</span>
    <span><strong>Living Lab</strong><small>SUPPORT SYSTEM</small></span>
  </a>

  <div class="nav-label">MENU UTAMA</div>
  <nav>
    <a class="<?= $active === 'dashboard' ? 'active' : '' ?>" href="<?= e(app_url('index.php')) ?>"><span>▦</span> Dashboard</a>
    <a class="<?= $active === 'new' ? 'active' : '' ?>" href="<?= e(app_url('audit/tambah.php')) ?>"><span>＋</span> Input Audit</a>
    <a class="<?= $active === 'journal' ? 'active' : '' ?>" href="<?= e(app_url('jurnal/index.php')) ?>"><span>▤</span> Jurnal Audit</a>

    <?php if ($isAdmin): ?>
    <a class="<?= $active === 'users' ? 'active' : '' ?>" href="<?= e(app_url('pengguna/index.php')) ?>"><span>♙</span> Manajemen Pengguna</a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-bottom">
    <span class="status-dot"></span> Sistem dokumentasi limbah<br>
    <small>Versi 1.0 · <?= date('Y') ?></small>
  </div>
</aside>

<main class="main-content">
<header class="topbar">
  <div>
    <div class="eyebrow">LIVING LAB SUPPORT</div>
    <h1><?= e($pageTitle) ?></h1>
  </div>

  <div class="topbar-right">
    <span class="today"><?= date('d M Y') ?></span>

    <?php if ($currentUser): ?>
      <div class="user-info">
        <strong><?= e($currentUser['full_name']) ?></strong>
        <small><?= $isAdmin ? 'Admin' : 'Petugas' ?></small>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(app_url('logout.php')) ?>">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <button type="submit" class="logout-button">Keluar</button>
    </form>
  </div>
</header>

<?php if ($flashMessage): ?>
<div class="alert <?= e($flashMessage['type']) ?>">
  <?= e($flashMessage['message']) ?>
</div>
<?php endif; ?>
