<section class="page-heading">
    <div>
        <span class="eyebrow">SURAT MASUK</span>
        <h1>Agenda surat masuk</h1>
        <p>Catat, telusuri, dan pantau tindak lanjut surat yang diterima.</p>
    </div>
    <a class="btn btn-primary" href="/incoming/create"><?= ui_icon('plus') ?><span>Tambah Surat Masuk</span></a>
</section>

<section class="panel agenda-panel">
    <form method="get" class="agenda-toolbar incoming-toolbar-advanced">
        <div class="toolbar-search"><?= ui_icon('search') ?><input name="q" value="<?= e($filters['q']) ?>" placeholder="Cari nomor, asal, perihal, kepada..."></div>
        <label class="incoming-status-filter"><span>Status</span><select name="workflow_status"><option value="">Semua status</option><option value="MASUK" <?= ($filters['workflow_status']??'')==='MASUK'?'selected':'' ?>>Masuk</option><option value="DISPOSISI" <?= ($filters['workflow_status']??'')==='DISPOSISI'?'selected':'' ?>>Disposisi</option><option value="SELESAI" <?= ($filters['workflow_status']??'')==='SELESAI'?'selected':'' ?>>Selesai</option></select></label>
        <label class="incoming-year-filter"><span>Tahun</span><input name="year" value="<?= e($filters['year']) ?>" inputmode="numeric" placeholder="2026"></label>
        <div class="incoming-filter-actions"><button type="submit" class="btn btn-primary incoming-search-btn">Cari</button><a href="/incoming" class="incoming-reset-btn" title="Reset pencarian" aria-label="Reset pencarian"><?= ui_icon('refresh') ?></a></div>
    </form>

    <div class="agenda-summary-row"><div><span class="summary-dot green"></span><b><?= count($letters) ?></b><span>surat masuk ditampilkan</span></div></div>

    <div class="data-table-wrap">
        <table class="data-table incoming-table">
            <thead><tr><th>Nomor Surat</th><th>Tanggal</th><th>Asal</th><th>Perihal & Kepada</th><th>Tindak Lanjut</th><th>Penanggung Jawab</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
            <?php if(!$letters): ?>
                <tr><td colspan="7"><div class="empty-state"><span><?= ui_icon('mail') ?></span><h3>Belum ada surat masuk</h3><p>Tambahkan surat masuk pertama untuk mulai mengisi agenda.</p><a class="btn btn-primary" href="/incoming/create">Tambah Surat Masuk</a></div></td></tr>
            <?php else: foreach($letters as $r): ?>
                <tr>
                    <td><a class="number-link" href="/incoming/<?= (int)$r['id'] ?>"><?= e($r['letter_number']) ?></a><?php if (is_new_record($r['created_at'] ?? null)): ?><span class="new-letter-badge">Surat baru</span><?php endif; ?><small>Diterima <?= e(format_date_id($r['received_date'])) ?></small></td>
                    <td><?= e(format_date_id($r['letter_date'])) ?><small>Dicatat <?= e(format_date_id($r['recorded_at'], true)) ?></small></td>
                    <td><span class="team-chip"><?= e($r['origin']) ?></span></td>
                    <td><strong class="cell-title"><?= e($r['subject']) ?></strong><small><?= e($r['recipient']) ?></small></td>
                    <td><span class="workflow-badge workflow-<?= strtolower($r['workflow_status']) ?>"><?= e(incoming_status_label($r['workflow_status'])) ?></span><?php if($r['follow_up_note']):?><small><?= e($r['follow_up_note']) ?></small><?php endif;?></td>
                    <td><strong><?= e($r['disposition_users'] ? display_user_name($r['disposition_users']) : ($r['assigned_user_name'] ? display_user_name($r['assigned_user_name']) : '-')) ?></strong><small><?= e($r['disposition_teams'] ?: ($r['assigned_team_name'] ?: 'Belum ditentukan')) ?></small></td>
                    <td class="text-right"><a class="icon-btn row-action" href="/incoming/<?= (int)$r['id'] ?>" title="Lihat detail"><?= ui_icon('eye') ?></a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
