<section class="page-heading">
    <div>
        <span class="eyebrow">PENGATURAN</span>
        <h1>Pengaturan penomoran</h1>
        <p>Atur kode unit dan bentuk nomor surat yang digunakan pada setiap jenis surat.</p>
    </div>
    <a class="btn btn-secondary" href="/admin/classifications"><?= ui_icon('archive') ?><span>Katalog KKA</span></a>
</section>

<section class="panel master-note-panel">
    <div class="panel-heading">
        <div>
            <span class="eyebrow">INFORMASI</span>
            <h2>Pengaturan nomor dapat disesuaikan sesuai kebutuhan kantor</h2>
            <p>Bagian yang berada di dalam tanda kurung akan diisi otomatis oleh PENA MAS ketika nomor surat dibuat.</p>
        </div>
        <span class="panel-icon-soft"><?= ui_icon('settings') ?></span>
    </div>
</section>

<div class="admin-grid-2">
    <?php foreach($rules as $rule): ?>
        <section class="panel rule-card">
            <div class="panel-heading compact-heading">
                <div>
                    <span class="eyebrow">UNIT <?= e($rule['unit_code']) ?></span>
                    <h2><?= e($rule['name']) ?></h2>
                    <p><?= (int)$rule['type_count'] ?> jenis surat menggunakan pengaturan ini.</p>
                </div>
                <span class="badge <?= $rule['is_active'] ? 'badge-active' : 'badge-cancelled' ?>"><?= $rule['is_active'] ? 'Aktif' : 'Nonaktif' ?></span>
            </div>

            <form class="modern-form" method="post" action="/admin/numbering-rules/<?= (int)$rule['id'] ?>" data-loading-form>
                <?= csrf_field() ?>
                <label class="field"><span>Nama Pengaturan</span><input name="name" value="<?= e($rule['name']) ?>" required></label>
                <label class="field"><span>Kode Unit</span><input name="unit_code" value="<?= e($rule['unit_code']) ?>" required></label>
                <label class="field"><span>Format Nomor</span><input name="pattern" value="<?= e($rule['pattern']) ?>" required><small class="field-help">Gunakan format yang telah disepakati untuk jenis surat terkait.</small></label>
                <label class="toggle-line"><input type="checkbox" name="is_active" value="1" <?= $rule['is_active'] ? 'checked' : '' ?>><span>Pengaturan digunakan</span></label>
                <button class="btn btn-primary" type="submit"><?= ui_icon('check') ?><span>Simpan Pengaturan</span></button>
            </form>
        </section>
    <?php endforeach; ?>
</div>

<section class="panel users-panel section-gap">
    <div class="panel-heading">
        <div>
            <span class="eyebrow">JENIS SURAT</span>
            <h2>Penomoran untuk setiap jenis surat</h2>
            <p>Tentukan pengaturan nomor yang digunakan untuk masing-masing jenis surat.</p>
        </div>
        <span class="panel-icon-soft"><?= ui_icon('mail') ?></span>
    </div>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead><tr><th>Jenis Surat</th><th>Pengaturan Saat Ini</th><th>Kode Unit</th><th>Format Nomor</th><th>Pengaturan</th></tr></thead>
            <tbody>
            <?php foreach($types as $type): ?>
                <tr>
                    <td><strong><?= e($type['name']) ?></strong><small class="table-subtext"><?= e($type['code']) ?></small></td>
                    <td><?= e($type['rule_name']) ?></td>
                    <td><strong><?= e($type['unit_code']) ?></strong></td>
                    <td><span class="code-wrap"><?= e($type['pattern']) ?></span></td>
                    <td>
                        <form class="inline-admin-form" method="post" action="/admin/letter-types/<?= (int)$type['id'] ?>">
                            <?= csrf_field() ?>
                            <select name="numbering_rule_id" aria-label="Pilih pengaturan nomor">
                                <?php foreach($rules as $rule): ?>
                                    <option value="<?= (int)$rule['id'] ?>" <?= (int)$type['numbering_rule_id']===(int)$rule['id'] ? 'selected' : '' ?>><?= e($rule['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="inline-check"><input type="checkbox" name="is_active" value="1" <?= $type['is_active'] ? 'checked' : '' ?>> Digunakan</label>
                            <button class="btn btn-secondary btn-sm" type="submit">Simpan</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
