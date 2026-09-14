<?php
$activeGroups = array_values(array_filter($groups, fn($g) => (int)$g['is_active'] === 1));
$totalItems = array_sum(array_map(fn($g)=>(int)$g['item_count'],$activeGroups));
?>
<section class="page-heading">
    <div>
        <span class="eyebrow">KLASIFIKASI ARSIP</span>
        <h1>Katalog Kode Klasifikasi</h1>
        <p>Temukan kode klasifikasi arsip yang digunakan dalam pencatatan surat.</p>
    </div>
    <a class="btn btn-secondary" href="/admin/numbering-rules"><?= ui_icon('settings') ?><span>Pengaturan Nomor</span></a>
</section>

<div class="stat-grid compact-stat-grid">
    <article class="stat-card"><span class="stat-icon blue"><?= ui_icon('archive') ?></span><div><small>Kelompok Klasifikasi</small><strong><?= count($activeGroups) ?></strong><em>Kelompok yang dapat digunakan</em></div></article>
    <article class="stat-card"><span class="stat-icon green"><?= ui_icon('file') ?></span><div><small>Kode Klasifikasi</small><strong><?= $totalItems ?></strong><em>Kode yang tersedia</em></div></article>
    <article class="stat-card"><span class="stat-icon orange"><?= ui_icon('search') ?></span><div><small>Pencarian</small><strong>Mudah</strong><em>Cari berdasarkan kode atau nama</em></div></article>
</div>

<section class="panel section-gap">
    <form class="filter-form master-filter" method="get" action="/admin/classifications">
        <div class="filter-search"><?= ui_icon('search') ?><input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Cari kode atau nama klasifikasi..."></div>
        <select name="archive_type">
            <option value="">Semua jenis arsip</option>
            <option value="SUBSTANTIF" <?= $filters['archive_type']==='SUBSTANTIF'?'selected':'' ?>>Substantif</option>
            <option value="FASILITATIF" <?= $filters['archive_type']==='FASILITATIF'?'selected':'' ?>>Fasilitatif</option>
        </select>
        <select name="group">
            <option value="">Semua kelompok KKA</option>
            <?php foreach($activeGroups as $g): ?>
                <option value="<?= e($g['code']) ?>" <?= $filters['group']===$g['code']?'selected':'' ?>><?= e($g['code']) ?> — <?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit"><?= ui_icon('filter') ?><span>Tampilkan</span></button>
        <a class="btn btn-secondary" href="/admin/classifications">Bersihkan</a>
    </form>
</section>

<section class="panel users-panel section-gap">
    <div class="panel-heading">
        <div>
            <span class="eyebrow">HASIL PENCARIAN</span>
            <h2><?= count($rows) ?> klasifikasi ditampilkan</h2>
            <p>Pilih dan gunakan kode sesuai isi serta tujuan arsip surat.</p>
        </div>
        <span class="panel-icon-soft"><?= ui_icon('archive') ?></span>
    </div>

    <div class="data-table-wrap">
        <table class="data-table classification-table">
            <thead><tr><th>Jenis Arsip</th><th>Kelompok</th><th>Kode</th><th>Nama Klasifikasi</th><th>Keterangan</th></tr></thead>
            <tbody>
            <?php foreach($rows as $row): ?>
                <tr>
                    <td><span class="role-badge role-<?= strtolower($row['archive_type']) ?>"><?= e(ucfirst(strtolower($row['archive_type']))) ?></span></td>
                    <td><strong><?= e($row['group_code']) ?></strong><small class="table-subtext"><?= e($row['group_name']) ?></small></td>
                    <td><strong><?= e($row['code'] ?? '-') ?></strong></td>
                    <td><?= e($row['name'] ?? '-') ?></td>
                    <td><small><?= nl2br(e($row['description'] ?? '')) ?></small></td>
                </tr>
            <?php endforeach; ?>
            <?php if(!$rows): ?><tr><td colspan="5"><div class="empty-state compact-empty"><strong>Data tidak ditemukan</strong><span>Coba gunakan kata kunci atau pilihan lain.</span></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
