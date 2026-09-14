<section class="page-heading">
    <div><a class="breadcrumb" href="/admin/users"><?= ui_icon('arrow-left') ?> Pengguna</a><span class="eyebrow">ADMINISTRASI</span><h1>Tambah pengguna</h1><p>Buat akun sesuai tugas dan kewenangan pengguna.</p></div>
</section>

<section class="panel form-panel form-panel-narrow">
    <form method="post" action="/admin/users" class="modern-form" data-loading-form autocomplete="off">
        <?= csrf_field() ?>
        <div class="form-section no-border">
            <div class="form-section-head"><span class="section-step">01</span><div><h2>Informasi Akun</h2><p>Password awal minimal 8 karakter dan dapat diubah kembali bila diperlukan.</p></div></div>
            <div class="form-grid form-grid-2">
                <label class="field"><span>Nama Lengkap <i>*</i></span><input name="name" value="<?= old('name') ?>" required><?php if(!empty($errors['name'])):?><small class="field-error"><?= e($errors['name']) ?></small><?php endif;?></label>
                <label class="field"><span>Email <i>*</i></span><input type="email" name="email" value="<?= old('email') ?>" placeholder="nama@bps.go.id" required><?php if(!empty($errors['email'])):?><small class="field-error"><?= e($errors['email']) ?></small><?php endif;?></label>
                <label class="field"><span>Password Awal <i>*</i></span><div class="password-field-shell"><input id="createUserPassword" type="password" name="password" placeholder="Minimal 8 karakter" required minlength="8" maxlength="72" autocomplete="new-password"><button class="password-eye" type="button" data-password-toggle="#createUserPassword" aria-label="Tampilkan password"><?= ui_icon('eye') ?></button></div><?php if(!empty($errors['password'])):?><small class="field-error"><?= e($errors['password']) ?></small><?php endif;?></label>
                <label class="field"><span>Hak Akses <i>*</i></span><select name="role" required><option value="USER" <?= old('role','USER')==='USER'?'selected':'' ?>>Pengguna Surat Keluar</option><option value="INCOMING" <?= old('role')==='INCOMING'?'selected':'' ?>>Petugas Surat Masuk</option><option value="ADMIN" <?= old('role')==='ADMIN'?'selected':'' ?>>Administrator</option></select></label>
                <label class="field span-all"><span>Tim Kerja</span><select name="work_team_id"><option value="">Tidak ditentukan</option><?php foreach($teams as $t):?><option value="<?= (int)$t['id'] ?>" <?= old('work_team_id')==(string)$t['id']?'selected':'' ?>><?= e($t['name']) ?></option><?php endforeach;?></select></label>
            </div>
        </div>
        <div class="form-actions-sticky"><div class="form-security-note"><?= ui_icon('shield') ?><span>Password dilindungi dan tidak ditampilkan kembali setelah akun dibuat.</span></div><div><a class="btn btn-secondary" href="/admin/users">Batal</a><button class="btn btn-primary" type="submit"><?= ui_icon('plus') ?><span>Simpan Pengguna</span></button></div></div>
    </form>
</section>
<?php unset($_SESSION['old']); ?>
