<?php
use App\Core\Auth;
$currentUserId = (int) (Auth::id() ?? 0);
$roleLabels = ['ADMIN' => 'Administrator', 'USER' => 'Pengguna Surat Keluar', 'INCOMING' => 'Petugas Surat Masuk'];
?>

<section class="page-heading">
    <div>
        <span class="eyebrow">ADMINISTRASI</span>
        <h1>Pengguna sistem</h1>
        <p>Kelola akun yang dapat mengakses PENA MAS.</p>
    </div>
    <a class="btn btn-primary" href="/admin/users/create"><?= ui_icon('plus') ?><span>Tambah Pengguna</span></a>
</section>

<section class="panel users-panel">
    <div class="panel-heading">
        <div>
            <span class="eyebrow">DAFTAR AKUN</span>
            <h2><?= count($users) ?> pengguna terdaftar</h2>
            <p>Kelola akun aktif maupun akun yang sedang dibekukan.</p>
        </div>
        <span class="panel-icon-soft"><?= ui_icon('users') ?></span>
    </div>

    <div class="data-table-wrap">
        <table class="data-table users-table">
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Hak Akses</th>
                    <th>Tim Kerja</th>
                    <th>Status</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="6"><div class="empty-state"><span><?= ui_icon('users') ?></span><h3>Belum ada pengguna</h3><p>Tambahkan pengguna baru untuk mulai memberikan akses ke PENA MAS.</p><a class="btn btn-primary" href="/admin/users/create">Tambah Pengguna</a></div></td></tr>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                    <?php
                    $userId = (int) $u['id'];
                    $isSelf = $userId === $currentUserId;
                    $isActive = (int) $u['is_active'] === 1;
                    ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <span class="avatar avatar-sm"><?= e(user_initials(display_user_name(display_user_name($u['name'])))) ?></span>
                                <div class="user-cell-copy">
                                    <strong><?= e(display_user_name($u['name'])) ?><?php if ($isSelf): ?><span class="you-badge">Anda</span><?php endif; ?></strong>
                                    <small><?= e($u['email']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td><span class="role-badge role-<?= e(strtolower($u['role'])) ?>"><?= e($roleLabels[$u['role']] ?? $u['role']) ?></span></td>
                        <td><span class="team-name"><?= e($u['work_team_name'] ?? '-') ?></span></td>
                        <td><span class="badge <?= $isActive ? 'badge-active' : 'badge-cancelled' ?>"><i></i><?= $isActive ? 'Aktif' : 'Dibekukan' ?></span></td>
                        <td><span class="date-cell"><?= e(format_date_id($u['created_at'])) ?></span></td>
                        <td>
                            <div class="user-actions">
                                <a href="/admin/users/<?= $userId ?>/edit" class="btn btn-ghost btn-sm user-action-btn" title="Edit pengguna"><?= ui_icon('settings') ?><span>Edit</span></a>

                                <?php if (!$isSelf): ?>
                                    <a href="/admin/users/<?= $userId ?>/reset-password" class="btn btn-ghost btn-sm user-action-btn" title="Atur ulang password"><?= ui_icon('lock') ?><span>Atur Ulang</span></a>
                                    <form method="post" action="/admin/users/<?= $userId ?>/toggle-status" class="user-action-form" data-confirm="<?= e($isActive ? 'Bekukan akun ini? Pengguna tidak akan dapat login sampai akun diaktifkan kembali.' : 'Aktifkan kembali akun ini?') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm user-action-btn <?= $isActive ? 'btn-freeze' : 'btn-activate' ?>">
                                            <?= $isActive ? ui_icon('ban') : ui_icon('refresh') ?>
                                            <span><?= $isActive ? 'Bekukan' : 'Aktifkan' ?></span>
                                        </button>
                                    </form>

                                    <form method="post" action="/admin/users/<?= $userId ?>/delete" class="user-action-form" data-confirm="Hapus akun ini? Akun akan hilang dari daftar, tetapi riwayat surat dan aktivitas tetap dipertahankan.">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-danger btn-sm user-action-btn"><?= ui_icon('x') ?><span>Hapus</span></button>
                                    </form>
                                <?php else: ?>
                                    <span class="self-account-tag">Akun Anda</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
