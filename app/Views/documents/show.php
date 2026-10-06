<section class="page-heading detail-heading">
    <div>
        <a class="breadcrumb" href="/documents"><?= ui_icon('arrow-left') ?> Kembali ke Dokumen & Cetak</a>
        <span class="eyebrow">PREVIEW DOKUMEN</span>
        <h1><?= e($document['letter_number']) ?></h1>
        <p>Preview web digunakan untuk pemeriksaan cepat. Untuk template Word, gunakan DOCX Presisi agar layout mengikuti file sumber asli.</p>
    </div>
    <div class="heading-actions document-output-actions">
        <a class="btn btn-secondary" href="/documents/create/<?= (int)$letter['id'] ?>"><?= ui_icon('refresh') ?> Buat Ulang</a>
        <?php if(!empty($exactDocxAvailable)): ?>
            <a class="btn btn-primary" href="/documents/<?= (int)$document['id'] ?>/download-docx"><?= ui_icon('download') ?> DOCX Presisi</a>
        <?php endif; ?>
        <?php if(!empty($exactPdfAvailable)): ?>
            <a class="btn btn-secondary" href="/documents/<?= (int)$document['id'] ?>/download-pdf-exact"><?= ui_icon('file') ?> PDF Presisi</a>
        <?php endif; ?>
        <a class="btn btn-secondary" target="_blank" href="/documents/<?= (int)$document['id'] ?>/print"><?= ui_icon('printer') ?> Preview Cetak</a>
    </div>
</section>

<?php if(!empty($exactDocxAvailable)): ?>
<section class="precision-banner">
    <span class="precision-banner-icon"><?= ui_icon('file') ?></span>
    <div><strong>Template Word asli aktif</strong><p>Tombol <b>DOCX Presisi</b> mengisi data langsung pada file Word sumber, bukan membuat ulang desainnya. Perbedaan tampilan pada preview web tidak memengaruhi file DOCX presisi.</p></div>
</section>
<?php endif; ?>

<section class="signature-workflow panel">
    <div><span class="eyebrow">ALUR TANDA TANGAN</span><h2><?= $document['tte_status']==='SIGNED'?'Sudah ditandatangani':($document['tte_status']==='PENDING'?'Menunggu tanda tangan':($document['tte_status']==='FAILED'?'Ditolak':'Belum diajukan')) ?></h2><p>Penanda tangan: <?= e($document['signer_name']) ?> · <?= e($document['signer_position']) ?></p></div>
    <div class="signature-actions">
        <?php if($document['tte_status']==='NOT_CONNECTED'||$document['tte_status']==='FAILED'): ?>
            <form method="post" action="/documents/<?= (int)$document['id'] ?>/submit"><?= csrf_field() ?><button class="btn btn-primary" type="submit">Ajukan Tanda Tangan</button></form>
        <?php elseif($document['tte_status']==='PENDING'&&\App\Core\Auth::isAdmin()): ?>
            <form method="post" action="/documents/<?= (int)$document['id'] ?>/sign" class="inline-sign-form"><?= csrf_field() ?><input type="password" name="passkey" placeholder="Passkey prototype" required><button class="btn btn-primary" type="submit"><?= ui_icon('signature') ?> Tanda Tangani</button></form>
            <form method="post" action="/documents/<?= (int)$document['id'] ?>/reject" class="inline-sign-form"><?= csrf_field() ?><input name="reason" placeholder="Alasan penolakan"><button class="btn btn-danger" type="submit">Tolak</button></form>
        <?php elseif($document['tte_status']==='SIGNED'): ?>
            <span class="signed-chip">✓ Prototype ditandatangani <?= e(format_date_id($document['signed_at'],true)) ?></span>
        <?php endif; ?>
    </div>
</section>

<section class="document-preview-shell">
    <div class="document-preview-toolbar"><span><b>Template:</b> <?= e($document['template_name']) ?></span><span><b>Penanda tangan:</b> <?= e($document['signer_name']) ?></span><span class="tte-pill <?= strtolower($document['tte_status']) ?>"><?= e($document['tte_status']==='SIGNED'?'Ditandatangani Prototype':($document['tte_status']==='PENDING'?'Menunggu':'Belum ditandatangani')) ?></span></div>
    <article class="document-paper"><?= $document['display_html'] ?></article>
</section>
