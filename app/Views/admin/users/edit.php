<section class="page-heading">
    <div>
        <a class="breadcrumb" href="/admin/users"><?= ui_icon('arrow-left') ?> Pengguna</a>
        <span class="eyebrow">ADMINISTRASI</span>
        <h1>Edit pengguna</h1>
        <p>Perbarui identitas, hak akses, dan tim kerja pengguna PENA MAS.</p>
    </div>
</section>

<section class="panel form-panel form-panel-narrow">
    <form method="post" action="/admin/users/<?= (int) $user['id'] ?>/update" class="modern-form" data-loading-form>
        <?= csrf_field() ?>
        <div class="form-section no-border">
            <div class="form-section-head"><span class="section-step">01</span><div><h2>Informasi Akun</h2><p>Ubah informasi akun tanpa mengubah password pengguna.</p></div></div>
            <div class="form-grid form-grid-2">
                <label class="field">
                    <span>Nama Lengkap <i>*</i></span>
                    <input name="name" value="<?= old('name', (string) $user['name']) ?>" placeholder="Nama lengkap pengguna" required>
                    <?php if (!empty($errors['name'])): ?><small class="field-error"><?= e($errors['name']) ?></small><?php endif; ?>
                </label>

                <label class="field">
                    <span>Email <i>*</i></span>
                    <input type="email" name="email" value="<?= old('email', (string) $user['email']) ?>" placeholder="nama@bps.go.id" required>
                    <?php if (!empty($errors['email'])): ?><small class="field-error"><?= e($errors['email']) ?></small><?php endif; ?>
                </label>

                <?php $selectedRole = old('role', (string) $user['role']); ?>
                <label class="field">
                    <span>Hak Akses <i>*</i></span>
                    <select name="role" required>
                        <option value="USER" <?= $selectedRole === 'USER' ? 'selected' : '' ?>>Pengguna Surat Keluar</option>
                        <option value="INCOMING" <?= $selectedRole === 'INCOMING' ? 'selected' : '' ?>>Petugas Surat Masuk</option>
                        <option value="ADMIN" <?= $selectedRole === 'ADMIN' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                    <?php if (!empty($errors['role'])): ?><small class="field-error"><?= e($errors['role']) ?></small><?php endif; ?>
                </label>

                <?php $selectedTeam = old('work_team_id', (string) ($user['work_team_id'] ?? '')); ?>
                <label class="field">
                    <span>Tim Kerja</span>
                    <select name="work_team_id">
                        <option value="">Tidak ditentukan</option>
                        <?php foreach ($teams as $t): ?><option value="<?= (int) $t['id'] ?>" <?= $selectedTeam === (string) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
                    </select>
                </label>

                <div class="field span-all">
                    <span>Status Akun</span>
                    <div><span class="badge <?= $user['is_active'] ? 'badge-active' : 'badge-cancelled' ?>"><i></i><?= $user['is_active'] ? 'Aktif' : 'Dibekukan' ?></span></div>
                    <small class="field-help">Status akun dapat diubah dari halaman daftar pengguna.</small>
                </div>
            </div>
        </div>

        <div class="form-actions-sticky">
            <div class="form-security-note"><?= ui_icon('shield') ?><span>Mengedit profil tidak mengubah password pengguna.</span></div>
            <div><a class="btn btn-secondary" href="/admin/users">Batal</a><button class="btn btn-primary" type="submit"><?= ui_icon('check') ?><span>Simpan Perubahan</span></button></div>
        </div>
    </form>
</section>
<?php unset($_SESSION['old']); ?>
