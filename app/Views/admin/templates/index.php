<section class="page-heading">
    <div><span class="eyebrow">DOKUMEN</span><h1>Template dokumen</h1><p>Atur format surat yang akan diisi otomatis dari data agenda PENA MAS.</p></div>
    <a class="btn btn-primary" href="/admin/templates/create"><?= ui_icon('plus') ?><span>Template Baru</span></a>
</section>

<section class="panel template-library-panel">
    <div class="template-callout"><span><?= ui_icon('info') ?></span><div><strong>Template dapat diubah tanpa mengubah source code</strong><p>Gunakan placeholder yang tersedia untuk nomor surat, tanggal, tujuan, KKA, dan penanda tangan.</p></div></div>
    <div class="template-card-grid">
        <?php if(!$templates): ?><div class="empty-state"><span><?= ui_icon('template') ?></span><h3>Belum ada template</h3><p>Buat template pertama untuk mulai menghasilkan dokumen.</p></div><?php endif; ?>
        <?php foreach($templates as $t): ?>
            <article class="template-card <?= $t['is_active']?'':'is-disabled' ?>">
                <div class="template-card-icon"><?= ui_icon('template') ?></div>
                <div class="template-card-main"><div class="template-card-title"><strong><?= e($t['name']) ?></strong><span class="badge <?= $t['is_active']?'badge-active':'badge-cancelled' ?>"><?= $t['is_active']?'Aktif':'Nonaktif' ?></span></div><p><?= e($t['description'] ?: 'Template dokumen PENA MAS') ?></p><small><?= e($t['letter_type_name'] ?: 'Dapat digunakan untuk semua jenis surat') ?></small><?php if(in_array(strtoupper((string)($t['source_format']??'')), ['DOCX','DOCX_EXACT'], true) && !empty($t['source_file_path'])):?><span class="badge badge-active" style="margin-top:7px;width:max-content">DOCX Presisi</span><?php elseif(strtoupper((string)($t['source_format']??''))==='PDF'):?><span class="badge" style="margin-top:7px;width:max-content">PDF Referensi</span><?php endif;?></div>
                <div class="template-card-actions"><a class="btn btn-secondary" href="/admin/templates/<?= (int)$t['id'] ?>/edit">Ubah</a><form method="post" action="/admin/templates/<?= (int)$t['id'] ?>/toggle"><?= csrf_field() ?><button class="btn btn-ghost" type="submit"><?= $t['is_active']?'Nonaktifkan':'Aktifkan' ?></button></form></div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
