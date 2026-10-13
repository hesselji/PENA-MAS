<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Env;
use App\Models\Notification;

$me = Auth::user();
$success = flash('flash_success');
$error = flash('flash_error');
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
$path = current_path();
$appName = Env::get('APP_NAME', 'PENA MAS');
$roleLabels = [
    'ADMIN' => 'Administrator',
    'USER' => 'Pengguna Surat Keluar',
    'INCOMING' => 'Petugas Surat Masuk',
];
$roleLabel = $roleLabels[$me['role'] ?? ''] ?? ($me['role'] ?? '');
$searchAction = '/search';
$searchPlaceholder = Auth::isIncoming()
    ? 'Cari surat masuk...'
    : 'Cari surat, KKA, perihal, tujuan...';

$notificationItems = [];
$notificationUnread = 0;
if ($me) {
    try {
        $notificationItems = Notification::recentForUser((int)$me['id'], 8);
        $notificationUnread = Notification::unreadCount((int)$me['id']);
    } catch (Throwable $notificationError) {
        error_log('[PENA MAS][notifications] ' . $notificationError->getMessage());
    }
}
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
    <link rel="stylesheet" href="/assets/css/style.css?v=20261013-ui2">
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
                <a class="nav-item<?= nav_active('/letters/create', true) ?>" href="/letters/create"><span class="nav-icon"><?= ui_icon('mail-plus') ?></span><span>Buat Nomor Surat</span></a>
                <a class="nav-item<?= ($path === '/letters' || (str_starts_with($path, '/letters/') && $path !== '/letters/create')) ? ' active' : '' ?>" href="/letters"><span class="nav-icon"><?= ui_icon('mail') ?></span><span>Agenda Surat Keluar</span></a>
                <a class="nav-item<?= (str_starts_with($path, '/documents') || str_starts_with($path, '/document-batches')) ? ' active' : '' ?>" href="/documents"><span class="nav-icon"><?= ui_icon('layers') ?></span><span>Dokumen & Cetak</span></a>
            <?php endif; ?>

            <?php if (Auth::canAccessIncoming()): ?>
                <span class="nav-section-label nav-section-spaced">Surat Masuk</span>
                <a class="nav-item<?= nav_active('/incoming/create', true) ?>" href="/incoming/create"><span class="nav-icon"><?= ui_icon('plus') ?></span><span>Tambah Surat Masuk</span></a>
                <a class="nav-item<?= ($path === '/incoming' || (str_starts_with($path, '/incoming/') && $path !== '/incoming/create')) ? ' active' : '' ?>" href="/incoming"><span class="nav-icon"><?= ui_icon('archive') ?></span><span>Agenda Surat Masuk</span></a>
            <?php endif; ?>

            <span class="nav-section-label nav-section-spaced">Laporan</span>
            <a class="nav-item<?= nav_active('/reports') ?>" href="/reports"><span class="nav-icon"><?= ui_icon('chart') ?></span><span>Laporan & Ekspor</span></a>
            <?php if (Auth::isAdmin()): ?>
                <a class="nav-item<?= nav_active('/activity') ?>" href="/activity"><span class="nav-icon"><?= ui_icon('history') ?></span><span>Riwayat Aktivitas</span></a>
            <?php endif; ?>

            <span class="nav-section-label nav-section-spaced">Akun</span>
            <a class="nav-item<?= nav_active('/account/password', true) ?>" href="/account/password"><span class="nav-icon"><?= ui_icon('lock') ?></span><span>Ganti Password</span></a>
            <a class="nav-item<?= nav_active('/help') ?>" href="/help"><span class="nav-icon"><?= ui_icon('info') ?></span><span>Panduan</span></a>

            <?php if (Auth::isAdmin()): ?>
                <span class="nav-section-label nav-section-spaced">Administrasi</span>
                <a class="nav-item<?= nav_active('/admin/users') ?>" href="/admin/users"><span class="nav-icon"><?= ui_icon('users') ?></span><span>Pengguna</span></a>
                <a class="nav-item<?= nav_active('/admin/employees') ?>" href="/admin/employees"><span class="nav-icon"><?= ui_icon('user') ?></span><span>Master Pegawai</span></a>
                <a class="nav-item<?= nav_active('/admin/signers') ?>" href="/admin/signers"><span class="nav-icon"><?= ui_icon('signature') ?></span><span>Penanda Tangan</span></a>
                <a class="nav-item<?= nav_active('/admin/templates') ?>" href="/admin/templates"><span class="nav-icon"><?= ui_icon('template') ?></span><span>Template Dokumen</span></a>
                <a class="nav-item<?= nav_active('/admin/classifications') ?>" href="/admin/classifications"><span class="nav-icon"><?= ui_icon('archive') ?></span><span>Katalog KKA</span></a>
                <a class="nav-item<?= nav_active('/admin/numbering-rules') ?>" href="/admin/numbering-rules"><span class="nav-icon"><?= ui_icon('settings') ?></span><span>Pengaturan Nomor</span></a>
                <a class="nav-item<?= nav_active('/admin/system') ?>" href="/admin/system"><span class="nav-icon"><?= ui_icon('database') ?></span><span>Backup & Pemulihan</span></a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-spacer"></div>
        <div class="sidebar-user">
            <div class="avatar avatar-sm"><?= e(user_initials(display_user_name($me['name']))) ?></div>
            <div class="sidebar-user-copy"><strong><?= e(display_user_name($me['name'])) ?></strong><small><?= e($roleLabel) ?><?= !empty($me['work_team_name']) ? ' - ' . e($me['work_team_name']) : '' ?></small></div>
            <form method="post" action="/logout"><?= csrf_field() ?><button class="icon-btn logout-btn" type="submit" aria-label="Keluar" title="Keluar"><?= ui_icon('logout') ?></button></form>
        </div>
    </aside>

    <div class="app-main">
        <div class="app-ambient" aria-hidden="true"><span class="a1"></span><span class="a2"></span><span class="a3"></span></div>
        <header class="topbar">
            <div class="topbar-left">
                <button class="icon-btn mobile-menu" type="button" data-sidebar-open aria-label="Buka menu"><?= ui_icon('menu') ?></button>
                <form class="global-search" method="get" action="<?= e($searchAction) ?>" role="search"><?= ui_icon('search') ?><input type="search" name="q" placeholder="<?= e($searchPlaceholder) ?>" aria-label="Pencarian"><kbd>⌘K</kbd></form>
            </div>
            <div class="topbar-actions">
                <button class="icon-btn" type="button" data-theme-toggle aria-label="Ganti tema" title="Ganti tema"><span data-theme-icon><?= ui_icon('moon') ?></span></button>

                <div class="notification-menu" data-notification-menu>
                    <button
                        class="icon-btn notification-trigger"
                        type="button"
                        data-notification-trigger
                        aria-label="Notifikasi"
                        title="Notifikasi"
                        aria-expanded="false"
                        aria-haspopup="true"
                    >
                        <?= ui_icon('bell') ?>
                        <span
                            class="notification-unread-dot<?= $notificationUnread > 0 ? ' is-visible' : '' ?>"
                            data-notification-dot
                            aria-hidden="true"
                        ></span>
                    </button>

                    <div
                        class="notification-dropdown"
                        data-notification-dropdown
                        aria-hidden="true"
                    >
                        <div class="notification-dropdown-head">
                            <div>
                                <strong>Notifikasi</strong>
                                <small data-notification-summary>
                                    <?= $notificationUnread > 0 ? e($notificationUnread . ' belum dibaca') : 'Tidak ada notifikasi baru' ?>
                                </small>
                            </div>

                            <button
                                type="button"
                                class="notification-mark-all"
                                data-notification-read-all
                                <?= $notificationUnread > 0 ? '' : 'hidden' ?>
                            >
                                Tandai semua dibaca
                            </button>
                        </div>

                        <div class="notification-list" data-notification-list>
                            <?php if ($notificationItems): ?>
                                <?php foreach ($notificationItems as $notification): ?>
                                    <article class="notification-item<?= empty($notification['read_at']) ? ' is-unread' : '' ?>" data-notification-item="<?= (int)$notification['id'] ?>">
                                        <button
                                            type="button"
                                            class="notification-item-main"
                                            data-notification-open
                                            data-notification-id="<?= (int)$notification['id'] ?>"
                                            data-notification-url="<?= e($notification['url'] ?: '#') ?>"
                                        >
                                            <span class="notification-item-icon"><?= ui_icon('mail') ?></span>
                                            <span class="notification-item-copy">
                                                <strong><?= e($notification['title']) ?></strong>
                                                <span><?= e($notification['message']) ?></span>
                                                <small><?= e(format_date_id($notification['created_at'], true)) ?></small>
                                            </span>
                                            <span class="notification-item-dot" aria-hidden="true"></span>
                                        </button>
                                        <?php if (empty($notification['read_at'])): ?>
                                            <button
                                                type="button"
                                                class="notification-item-read"
                                                data-notification-read
                                                data-notification-id="<?= (int)$notification['id'] ?>"
                                            >
                                                <?= ui_icon('check') ?>
                                                <span>Tandai dibaca</span>
                                            </button>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="notification-empty" data-notification-empty>
                                    <span><?= ui_icon('bell') ?></span>
                                    <strong>Belum ada notifikasi</strong>
                                    <small>Informasi terbaru akan muncul di sini.</small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="notification-dropdown-footer">
                            <button
                                type="button"
                                class="notification-clear"
                                data-notification-clear
                                <?= $notificationItems ? '' : 'hidden' ?>
                            >
                                <?= ui_icon('refresh') ?>
                                <span>Bersihkan notifikasi</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="profile-menu" data-profile-menu>
                    <button
                        class="profile-chip profile-trigger"
                        type="button"
                        data-profile-trigger
                        aria-expanded="false"
                        aria-haspopup="true"
                    >
                        <div class="avatar">
                            <?= e(user_initials(display_user_name($me['name']))) ?>
                        </div>

                        <div class="profile-trigger-copy">
                            <strong><?= e(display_user_name($me['name'])) ?></strong>
                            <small><?= e($roleLabel) ?></small>
                        </div>

                        <span class="profile-chevron" aria-hidden="true">
                            <?= ui_icon('chevron-down') ?>
                        </span>
                    </button>

                    <div
                        class="profile-dropdown"
                        data-profile-dropdown
                        aria-hidden="true"
                    >
                        <div class="profile-dropdown-head">
                            <div class="avatar profile-dropdown-avatar">
                                <?= e(user_initials(display_user_name($me['name']))) ?>
                            </div>
                            <div>
                                <strong><?= e(display_user_name($me['name'])) ?></strong>
                                <small><?= e($roleLabel) ?></small>
                            </div>
                        </div>

                        <div class="profile-dropdown-divider"></div>

                        <form method="post" action="/logout" class="profile-logout-form">
                            <?= csrf_field() ?>
                            <button class="profile-logout-action" type="submit">
                                <span class="profile-logout-icon"><?= ui_icon('logout') ?></span>
                                <span class="profile-logout-copy">
                                    <strong>Keluar</strong>
                                    <small>Akhiri sesi PENA MAS</small>
                                </span>
                                <span class="profile-logout-arrow"><?= ui_icon('arrow-right') ?></span>
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
