<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Env;

$me = Auth::user();
$success = flash('flash_success');
$error = flash('flash_error');
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
$path = current_path();
$appName = Env::get('APP_NAME', 'PENA MAS');
$searchIncoming = Auth::isIncoming() || str_starts_with($path, '/incoming');
$searchAction = $searchIncoming ? '/incoming' : '/letters';
$searchPlaceholder = $searchIncoming ? 'Cari surat masuk, asal, perihal...' : 'Cari nomor, perihal, tujuan...';
?><!doctype html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <meta name="color-scheme" content="dark light">
    <meta name="theme-color" content="#0b1118">
    <meta name="description" content="PENA MAS - Penomoran Agenda dan Manajemen Arsip Surat">
    <title><?= e($appName) ?></title>
    <link rel="icon" href="/assets/images/penamas-icon.png" type="image/png">
    <link rel="stylesheet" href="/assets/css/style.css?v=20260910-batch-v06">
</head>
<body class="<?= $me ? 'app-body' : 'auth-body' ?>">
<?php if ($me): ?>
<div class="app-layout">
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <aside class="sidebar" data-sidebar>
        <div class="brand-panel">
            <a class="brand" href="<?= e(Auth::homePath()) ?>" aria-label="PENA MAS">
                <span class="brand-logo-wrap"><img src="/assets/images/penamas-icon.png" alt="Logo PENA MAS"></span>
                <span class="brand-copy"><strong>PENA MAS</strong><small>Penomoran Agenda dan<br>Manajemen Arsip Surat</small></span>
            </a>
            <button class="sidebar-close icon-btn" type="button" data-sidebar-close aria-label="Tutup menu"><?= ui_icon('x') ?></button>
        </div>

        <nav class="sidebar-nav" aria-label="Navigasi utama">
            <?php if (!Auth::isIncoming()): ?>
                <span class="nav-section-label">Menu Utama</span>
                <a class="nav-item<?= nav_active('/dashboard', true) ?>" href="/dashboard"><span class="nav-icon"><?= ui_icon('dashboard') ?></span><span>Dashboard</span></a>

                <span class="nav-section-label nav-section-spaced">Surat Keluar</span>
                <a class="nav-item<?= nav_active('/letters/create', true) ?>" href="/letters/create"><span class="nav-icon"><?= ui_icon('mail-plus') ?></span><span>Generate Nomor</span></a>
                <a class="nav-item<?= ($path === '/letters' || (str_starts_with($path, '/letters/') && $path !== '/letters/create')) ? ' active' : '' ?>" href="/letters"><span class="nav-icon"><?= ui_icon('mail') ?></span><span>Agenda Surat Keluar</span></a>
            <?php endif; ?>

            <?php if (Auth::canAccessIncoming()): ?>
                <span class="nav-section-label nav-section-spaced">Surat Masuk</span>
                <a class="nav-item<?= nav_active('/incoming/create', true) ?>" href="/incoming/create"><span class="nav-icon"><?= ui_icon('plus') ?></span><span>Tambah Surat Masuk</span></a>
                <a class="nav-item<?= ($path === '/incoming' || (str_starts_with($path, '/incoming/') && $path !== '/incoming/create')) ? ' active' : '' ?>" href="/incoming"><span class="nav-icon"><?= ui_icon('archive') ?></span><span>Agenda Surat Masuk</span></a>
            <?php endif; ?>

            <span class="nav-section-label nav-section-spaced">Akun</span>
            <a class="nav-item<?= nav_active('/account/password', true) ?>" href="/account/password"><span class="nav-icon"><?= ui_icon('lock') ?></span><span>Ganti Password</span></a>

            <?php if (Auth::isAdmin()): ?>
                <span class="nav-section-label nav-section-spaced">Administrasi</span>
                <a class="nav-item<?= nav_active('/admin/users') ?>" href="/admin/users"><span class="nav-icon"><?= ui_icon('users') ?></span><span>Pengguna</span></a>
                <a class="nav-item<?= nav_active('/admin/classifications') ?>" href="/admin/classifications"><span class="nav-icon"><?= ui_icon('archive') ?></span><span>Katalog KKA</span></a>
                <a class="nav-item<?= nav_active('/admin/numbering-rules') ?>" href="/admin/numbering-rules"><span class="nav-icon"><?= ui_icon('settings') ?></span><span>Aturan Nomor</span></a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-spacer"></div>
        <div class="sidebar-help">
            <span class="help-icon"><?= ui_icon('info') ?></span>
            <div><strong>Butuh bantuan?</strong><small>Hubungi administrator PENA MAS.</small></div>
            <a href="mailto:admin@demo.local" class="btn btn-primary btn-sm">Hubungi Admin</a>
        </div>

        <div class="sidebar-user">
            <div class="avatar avatar-sm"><?= e(user_initials($me['name'])) ?></div>
            <div class="sidebar-user-copy"><strong><?= e($me['name']) ?></strong><small><?= e($me['role']) ?><?= !empty($me['work_team_name']) ? ' • ' . e($me['work_team_name']) : '' ?></small></div>
            <form method="post" action="/logout"><?= csrf_field() ?><button class="icon-btn logout-btn" type="submit" aria-label="Keluar" title="Keluar"><?= ui_icon('logout') ?></button></form>
        </div>
    </aside>

    <div class="app-main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="icon-btn mobile-menu" type="button" data-sidebar-open aria-label="Buka menu"><?= ui_icon('menu') ?></button>
                <form class="global-search" method="get" action="<?= e($searchAction) ?>" role="search"><?= ui_icon('search') ?><input type="search" name="q" placeholder="<?= e($searchPlaceholder) ?>" aria-label="Pencarian"><kbd>⌘K</kbd></form>
            </div>
            <div class="topbar-actions">
                <button class="icon-btn" type="button" data-theme-toggle aria-label="Ganti tema" title="Ganti tema"><span data-theme-icon><?= ui_icon('moon') ?></span></button>
                <div class="notification-wrap">
    <button
        class="icon-btn notification-btn"
        type="button"
        aria-label="Notifikasi"
        title="Notifikasi"
        id="notificationButton"
    >
        <?= ui_icon('bell') ?>
        <i id="notificationDot"></i>
    </button>

    <div
        class="notification-panel"
        id="notificationPanel"
        hidden
    >
        <div class="notification-header">
            <div>
                <strong>Notifikasi</strong>
                <small id="notificationSummary">
                    Memuat notifikasi...
                </small>
            </div>
        </div>

        <div
            class="notification-list"
            id="notificationList"
        ></div>
    </div>
</div>
                <div class="profile-chip" id="profileChip">
    <div class="avatar"><?= e(user_initials($me['name'])) ?></div>
    <div><strong><?= e($me['name']) ?></strong><small><?= e($me['role']) ?></small></div>
    <!-- Dropdown Logout -->
    <div class="profile-dropdown" id="profileDropdown">
        <form method="post" action="/logout">
            <?= csrf_field() ?>
            <button type="submit" class="profile-dropdown-item">
                <?= ui_icon('logout') ?> Logout
            </button>
        </form>
    </div>
</div>
            </div>
        </header>

        <main class="content-shell">
            <?php if ($success): ?><div class="toast toast-success" data-toast><?= ui_icon('check') ?><div><strong>Berhasil</strong><span><?= e($success) ?></span></div><button type="button" data-toast-close aria-label="Tutup"><?= ui_icon('x') ?></button></div><?php endif; ?>
            <?php if ($error): ?><div class="toast toast-error" data-toast><?= ui_icon('ban') ?><div><strong>Terjadi kendala</strong><span><?= e($error) ?></span></div><button type="button" data-toast-close aria-label="Tutup"><?= ui_icon('x') ?></button></div><?php endif; ?>
<?php else: ?>
    <main class="auth-shell">
        <?php if ($error): ?><div class="toast toast-error auth-toast" data-toast><?= ui_icon('ban') ?><div><strong>Login gagal</strong><span><?= e($error) ?></span></div><button type="button" data-toast-close aria-label="Tutup"><?= ui_icon('x') ?></button></div><?php endif; ?>
<?php endif; ?>
