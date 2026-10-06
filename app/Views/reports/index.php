<?php
use App\Core\Auth;

$query = array_filter([
    'source' => $source,
    'q' => $filters['q'] ?? '',
    'date_from' => $filters['date_from'] ?? '',
    'date_to' => $filters['date_to'] ?? '',
    'team' => $filters['team'] ?? '',
    'letter_type' => $filters['letter_type'] ?? '',
    'sensitivity' => $filters['sensitivity'] ?? '',
    'status' => $filters['status'] ?? '',
], static fn($value) => $value !== '' && $value !== null);

$queryString = http_build_query($query);
$isOutgoing = $source === 'outgoing';
$isAdmin = Auth::isAdmin();
?>

<section class="page-heading report-heading">
    <div>
        <span class="eyebrow">LAPORAN</span>
        <h1>Laporan & ekspor</h1>
        <p>Susun laporan berdasarkan periode dan kebutuhan, lalu unduh untuk diolah atau dicetak.</p>
    </div>

    <div class="report-heading-actions">
        <a class="btn btn-secondary" href="/reports/print?<?= e($queryString) ?>" target="_blank" rel="noopener">
            <?= ui_icon('printer') ?>
            <span>Cetak / Simpan PDF</span>
        </a>
        <a class="btn btn-primary" href="/reports/export?<?= e($queryString) ?>">
            <?= ui_icon('download') ?>
            <span>Unduh CSV</span>
        </a>
    </div>
</section>

<?php if ($canOutgoing && $canIncoming): ?>
<section class="report-source-switch" aria-label="Jenis laporan">
    <a class="report-source-option<?= $isOutgoing ? ' active' : '' ?>" href="/reports?source=outgoing">
        <span class="report-source-icon blue"><?= ui_icon('mail') ?></span>
        <span><strong>Surat Keluar</strong><small>Laporan agenda nomor surat keluar</small></span>
    </a>
    <a class="report-source-option<?= !$isOutgoing ? ' active' : '' ?>" href="/reports?source=incoming">
        <span class="report-source-icon green"><?= ui_icon('archive') ?></span>
        <span><strong>Surat Masuk</strong><small>Laporan surat yang diterima</small></span>
    </a>
</section>
<?php endif; ?>

<section class="panel report-filter-panel">
    <div class="report-panel-title">
        <div>
            <span class="eyebrow">PENYARINGAN</span>
            <h2>Atur isi laporan</h2>
            <p>Periode awal ditampilkan untuk bulan berjalan. Ubah pilihan sesuai laporan yang diperlukan.</p>
        </div>
        <span class="report-period-chip"><?= ui_icon('calendar') ?> <?= e(format_date_id($filters['date_from'])) ?> - <?= e(format_date_id($filters['date_to'])) ?></span>
    </div>

    <form method="get" action="/reports" class="report-filter-form">
        <input type="hidden" name="source" value="<?= e($source) ?>">

        <label class="report-field report-field-search">
            <span>Pencarian</span>
            <div class="report-search-shell"><?= ui_icon('search') ?><input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Cari nomor, perihal, tujuan, asal..."></div>
        </label>

        <label class="report-field">
            <span>Dari tanggal</span>
            <input type="date" name="date_from" value="<?= e($filters['date_from']) ?>">
        </label>

        <label class="report-field">
            <span>Sampai tanggal</span>
            <input type="date" name="date_to" value="<?= e($filters['date_to']) ?>">
        </label>

        <?php if ($isOutgoing): ?>
            <label class="report-field">
                <span>Tim kerja</span>
                <select name="team">
                    <option value="">Semua tim kerja</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= (int)$team['id'] ?>" <?= (string)$filters['team'] === (string)$team['id'] ? 'selected' : '' ?>><?= e($team['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="report-field">
                <span>Jenis surat</span>
                <select name="letter_type">
                    <option value="">Semua jenis surat</option>
                    <?php foreach ($letterTypes as $type): ?>
                        <option value="<?= (int)$type['id'] ?>" <?= (string)$filters['letter_type'] === (string)$type['id'] ? 'selected' : '' ?>><?= e($type['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <?php if ($isAdmin): ?>
                <label class="report-field">
                    <span>Sifat surat</span>
                    <select name="sensitivity">
                        <option value="">Semua sifat</option>
                        <?php foreach ($sensitivities as $item): ?>
                            <option value="<?= e($item['code']) ?>" <?= $filters['sensitivity'] === $item['code'] ? 'selected' : '' ?>><?= e($item['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>

            <label class="report-field">
                <span>Status</span>
                <select name="status">
                    <option value="">Semua status</option>
                    <option value="ACTIVE" <?= $filters['status'] === 'ACTIVE' ? 'selected' : '' ?>>Aktif</option>
                    <option value="CANCELLED" <?= $filters['status'] === 'CANCELLED' ? 'selected' : '' ?>>Dibatalkan</option>
                </select>
            </label>
        <?php endif; ?>

        <div class="report-filter-actions">
            <a class="btn btn-ghost" href="/reports?source=<?= e($source) ?>"><?= ui_icon('refresh') ?><span>Reset</span></a>
            <button class="btn btn-primary" type="submit"><?= ui_icon('filter') ?><span>Tampilkan Laporan</span></button>
        </div>
    </form>
</section>

<section class="report-summary-grid">
    <?php if ($isOutgoing): ?>
        <article class="report-summary-card tone-blue">
            <span class="report-summary-icon"><?= ui_icon('file') ?></span>
            <div><small>Total Surat</small><strong><?= (int)$summary['total'] ?></strong><p>Sesuai penyaringan laporan</p></div>
        </article>
        <article class="report-summary-card tone-green">
            <span class="report-summary-icon"><?= ui_icon('check') ?></span>
            <div><small>Nomor Aktif</small><strong><?= (int)$summary['active'] ?></strong><p>Nomor yang masih aktif</p></div>
        </article>
        <article class="report-summary-card tone-orange">
            <span class="report-summary-icon"><?= ui_icon('ban') ?></span>
            <div><small>Dibatalkan</small><strong><?= (int)$summary['cancelled'] ?></strong><p>Tetap tercatat dalam agenda</p></div>
        </article>
        <?php if ($isAdmin): ?>
        <article class="report-summary-card tone-purple">
            <span class="report-summary-icon"><?= ui_icon('lock') ?></span>
            <div><small>Surat Terlindungi</small><strong><?= (int)$summary['confidential'] ?></strong><p>Rahasia dan sangat rahasia</p></div>
        </article>
        <?php endif; ?>
    <?php else: ?>
        <article class="report-summary-card tone-blue">
            <span class="report-summary-icon"><?= ui_icon('archive') ?></span>
            <div><small>Total Surat Masuk</small><strong><?= (int)$summary['total'] ?></strong><p>Sesuai penyaringan laporan</p></div>
        </article>
        <article class="report-summary-card tone-green">
            <span class="report-summary-icon"><?= ui_icon('briefcase') ?></span>
            <div><small>Asal Surat</small><strong><?= (int)$summary['origins'] ?></strong><p>Jumlah asal yang berbeda</p></div>
        </article>
        <article class="report-summary-card tone-orange">
            <span class="report-summary-icon"><?= ui_icon('users') ?></span>
            <div><small>Petugas Pencatat</small><strong><?= (int)$summary['recorders'] ?></strong><p>Petugas yang mencatat surat</p></div>
        </article>
        <article class="report-summary-card tone-purple">
            <span class="report-summary-icon"><?= ui_icon('calendar') ?></span>
            <div><small>Terakhir Diterima</small><strong class="report-summary-date"><?= e($summary['latest_received'] ? format_date_id($summary['latest_received']) : '-') ?></strong><p>Tanggal penerimaan terbaru</p></div>
        </article>
    <?php endif; ?>
</section>

<section class="panel report-result-panel">
    <div class="report-result-head">
        <div>
            <span class="eyebrow">HASIL LAPORAN</span>
            <h2><?= $isOutgoing ? 'Surat keluar' : 'Surat masuk' ?></h2>
            <p><?= count($rows) ?> baris ditampilkan<?= count($rows) >= $previewLimit ? ' pada pratinjau. File ekspor tetap memuat seluruh hasil.' : '.' ?></p>
        </div>
        <div class="report-result-actions">
            <a class="btn btn-secondary btn-sm" href="/reports/print?<?= e($queryString) ?>" target="_blank" rel="noopener"><?= ui_icon('printer') ?><span>Cetak</span></a>
            <a class="btn btn-primary btn-sm" href="/reports/export?<?= e($queryString) ?>"><?= ui_icon('download') ?><span>CSV</span></a>
        </div>
    </div>

    <?php if (!$rows): ?>
        <div class="empty-state report-empty">
            <span><?= ui_icon('chart') ?></span>
            <h3>Belum ada data pada periode ini</h3>
            <p>Ubah tanggal atau pilihan penyaringan untuk menampilkan laporan.</p>
        </div>
    <?php else: ?>
        <div class="data-table-wrap report-table-wrap">
            <?php if ($isOutgoing): ?>
                <table class="data-table report-table">
                    <thead><tr><th>No</th><th>Nomor Surat</th><th>Tanggal</th><th>Jenis / Tim</th><th>Perihal & Tujuan</th><th>KKA</th><th>Sifat</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $i => $row): $secret = in_array($row['sensitivity'], ['RAHASIA','SANGAT_RAHASIA'], true); ?>
                        <tr>
                            <td data-label="No"><?= $i + 1 ?></td>
                            <td data-label="Nomor Surat"><a class="number-link" href="/letters/<?= (int)$row['id'] ?>"><?= e($row['letter_number']) ?></a><small>Agenda #<?= e($row['agenda_number']) ?></small></td>
                            <td data-label="Tanggal"><?= e(format_date_id($row['letter_date'])) ?></td>
                            <td data-label="Jenis / Tim"><strong class="cell-title"><?= e($row['letter_type_name']) ?></strong><small><?= e($row['work_team_name']) ?></small></td>
                            <td data-label="Perihal & Tujuan">
                                <?php if ($secret): ?>
                                    <strong class="cell-title confidential-mask"><?= ui_icon('lock') ?> Detail terlindungi</strong><small>Isi tidak ditampilkan pada laporan.</small>
                                <?php else: ?>
                                    <strong class="cell-title"><?= e($row['subject']) ?></strong><small><?= e($row['recipient']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td data-label="KKA"><span class="team-chip"><?= e($row['classification_code']) ?></span></td>
                            <td data-label="Sifat"><span class="sensitivity-badge sens-<?= strtolower(e($row['sensitivity'])) ?>"><?= e(sensitivity_label($row['sensitivity'])) ?></span></td>
                            <td data-label="Status"><span class="badge badge-<?= strtolower(e($row['status'])) ?>"><i></i><?= e(status_label($row['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <table class="data-table report-table">
                    <thead><tr><th>No</th><th>Nomor Surat</th><th>Tanggal Surat</th><th>Diterima</th><th>Asal</th><th>Perihal & Kepada</th><th>Dicatat Oleh</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $i => $row): ?>
                        <tr>
                            <td data-label="No"><?= $i + 1 ?></td>
                            <td data-label="Nomor Surat"><a class="number-link" href="/incoming/<?= (int)$row['id'] ?>"><?= e($row['letter_number']) ?></a></td>
                            <td data-label="Tanggal Surat"><?= e(format_date_id($row['letter_date'])) ?></td>
                            <td data-label="Diterima"><strong><?= e(format_date_id($row['received_date'])) ?></strong></td>
                            <td data-label="Asal"><span class="team-chip"><?= e($row['origin']) ?></span></td>
                            <td data-label="Perihal & Kepada"><strong class="cell-title"><?= e($row['subject']) ?></strong><small><?= e($row['recipient']) ?></small></td>
                            <td data-label="Dicatat Oleh"><?= e(display_user_name($row['creator_name'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
