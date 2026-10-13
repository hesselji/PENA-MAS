<?php use App\Core\Auth; ?>
<section class="page-heading">
    <div>
        <span class="eyebrow">SURAT KELUAR</span>
        <h1>Agenda surat keluar</h1>
        <p><?= Auth::isAdmin() ? 'Administrator melihat seluruh agenda, termasuk surat terlindungi.' : 'Seluruh surat Biasa dari semua pengguna ditampilkan pada agenda bersama.' ?></p>
    </div>
    <a class="btn btn-primary" href="/letters/create"><?= ui_icon('plus') ?><span>Buat Nomor Baru</span></a>
</section>

<section class="panel agenda-panel">
    <form method="get" class="agenda-toolbar">
        <div class="toolbar-search"><?= ui_icon('search') ?><input name="q" value="<?= e($filters['q']) ?>" placeholder="Cari nomor, perihal, tujuan, pembuat..."></div>
        <button class="btn btn-secondary filter-toggle" type="button" data-filter-toggle><?= ui_icon('filter') ?><span>Filter</span></button>
        <a class="btn btn-ghost" href="/letters"><?= ui_icon('refresh') ?><span>Reset</span></a>
        <div class="advanced-filters" data-filter-panel>
            <label><span>Tim Kerja</span><select name="team"><option value="">Semua Tim</option><?php foreach($teams as $t):?><option value="<?= (int)$t['id'] ?>" <?= ((string)$filters['team']===(string)$t['id'])?'selected':'' ?>><?= e($t['name']) ?></option><?php endforeach;?></select></label>
            <?php if(Auth::isAdmin()): ?><label><span>Sifat</span><select name="sensitivity"><option value="">Semua Sifat</option><?php foreach($sensitivities as $s):?><option value="<?= e($s['code']) ?>" <?= $filters['sensitivity']===$s['code']?'selected':'' ?>><?= e($s['name']) ?></option><?php endforeach;?></select></label><?php endif; ?>
            <label><span>Status</span><select name="status"><option value="">Semua Status</option><option value="ACTIVE" <?= $filters['status']==='ACTIVE'?'selected':'' ?>>Aktif</option><option value="CANCELLED" <?= $filters['status']==='CANCELLED'?'selected':'' ?>>Dibatalkan</option></select></label>
            <label><span>Tahun</span><input name="year" inputmode="numeric" value="<?= e($filters['year']) ?>" placeholder="2026"></label>
            <button class="btn btn-primary" type="submit">Terapkan Filter</button>
        </div>
    </form>

    <div class="agenda-summary-row">
        <div><span class="summary-dot blue"></span><b><?= count($letters) ?></b><span>surat ditampilkan</span></div>
        <?php if (!empty($filters['q']) || !empty($filters['team']) || !empty($filters['status'])): ?><span class="filter-active-chip"><?= ui_icon('filter') ?> Filter aktif</span><?php endif; ?>
    </div>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead><tr><th>Nomor Surat</th><th>Tanggal</th><th>Perihal & Tujuan</th><th>Tim / Pembuat</th><th>Sifat</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
            <?php if (!$letters): ?>
                <tr><td colspan="7"><div class="empty-state"><span><?= ui_icon('mail') ?></span><h3>Tidak ada item ditemukan</h3><p>Belum ada surat yang cocok dengan pencarian atau pilihan Anda.</p><a class="btn btn-primary" href="/letters/create">Buat Nomor Surat</a></div></td></tr>
            <?php else: foreach($letters as $r): $secret=in_array($r['sensitivity'],['RAHASIA','SANGAT_RAHASIA'],true); ?>
                <tr class="<?= $secret?'confidential-row':'' ?>">
                    <td><a class="number-link" href="/letters/<?= (int)$r['id'] ?>"><?= e($r['letter_number']) ?></a><?php if (is_new_record($r['created_at'] ?? null)): ?><span class="new-letter-badge">Surat baru</span><?php endif; ?><small>Agenda #<?= e($r['agenda_number']) ?> - <?= e($r['letter_type_name']) ?></small></td>
                    <td><span class="date-cell"><?= ui_icon('calendar') ?><?= e(format_date_id($r['letter_date'])) ?></span></td>
                    <td>
                        <?php if($secret): ?>
                            <strong class="cell-title confidential-mask"><?= ui_icon('lock') ?> Detail terlindungi</strong><small>Verifikasi password Administrator untuk melihat perihal dan tujuan.</small>
                        <?php else: ?>
                            <strong class="cell-title"><?= e($r['subject']) ?></strong><small><?= e($r['recipient']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><span class="team-chip"><?= e($r['work_team_name']) ?></span><small><?= e(display_user_name($r['requester_name'])) ?></small></td>
                    <td><span class="sensitivity-badge sens-<?= strtolower($r['sensitivity']) ?>"><?= e(sensitivity_label($r['sensitivity'])) ?></span></td>
                    <td><span class="badge badge-<?= strtolower($r['status']) ?>"><i></i><?= e(status_label($r['status'])) ?></span></td>
                    <td class="text-right"><a class="icon-btn row-action" href="/letters/<?= (int)$r['id'] ?>" title="Lihat detail" aria-label="Lihat detail"><?= $secret?ui_icon('lock'):ui_icon('eye') ?></a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
