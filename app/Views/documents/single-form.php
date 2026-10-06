<section class="page-heading detail-heading">
    <div>
        <a class="breadcrumb" href="/letters/<?= (int)$letter['id'] ?>"><?= ui_icon('arrow-left') ?> Kembali</a>
        <span class="eyebrow">BUAT DOKUMEN</span>
        <h1><?= e($letter['letter_number']) ?></h1>
        <p>Pilih template dan penanda tangan. Jika template berasal dari DOCX, PENA MAS juga dapat menghasilkan file Word dengan layout asli.</p>
    </div>
</section>

<section class="panel form-panel form-panel-narrow">
    <form method="post" action="/documents/create/<?= (int)$letter['id'] ?>" class="modern-form" data-loading-form>
        <?= csrf_field() ?>
        <div class="form-grid form-grid-2">
            <label class="field span-all">
                <span>Template</span>
                <select name="template_id" required>
                    <option value="">Pilih template</option>
                    <?php foreach($templates as $t): ?>
                        <?php $exact=in_array(strtoupper((string)($t['source_format']??'')),['DOCX','DOCX_EXACT'],true) && !empty($t['source_file_path']); ?>
                        <option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?><?= $t['letter_type_name']?' · '.e($t['letter_type_name']):'' ?><?= $exact?' · DOCX Presisi':'' ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="field span-all">
                <span>Penanda tangan</span>
                <select name="signer_id" required>
                    <option value="">Pilih penanda tangan</option>
                    <?php foreach($signers as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= e($s['name'].' · '.$s['position']) ?><?= !empty($s['prototype_tte_enabled'])?' · Passkey aktif':'' ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="field"><span>Tempat tanda tangan</span><input name="tempat_ttd" value="Palangka Raya"></label>
            <label class="field"><span>Kode pembebanan / mata anggaran</span><input name="pembebanan" placeholder="Contoh: 2910.QMA.007.005.521213"></label>
            <label class="field span-all"><span>Dasar pembebanan</span><input name="dasar_pembebanan" placeholder="Contoh: DIPA BPS Kota Palangka Raya T.A 2026"></label>
            <label class="field"><span>Tanggal pelaksanaan</span><input name="tgl_pelaksanaan" placeholder="Contoh: 16-31 Desember 2026"></label>
            <label class="field"><span>Tujuan tugas / lokasi</span><input name="tujuan_tugas" placeholder="Contoh: Palangka Raya"></label>
            <label class="field span-all"><span>Keperluan / keterangan tugas</span><textarea name="keterangan_tugas" rows="3" placeholder="Jika dikosongkan, PENA MAS memakai perihal surat."></textarea></label>
        </div>

        <div class="precision-inline-note">
            <?= ui_icon('info') ?>
            <div><strong>Mode DOCX Presisi</strong><span>Template Word tidak dibangun ulang menjadi HTML. PENA MAS mengisi Mail Merge/placeholder langsung pada file DOCX asli sehingga logo, margin, tabel, font, header, footer, dan susunan halaman tetap mengikuti template sumber.</span></div>
        </div>

        <div class="form-actions-inline">
            <button class="btn btn-primary" type="submit"><?= ui_icon('file') ?> Buat Dokumen</button>
        </div>
    </form>
</section>
