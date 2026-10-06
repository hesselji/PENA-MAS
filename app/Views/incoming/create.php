<?php $errs=$errors??[]; ?>
<section class="page-heading">
    <div>
        <a class="breadcrumb" href="/incoming"><?= ui_icon('arrow-left') ?> Agenda Surat Masuk</a>
        <span class="eyebrow">PENCATATAN SURAT MASUK</span>
        <h1>Tambah surat masuk</h1>
        <p>Nomor surat berasal dari pengirim. Tanggal pencatatan dibuat otomatis ketika data disimpan.</p>
    </div>
    <div class="heading-hint"><span><?= ui_icon('calendar') ?></span><div><strong>Pencatatan otomatis</strong><small>Waktu pencatatan tersimpan secara otomatis.</small></div></div>
</section>

<section class="panel form-panel form-panel-narrow">
    <form method="post" action="/incoming" enctype="multipart/form-data" class="modern-form" data-loading-form>
        <?= csrf_field() ?>
        <div class="form-section no-border">
            <div class="form-section-head"><span class="section-step green">01</span><div><h2>Informasi Surat</h2><p>Salin informasi sesuai surat yang diterima.</p></div></div>
            <div class="form-grid form-grid-2">
                <label class="field"><span>Nomor Surat <i>*</i></span><input name="letter_number" value="<?= old('letter_number') ?>" placeholder="Nomor dari surat pengirim" required><?php if(!empty($errs['letter_number'])):?><small class="field-error"><?= e($errs['letter_number']) ?></small><?php endif;?></label>
                <label class="field"><span>Asal Surat <i>*</i></span><input name="origin" value="<?= old('origin') ?>" placeholder="Instansi / pihak pengirim" required><?php if(!empty($errs['origin'])):?><small class="field-error"><?= e($errs['origin']) ?></small><?php endif;?></label>
                <label class="field"><span>Tanggal Surat <i>*</i></span><div class="field-icon-wrap"><?= ui_icon('calendar') ?><input type="date" name="letter_date" value="<?= old('letter_date') ?>" required></div><?php if(!empty($errs['letter_date'])):?><small class="field-error"><?= e($errs['letter_date']) ?></small><?php endif;?></label>
                <label class="field"><span>Tanggal Diterima <i>*</i></span><div class="field-icon-wrap"><?= ui_icon('calendar') ?><input type="date" name="received_date" value="<?= old('received_date',date('Y-m-d')) ?>" required></div><?php if(!empty($errs['received_date'])):?><small class="field-error"><?= e($errs['received_date']) ?></small><?php endif;?></label>
                <label class="field span-all"><span>Perihal <i>*</i></span><input name="subject" value="<?= old('subject') ?>" placeholder="Perihal surat" required><?php if(!empty($errs['subject'])):?><small class="field-error"><?= e($errs['subject']) ?></small><?php endif;?></label>
                <label class="field span-all"><span>Kepada <i>*</i></span><input name="recipient" value="<?= old('recipient') ?>" placeholder="Contoh: Kepala BPS Kota Palangka Raya" required><?php if(!empty($errs['recipient'])):?><small class="field-error"><?= e($errs['recipient']) ?></small><?php endif;?></label>
                <label class="field span-all"><span>Catatan</span><textarea name="notes" rows="4" placeholder="Catatan tambahan jika diperlukan"><?= old('notes') ?></textarea></label>
                <label class="field span-all"><span>Lampiran surat <small>(opsional · PDF/JPG/PNG maks 8 MB)</small></span><input type="file" name="attachment" accept="application/pdf,image/jpeg,image/png"><small>Disimpan di storage dan hanya dapat dibuka pengguna yang memiliki akses Surat Masuk.</small></label>
                <div class="scope-card span-all"><span>Waktu Pencatatan</span><strong><?= e(format_date_id(date('Y-m-d H:i:s'),true)) ?></strong><small>Waktu akan diperbarui otomatis saat surat disimpan.</small></div>
            </div>
        </div>
        <div class="form-actions-sticky"><div class="form-security-note"><?= ui_icon('shield') ?><span>Nomor surat masuk mengikuti nomor yang tercantum pada surat pengirim.</span></div><div><a class="btn btn-secondary" href="/incoming">Batal</a><button class="btn btn-primary" type="submit"><?= ui_icon('plus') ?><span>Simpan Surat Masuk</span></button></div></div>
    </form>
</section>
<?php unset($_SESSION['old']); ?>
