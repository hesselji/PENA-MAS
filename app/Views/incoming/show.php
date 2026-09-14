<section class="page-heading detail-heading">
    <div>
        <a class="breadcrumb" href="/incoming"><?= ui_icon('arrow-left') ?> Kembali ke Agenda Surat Masuk</a>
        <span class="eyebrow">DETAIL SURAT MASUK</span>
        <h1>Informasi surat masuk</h1>
        <p>Informasi surat yang telah diterima dan dicatat pada PENA MAS.</p>
    </div>
</section>

<section class="number-hero-card incoming-hero">
    <div class="number-hero-icon"><?= ui_icon('mail') ?></div>
    <div class="number-hero-main"><span>Nomor Surat dari Pengirim</span><strong id="incomingNumberValue"><?= e($letter['letter_number']) ?></strong><small><?= e($letter['origin']) ?></small></div>
    <button class="btn btn-secondary" type="button" data-copy-target="#incomingNumberValue"><?= ui_icon('copy') ?><span>Salin Nomor</span></button>
</section>

<div class="detail-layout">
    <section class="panel detail-panel">
        <div class="panel-heading"><div><span class="eyebrow">DATA SURAT</span><h2>Informasi utama</h2></div><span class="panel-icon-soft"><?= ui_icon('mail') ?></span></div>
        <div class="detail-data-grid">
            <div><span>Asal Surat</span><strong><?= e($letter['origin']) ?></strong></div>
            <div><span>Kepada</span><strong><?= e($letter['recipient']) ?></strong></div>
            <div><span>Tanggal Surat</span><strong><?= e(format_date_id($letter['letter_date'])) ?></strong></div>
            <div><span>Tanggal Diterima</span><strong><?= e(format_date_id($letter['received_date'])) ?></strong></div>
            <div class="detail-span-2"><span>Perihal</span><strong><?= e($letter['subject']) ?></strong></div>
            <?php if($letter['notes']):?><div class="detail-span-2"><span>Catatan</span><p><?= nl2br(e($letter['notes'])) ?></p></div><?php endif;?>
        </div>
    </section>
    <aside class="detail-aside">
        <section class="panel metadata-panel">
            <span class="eyebrow">PENCATATAN</span>
            <div><span>Dicatat Oleh</span><strong><?= e(display_user_name($letter['creator_name'])) ?></strong></div>
            <div><span>Tanggal Pencatatan</span><strong><?= e(format_date_id($letter['recorded_at'],true)) ?></strong></div>
            <div><span>Terakhir Diperbarui</span><strong><?= e(format_date_id($letter['updated_at'],true)) ?></strong></div>
        </section>
    </aside>
</div>
