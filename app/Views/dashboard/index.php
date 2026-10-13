<?php
$total = (int)($summary['total'] ?? 0);
$cancelled = (int)($summary['cancelled'] ?? 0);
$active = max(0, $total - $cancelled);
$thisMonth = (int)($summary['this_month'] ?? 0);
$ordinary = (int)($summary['ordinary'] ?? 0);
$activePct = $total > 0 ? round(($active / $total) * 100) : 0;
$cancelPct = $total > 0 ? 100 - $activePct : 0;
$ordinaryPct = $total > 0 ? round(($ordinary / $total) * 100) : 0;
$maxTeam = $byTeam ? max(array_map(fn($r)=>(int)$r['total'], $byTeam)) : 1;
$isAdmin = \App\Core\Auth::isAdmin();
?>
<section class="page-heading dashboard-heading">
    <div>
        <span class="eyebrow">RINGKASAN</span>
        <h1>Selamat <?= (int)date('H') < 11 ? 'pagi' : ((int)date('H') < 15 ? 'siang' : ((int)date('H') < 18 ? 'sore' : 'malam')) ?>, <?= e(explode(' ', display_user_name(auth_user()['name']))[0] ?? display_user_name(auth_user()['name'])) ?> <span class="wave">👋</span></h1>
        <p><?= e(format_date_id(date('Y-m-d'))) ?> &nbsp; <?= $isAdmin ? 'Ringkasan seluruh agenda surat keluar.' : 'Ringkasan surat keluar yang Anda buat.' ?></p>
    </div>

    <div class="dashboard-motion" aria-hidden="true">
        <span class="dm-ring"></span>
        <span class="dm-glow"></span>
        <span class="dm-card dm-c1"><?= ui_icon('mail') ?></span>
        <span class="dm-card dm-c2"><?= ui_icon('check') ?></span>
        <span class="dm-doc"></span>
    </div>

    <div class="heading-actions">
        <a class="btn btn-secondary" href="/letters"><?= ui_icon('mail') ?><span>Lihat Agenda</span></a>
        <a class="btn btn-primary" href="/letters/create"><?= ui_icon('plus') ?><span>Buat Nomor Surat</span></a>
    </div>
</section>

<section class="metric-grid">
    <article class="metric-card metric-blue">
        <div class="metric-top"><span>Total Agenda</span><span class="metric-icon"><?= ui_icon('file') ?></span></div>
        <strong><?= number_format($total) ?></strong>
        <small>Jumlah surat yang sudah tercatat</small>
        <div class="metric-accent"></div>
    </article>

    <article class="metric-card metric-orange">
        <div class="metric-top"><span>Bulan Ini</span><span class="metric-icon"><?= ui_icon('calendar') ?></span></div>
        <strong><?= number_format($thisMonth) ?></strong>
        <small>Surat tercatat pada <?= e(month_year_id()) ?></small>
        <div class="metric-accent"></div>
    </article>

    <article class="metric-card metric-green">
        <div class="metric-top"><span>Nomor Aktif</span><span class="metric-icon"><?= ui_icon('check') ?></span></div>
        <strong><?= number_format($active) ?></strong>
        <small><?= $activePct ?>% dari seluruh agenda</small>
        <div class="metric-accent"></div>
    </article>

    <article class="metric-card metric-neutral">
        <div class="metric-top"><span>Dibatalkan</span><span class="metric-icon"><?= ui_icon('ban') ?></span></div>
        <strong><?= number_format($cancelled) ?></strong>
        <small>Tetap tersimpan dalam riwayat</small>
        <div class="metric-accent"></div>
    </article>
</section>

<section class="dashboard-primary-grid">
    <article class="panel status-panel">
        <div class="panel-heading">
            <div>
                <span class="eyebrow">STATUS AGENDA</span>
                <h2>Kondisi Surat Saat Ini</h2>
                <p>Ringkasan surat aktif, dibatalkan, dan surat biasa.</p>
            </div>
            <span class="period-chip"><?= ui_icon('calendar') ?> <?= e(month_year_id()) ?></span>
        </div>

        <div class="status-content">
            <div class="donut-wrap">
                <div class="donut" style="--active: <?= $activePct ?>%; --cancelled: <?= $cancelPct ?>%;">
                    <div class="donut-center"><strong><?= number_format($total) ?></strong><span>Total</span></div>
                </div>
            </div>

            <div class="legend-list">
                <div><span class="legend-dot green"></span><span>Aktif</span><strong><?= number_format($active) ?></strong><small><?= $activePct ?>%</small></div>
                <div><span class="legend-dot red"></span><span>Dibatalkan</span><strong><?= number_format($cancelled) ?></strong><small><?= $cancelPct ?>%</small></div>
                <div><span class="legend-dot blue"></span><span>Surat Biasa</span><strong><?= number_format($ordinary) ?></strong><small><?= $ordinaryPct ?>%</small></div>
            </div>
        </div>
    </article>

    <article class="panel latest-panel">
        <div class="panel-heading">
            <div>
                <span class="eyebrow">TERBARU</span>
                <h2>Agenda Terakhir</h2>
                <p>Surat yang paling baru dicatat.</p>
            </div>
            <a class="text-link" href="/letters">Lihat Semua <?= ui_icon('arrow-right') ?></a>
        </div>

        <div class="latest-list">
            <?php if (!$latest): ?>
                <div class="empty-mini">
                    <span><?= ui_icon('mail') ?></span>
                    <b>Belum ada agenda</b>
                    <small>Buat nomor surat pertama untuk mulai mengisi agenda.</small>
                </div>
            <?php else: foreach ($latest as $r): ?>
                <?php $isSecret = in_array($r['sensitivity'], ['RAHASIA','SANGAT_RAHASIA'], true); ?>
                <a class="latest-item" href="/letters/<?= (int)$r['id'] ?>">
                    <span class="latest-file-icon"><?= ui_icon($isSecret ? 'lock' : 'file') ?></span>
                    <span class="latest-main">
                        <strong><?= e($r['letter_number']) ?><?php if (is_new_record($r['created_at'] ?? null)): ?><span class="new-letter-badge">Surat baru</span><?php endif; ?></strong>
                        <small><?= $isSecret ? 'Surat terlindungi. Verifikasi diperlukan untuk melihat detail.' : e($r['subject']) ?></small>
                    </span>
                    <span class="latest-meta">
                        <span class="badge badge-<?= strtolower($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
                        <small><?= e(format_date_id($r['created_at'])) ?></small>
                    </span>
                </a>
            <?php endforeach; endif; ?>
        </div>
    </article>
</section>

<section class="dashboard-secondary-grid">
    <article class="panel team-panel">
        <div class="panel-heading">
            <div>
                <span class="eyebrow">TIM KERJA</span>
                <h2>Surat per Tim Kerja</h2>
                <p>Perbandingan jumlah surat berdasarkan tim.</p>
            </div>
            <span class="panel-icon-soft"><?= ui_icon('chart') ?></span>
        </div>

        <div class="bar-chart-list">
            <?php if (!$byTeam): ?>
                <div class="empty-mini"><b>Belum ada data tim</b><small>Ringkasan akan muncul setelah surat mulai dicatat.</small></div>
            <?php else: foreach ($byTeam as $idx => $row): $pct = max(5, round(((int)$row['total']/$maxTeam)*100)); ?>
                <div class="bar-chart-row">
                    <div class="bar-chart-label"><span><?= e($row['name']) ?></span><strong><?= (int)$row['total'] ?></strong></div>
                    <div class="bar-track"><i style="--w: <?= $pct ?>%; --delay: <?= $idx * 80 ?>ms"></i></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </article>

    <article class="panel quick-panel">
        <div class="panel-heading">
            <div>
                <span class="eyebrow">AKSES CEPAT</span>
                <h2>Pilih Kebutuhan Anda</h2>
                <p>Buka fitur yang paling sering digunakan.</p>
            </div>
        </div>

        <div class="quick-grid">
            <a class="quick-card quick-blue" href="/letters/create"><span><?= ui_icon('mail-plus') ?></span><div><b>Buat Nomor Surat</b><small>Catat dan buat nomor surat baru</small></div><?= ui_icon('arrow-right') ?></a>
            <a class="quick-card quick-green" href="/letters"><span><?= ui_icon('archive') ?></span><div><b>Agenda Surat Keluar</b><small>Lihat surat yang sudah tercatat</small></div><?= ui_icon('arrow-right') ?></a>
            <a class="quick-card quick-blue" href="/documents"><span><?= ui_icon('layers') ?></span><div><b>Dokumen & Cetak</b><small>Buat dokumen satuan atau batch</small></div><?= ui_icon('arrow-right') ?></a>
            <a class="quick-card quick-muted" href="/reports"><span><?= ui_icon('chart') ?></span><div><b>Laporan & Ekspor</b><small>Rekap, CSV, dan PDF</small></div><?= ui_icon('arrow-right') ?></a>
            <?php if ($isAdmin): ?><a class="quick-card quick-orange" href="/admin/users"><span><?= ui_icon('users') ?></span><div><b>Kelola Pengguna</b><small>Atur akun dan hak akses</small></div><?= ui_icon('arrow-right') ?></a><?php endif; ?>
            <?php if ($isAdmin): ?><a class="quick-card quick-muted" href="/incoming"><span><?= ui_icon('mail') ?></span><div><b>Surat Masuk</b><small>Lihat dan tindak lanjuti surat</small></div><?= ui_icon('arrow-right') ?></a><?php endif; ?>
        </div>
    </article>
</section>


<section class="dashboard-analytics-grid">
    <article class="panel analytics-trend-panel">
        <div class="panel-heading">
            <div>
                <span class="eyebrow">ANALITIK</span>
                <h2>Tren 6 Bulan</h2>
                <p>Perkembangan surat keluar<?= $isAdmin ? ' dan surat masuk' : '' ?> selama enam bulan terakhir.</p>
            </div>
            <span class="panel-icon-soft"><?= ui_icon('chart') ?></span>
        </div>
        <?php
        $trendMax = 1;
        foreach (($trendMonths ?? []) as $m) $trendMax = max($trendMax, (int)$m['outgoing'], (int)$m['incoming']);
        ?>
        <div class="trend-chart">
            <?php foreach (($trendMonths ?? []) as $m): ?>
                <div class="trend-column">
                    <div class="trend-bars">
                        <i class="trend-bar outgoing" style="--h: <?= max(6, round(((int)$m['outgoing']/$trendMax)*100)) ?>%" title="<?= (int)$m['outgoing'] ?> surat keluar"></i>
                        <?php if($isAdmin): ?><i class="trend-bar incoming" style="--h: <?= max(6, round(((int)$m['incoming']/$trendMax)*100)) ?>%" title="<?= (int)$m['incoming'] ?> surat masuk"></i><?php endif; ?>
                    </div>
                    <strong><?= e($m['short']) ?></strong>
                    <small><?= (int)$m['outgoing'] ?><?= $isAdmin ? ' / '.(int)$m['incoming'] : '' ?></small>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="trend-legend"><span><i class="legend-swatch outgoing"></i>Surat keluar</span><?php if($isAdmin):?><span><i class="legend-swatch incoming"></i>Surat masuk</span><?php endif;?></div>
    </article>

    <article class="panel analytics-kka-panel">
        <div class="panel-heading">
            <div><span class="eyebrow">KLASIFIKASI</span><h2>KKA Paling Sering Dipakai</h2><p>Kode yang paling banyak digunakan pada agenda surat keluar.</p></div>
            <span class="panel-icon-soft"><?= ui_icon('archive') ?></span>
        </div>
        <div class="kka-rank-list">
            <?php if(empty($topKka)): ?><div class="empty-mini"><b>Belum ada data</b><small>Ringkasan KKA akan muncul setelah surat dicatat.</small></div>
            <?php else: foreach($topKka as $i=>$row): ?><div class="kka-rank-row"><span><?= $i+1 ?></span><strong><?= e($row['classification_code']) ?></strong><em><?= (int)$row['total'] ?> surat</em></div><?php endforeach; endif; ?>
        </div>
        <?php if($isAdmin): ?>
            <div class="workflow-mini-summary">
                <div><span>Masuk</span><strong><?= (int)($incomingWorkflow['MASUK']??0) ?></strong></div>
                <div><span>Disposisi</span><strong><?= (int)($incomingWorkflow['DISPOSISI']??0) ?></strong></div>
                <div><span>Selesai</span><strong><?= (int)($incomingWorkflow['SELESAI']??0) ?></strong></div>
            </div>
        <?php endif; ?>
    </article>
</section>
