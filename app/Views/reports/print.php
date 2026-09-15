<?php
$reportTitle = $source === 'incoming' ? 'Laporan Surat Masuk' : 'Laporan Surat Keluar';
$periodText = format_date_id($filters['date_from']) . ' - ' . format_date_id($filters['date_to']);
$isOutgoing = $source === 'outgoing';
$isAdmin = \App\Core\Auth::isAdmin();
?><!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($reportTitle) ?> - PENA MAS</title>
    <style>
        *{box-sizing:border-box} body{margin:0;background:#eef3f7;color:#172532;font-family:Arial,Helvetica,sans-serif;font-size:12px}.sheet{width:min(1180px,calc(100% - 32px));margin:24px auto;background:#fff;border:1px solid #dbe4eb;border-radius:14px;box-shadow:0 18px 55px rgba(25,52,72,.12);overflow:hidden}.screen-actions{display:flex;gap:8px;justify-content:flex-end;padding:14px 18px;background:#f7fafc;border-bottom:1px solid #e5edf2}.screen-actions a,.screen-actions button{border:1px solid #ccdbe5;background:#fff;color:#17354b;border-radius:8px;padding:9px 13px;font:inherit;font-weight:700;cursor:pointer;text-decoration:none}.screen-actions button{background:#168fc9;color:#fff;border-color:#168fc9}.report-body{padding:28px 32px 34px}.report-brand{display:flex;align-items:center;gap:12px;padding-bottom:18px;border-bottom:2px solid #138fc8}.report-brand img{width:48px;height:56px;object-fit:contain}.report-brand strong{display:block;font-size:20px;letter-spacing:.04em}.report-brand small{display:block;color:#637887;margin-top:4px}.report-title{display:flex;justify-content:space-between;gap:20px;align-items:flex-end;padding:22px 0 16px}.report-title h1{font-size:24px;margin:0 0 6px}.report-title p{margin:0;color:#647987}.report-meta{text-align:right;color:#647987;line-height:1.6}.summary{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:4px 0 20px}.summary div{border:1px solid #dce6ed;border-radius:9px;padding:12px}.summary small{display:block;color:#6f8290;margin-bottom:6px}.summary strong{font-size:20px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse}th{background:#eef6fa;color:#385269;text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.04em;padding:9px 8px;border:1px solid #d8e3ea}td{padding:9px 8px;border:1px solid #e0e8ed;vertical-align:top;line-height:1.45}tbody tr:nth-child(even){background:#fafcfd}.muted{color:#718492;font-size:10px}.protected{font-weight:700;color:#744d77}.footer-note{margin-top:18px;padding-top:12px;border-top:1px solid #dce5eb;color:#728492;font-size:10px;display:flex;justify-content:space-between;gap:20px}
        @media print{body{background:#fff;font-size:9px}.sheet{width:100%;margin:0;border:0;border-radius:0;box-shadow:none}.screen-actions{display:none}.report-body{padding:0}.report-brand{padding-bottom:10px}.report-brand img{width:36px;height:42px}.report-brand strong{font-size:16px}.report-title{padding:12px 0 10px}.report-title h1{font-size:17px}.summary{gap:6px;margin-bottom:12px}.summary div{padding:7px;border-radius:4px}.summary strong{font-size:14px}th,td{padding:5px 4px}.footer-note{margin-top:10px}@page{size:A4 landscape;margin:11mm}}
        @media(max-width:720px){.report-title{display:block}.report-meta{text-align:left;margin-top:10px}.summary{grid-template-columns:1fr 1fr}.sheet{width:100%;margin:0;border-radius:0}.report-body{padding:20px 14px}}
    </style>
</head>
<body>
<div class="sheet">
    <div class="screen-actions">
        <a href="/reports?<?= e(http_build_query(array_filter(['source'=>$source] + $filters, static fn($v) => $v !== '' && $v !== null))) ?>">Kembali ke laporan</a>
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>
    <main class="report-body">
        <header class="report-brand">
            <img src="/assets/images/penamas-icon.png" alt="Logo PENA MAS">
            <div><strong>PENA MAS</strong><small>Penomoran Agenda dan Manajemen Arsip Surat<br>Badan Pusat Statistik Kota Palangka Raya</small></div>
        </header>

        <section class="report-title">
            <div><h1><?= e($reportTitle) ?></h1><p>Periode <?= e($periodText) ?></p></div>
            <div class="report-meta">Dicetak: <?= e(format_date_id(date('Y-m-d H:i:s'), true)) ?><br>Oleh: <?= e(display_user_name($me['name'] ?? 'Pengguna')) ?></div>
        </section>

        <section class="summary">
            <?php if ($isOutgoing): ?>
                <div><small>Total surat</small><strong><?= (int)$summary['total'] ?></strong></div>
                <div><small>Nomor aktif</small><strong><?= (int)$summary['active'] ?></strong></div>
                <div><small>Dibatalkan</small><strong><?= (int)$summary['cancelled'] ?></strong></div>
                <?php if ($isAdmin): ?><div><small>Surat terlindungi</small><strong><?= (int)$summary['confidential'] ?></strong></div><?php endif; ?>
            <?php else: ?>
                <div><small>Total surat masuk</small><strong><?= (int)$summary['total'] ?></strong></div>
                <div><small>Asal surat</small><strong><?= (int)$summary['origins'] ?></strong></div>
                <div><small>Petugas pencatat</small><strong><?= (int)$summary['recorders'] ?></strong></div>
                <div><small>Terakhir diterima</small><strong><?= e($summary['latest_received'] ? format_date_id($summary['latest_received']) : '-') ?></strong></div>
            <?php endif; ?>
        </section>

        <div class="table-wrap">
        <?php if ($isOutgoing): ?>
            <table>
                <thead><tr><th>No</th><th>Nomor Surat</th><th>Tanggal</th><th>Jenis / Tim</th><th>Perihal & Tujuan</th><th>KKA</th><th>Sifat</th><th>Status</th><th>Dibuat Oleh</th></tr></thead>
                <tbody>
                <?php if (!$rows): ?><tr><td colspan="9">Tidak ada data pada periode yang dipilih.</td></tr><?php endif; ?>
                <?php foreach ($rows as $i => $row): $secret = in_array($row['sensitivity'], ['RAHASIA','SANGAT_RAHASIA'], true); ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= e($row['letter_number']) ?></strong><div class="muted">Agenda #<?= e($row['agenda_number']) ?></div></td>
                        <td><?= e(format_date_id($row['letter_date'])) ?></td>
                        <td><?= e($row['letter_type_name']) ?><div class="muted"><?= e($row['work_team_name']) ?></div></td>
                        <td><?php if ($secret): ?><span class="protected">Detail terlindungi</span><?php else: ?><?= e($row['subject']) ?><div class="muted"><?= e($row['recipient']) ?></div><?php endif; ?></td>
                        <td><?= e($row['classification_code']) ?></td>
                        <td><?= e(sensitivity_label($row['sensitivity'])) ?></td>
                        <td><?= e(status_label($row['status'])) ?></td>
                        <td><?= e(display_user_name($row['requester_name'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <table>
                <thead><tr><th>No</th><th>Nomor Surat</th><th>Tanggal Surat</th><th>Diterima</th><th>Asal</th><th>Perihal & Kepada</th><th>Dicatat Oleh</th></tr></thead>
                <tbody>
                <?php if (!$rows): ?><tr><td colspan="7">Tidak ada data pada periode yang dipilih.</td></tr><?php endif; ?>
                <?php foreach ($rows as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= e($row['letter_number']) ?></strong></td>
                        <td><?= e(format_date_id($row['letter_date'])) ?></td>
                        <td><?= e(format_date_id($row['received_date'])) ?></td>
                        <td><?= e($row['origin']) ?></td>
                        <td><?= e($row['subject']) ?><div class="muted"><?= e($row['recipient']) ?></div></td>
                        <td><?= e(display_user_name($row['creator_name'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        </div>

        <footer class="footer-note">
            <span>Laporan dihasilkan melalui PENA MAS.</span>
            <span><?= e($periodText) ?></span>
        </footer>
    </main>
</div>
</body>
</html>
