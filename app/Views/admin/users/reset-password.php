<?php
$roleLabels = ['ADMIN' => 'Administrator', 'USER' => 'Pengguna Surat Keluar', 'INCOMING' => 'Petugas Surat Masuk'];
?>
<section class="page-heading">
    <div><a class="breadcrumb" href="/admin/users"><?= ui_icon('arrow-left') ?> Pengguna</a><span class="eyebrow">KEAMANAN AKUN</span><h1>Atur ulang password pengguna</h1><p>Atur password baru untuk <?= e(display_user_name($user['name'])) ?> tanpa mengetahui password lamanya.</p></div>
</section>

<section class="panel form-panel form-panel-narrow">
    <form method="post" action="/admin/users/<?= (int)$user['id'] ?>/reset-password" class="modern-form" data-loading-form autocomplete="off">
        <?= csrf_field() ?>
        <div class="form-section no-border">
            <div class="form-section-head"><span class="section-step orange"><?= ui_icon('lock') ?></span><div><h2>Password Baru</h2><p><?= e($user['email']) ?> - <?= e($roleLabels[$user['role']] ?? $user['role']) ?></p></div></div>
            <div class="form-grid">
                <label class="field"><span>Password Baru <i>*</i></span><div class="password-field-shell"><input id="adminResetPassword" type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password" placeholder="Minimal 8 karakter"><button class="password-eye" type="button" data-password-toggle="#adminResetPassword"><?= ui_icon('eye') ?></button></div><?php if(!empty($errors['password'])):?><small class="field-error"><?= e($errors['password']) ?></small><?php endif;?></label>
                <label class="field"><span>Konfirmasi Password <i>*</i></span><div class="password-field-shell"><input id="adminResetConfirm" type="password" name="password_confirmation" required minlength="8" maxlength="72" autocomplete="new-password" placeholder="Ketik ulang password"><button class="password-eye" type="button" data-password-toggle="#adminResetConfirm"><?= ui_icon('eye') ?></button></div><?php if(!empty($errors['password_confirmation'])):?><small class="field-error"><?= e($errors['password_confirmation']) ?></small><?php endif;?></label>
            </div>
        </div>
        <div class="form-actions-sticky"><div class="form-security-note"><?= ui_icon('shield') ?><span>Password baru tidak disimpan dalam riwayat. Sistem hanya mencatat bahwa perubahan password dilakukan.</span></div><div><a class="btn btn-secondary" href="/admin/users">Batal</a><button class="btn btn-primary" type="submit"><?= ui_icon('lock') ?><span>Atur Ulang Password</span></button></div></div>
    </form>
</section>
