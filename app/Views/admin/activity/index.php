<?php
$formatActivity = static function (string $action) use ($activityTypes): string {
    return $activityTypes[$action] ?? 'Melakukan aktivitas pada sistem';
};
?>

<section class="page-heading">
    <div>
        <span class="eyebrow">ADMINISTRASI</span>
        <h1>Riwayat Aktivitas</h1>
        <p>Catatan aktivitas pengguna yang dibuat lebih mudah dipahami.</p>
    </div>
</section>

<section class="panel section-gap">
    <form class="filter-form master-filter activity-filter" method="get" action="/admin/activity">
        <div>
            <label class="sr-only" for="date_from">Dari tanggal</label>
            <input type="date" id="date_from" name="date_from" value="<?= e($filters['date_from']) ?>" aria-label="Dari tanggal">
        </div>
        <div>
            <label class="sr-only" for="date_to">Sampai tanggal</label>
            <input type="date" id="date_to" name="date_to" value="<?= e($filters['date_to']) ?>" aria-label="Sampai tanggal">
        </div>
        <select name="user_id" aria-label="Pengguna">
            <option value="0">Semua pengguna</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= (int) $user['id'] ?>"<?= $filters['user_id'] === (int) $user['id'] ? ' selected' : '' ?>><?= e($user['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="action" aria-label="Aktivitas">
            <option value="">Semua aktivitas</option>
            <?php foreach ($activityTypes as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $filters['action'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit"><?= ui_icon('filter') ?><span>Filter</span></button>
        <a class="btn btn-secondary" href="/admin/activity">Reset</a>
    </form>
</section>

<section class="panel users-panel section-gap">
    <div class="panel-heading">
        <div>
            <span class="eyebrow">CATATAN AKTIVITAS</span>
            <h2><?= count($activities) ?> aktivitas</h2>
            <p>Menampilkan aktivitas terbaru sesuai filter yang dipilih.</p>
        </div>
        <span class="panel-icon-soft"><?= ui_icon('shield') ?></span>
    </div>

    <div class="data-table-wrap">
        <table class="data-table activity-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Aktivitas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$activities): ?>
                    <tr>
                        <td colspan="3">
                            <div class="empty-state">Belum ada aktivitas yang sesuai dengan filter.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td><?= e(date('d/m/Y H:i', strtotime((string) $activity['created_at']))) ?></td>
                            <td><?= e($activity['user_name'] ?: 'Sistem') ?></td>
                            <td><?= e($formatActivity((string) $activity['action'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
