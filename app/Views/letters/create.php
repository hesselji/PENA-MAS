<?php
use App\Core\Auth;
$errs = $errors ?? [];
$oldData = $_SESSION['old'] ?? [];
$isAdmin = Auth::isAdmin();
$recipientCandidates = $recipientCandidates ?? [];
$selectedRecipientIds = array_map('intval', is_array($oldData['selected_user_ids'] ?? null) ? $oldData['selected_user_ids'] : []);
$recipientRoleLabels = ['ADMIN' => 'Administrator', 'USER' => 'Pengguna Surat Keluar', 'INCOMING' => 'Petugas Surat Masuk'];
?>
<section class="page-heading">
    <div>
        <a class="breadcrumb" href="/letters"><?= ui_icon('arrow-left') ?> Agenda Surat Keluar</a>
        <span class="eyebrow">BUAT NOMOR SURAT</span>
        <h1>Buat nomor surat keluar</h1>
        <p>Lengkapi informasi surat. Nomor urut untuk kode unit 62710 dan 62711 dikelola terpisah sesuai jenis surat.</p>
    </div>
    <div class="heading-hint"><span><?= ui_icon('shield') ?></span><div><strong>Powered By Sequence Aman</strong><small>Sistem memastikan setiap nomor tersimpan dengan urutan yang benar dan tidak ganda.</small></div></div>
</section>

<div class="create-layout">
    <section class="panel form-panel">
        <form method="post" action="/letters" id="letterForm" class="modern-form" data-loading-form>
            <?= csrf_field() ?>

            <div class="form-section">
                <div class="form-section-head"><span class="section-step">01</span><div><h2>Informasi Surat</h2><p>Pilih jenis surat, tim kerja, sistem, dan tanggal surat.</p></div></div>
                <div class="form-grid form-grid-2">
                    <label class="field">
                        <span>Jenis Surat <i>*</i></span>
                        <select name="letter_type_id" id="letterType" required>
                            <option value="">Pilih jenis surat</option>
                            <?php foreach ($types as $t): ?>
                                <?php $dateMode = $t['rule_code']==='MAIN_62710' ? 'NO_PAST' : 'ANY'; ?>
                                <option value="<?= (int)$t['id'] ?>"
                                    data-unit="<?= e($t['unit_code']) ?>"
                                    data-pattern="<?= e($t['pattern']) ?>"
                                    data-rule="<?= e($t['rule_code']) ?>"
                                    data-date-mode="<?= e($dateMode) ?>"
                                    <?= ((string)($oldData['letter_type_id'] ?? '') === (string)$t['id']) ? 'selected' : '' ?>>
                                    <?= e($t['name']) ?> — <?= e($t['unit_code']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-help" id="dateRuleHelp">Pilih jenis surat untuk melihat ketentuan tanggal dan penomoran.</small>
                        <?php if (!empty($errs['letter_type_id'])): ?><small class="field-error"><?= e($errs['letter_type_id']) ?></small><?php endif; ?>
                    </label>

                    <label class="field">
                        <span>Tim Kerja <i>*</i></span>
                        <select name="work_team_id" required>
                            <option value="">Pilih tim kerja</option>
                            <?php foreach ($teams as $t): ?><option value="<?= (int)$t['id'] ?>" <?= ((string)($oldData['work_team_id'] ?? '') === (string)$t['id']) ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
                        </select>
                        <?php if (!empty($errs['work_team_id'])): ?><small class="field-error"><?= e($errs['work_team_id']) ?></small><?php endif; ?>
                    </label>

                    <label class="field">
                        <span>Sistem <i>*</i></span>
                        <select name="system_type" required>
                            <option value="">Pilih sistem</option>
                            <option value="SRIKANDI" <?= ($oldData['system_type'] ?? '') === 'SRIKANDI' ? 'selected' : '' ?>>SRIKANDI</option>
                            <option value="NON_SRIKANDI" <?= ($oldData['system_type'] ?? '') === 'NON_SRIKANDI' ? 'selected' : '' ?>>Non Srikandi</option>
                        </select>
                        <?php if (!empty($errs['system_type'])): ?><small class="field-error"><?= e($errs['system_type']) ?></small><?php endif; ?>
                    </label>

                    <label class="field">
                        <span>Tanggal Surat <i>*</i></span>
                        <div class="field-icon-wrap"><?= ui_icon('calendar') ?><input type="date" name="letter_date" id="letterDate" value="<?= e($oldData['letter_date'] ?? date('Y-m-d')) ?>" required></div>
                        <?php if (!empty($errs['letter_date'])): ?><small class="field-error"><?= e($errs['letter_date']) ?></small><?php endif; ?>
                    </label>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-head"><span class="section-step orange">02</span><div><h2>Aturan & Klasifikasi</h2><p>Anggaran akan otomatis mengarahkan arsip ke Fasilitatif dan kelompok KU.</p></div></div>
                <div class="form-grid form-grid-2">
                    <?php if ($isAdmin): ?>
                        <label class="field">
                            <span>Sifat Surat <i>*</i></span>
                            <select name="sensitivity" id="sensitivity" required>
                                <option value="">Pilih sifat surat</option>
                                <?php foreach ($sensitivities as $s): ?>
                                    <option value="<?= e($s['code']) ?>" data-prefix="<?= e($s['prefix']) ?>" <?= ($oldData['sensitivity'] ?? '') === $s['code'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="field-help">Rahasia dan Sangat Rahasia hanya dapat dibuat oleh Administrator.</small>
                            <?php if (!empty($errs['sensitivity'])): ?><small class="field-error"><?= e($errs['sensitivity']) ?></small><?php endif; ?>
                        </label>
                    <?php else: ?>
                        <label class="field field-locked">
                            <span>Sifat Surat <i>*</i></span>
                            <input type="hidden" name="sensitivity" value="BIASA">
                            <input value="Biasa" readonly aria-readonly="true">
                            <small class="field-help">Akun pengguna hanya dapat membuat surat dengan sifat Biasa.</small>
                        </label>
                        <select id="sensitivity" hidden><option value="BIASA" data-prefix="B" selected>Biasa</option></select>
                    <?php endif; ?>

                    <label class="field">
                        <span>Ada Anggaran? <i>*</i></span>
                        <select name="uses_budget" id="usesBudget" required>
                            <option value="">Pilih kondisi</option>
                            <option value="Y" <?= ($oldData['uses_budget'] ?? '') === 'Y' ? 'selected' : '' ?>>Ya</option>
                            <option value="T" <?= ($oldData['uses_budget'] ?? '') === 'T' ? 'selected' : '' ?>>Tidak</option>
                        </select>
                        <small class="field-help">Jika Ya: Jenis Arsip = Fasilitatif dan Kelompok KKA = KU secara otomatis.</small>
                    </label>

                    <label class="field" id="archiveField">
                        <span>Jenis Arsip <i>*</i></span>
                        <select name="archive_type" id="archiveType" required>
                            <option value="">Pilih jenis arsip</option>
                            <?php foreach ($archiveTypes as $a): ?><option value="<?= e($a['code']) ?>" <?= ($oldData['archive_type'] ?? '') === $a['code'] ? 'selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?>
                        </select>
                        <input type="hidden" name="archive_type" id="archiveTypeMirror" value="FASILITATIF" disabled>
                        <small class="field-help" id="archiveLockHelp">Jenis arsip menentukan kelompok KKA yang tersedia.</small>
                        <?php if (!empty($errs['archive_type'])): ?><small class="field-error"><?= e($errs['archive_type']) ?></small><?php endif; ?>
                    </label>

                    <div class="scope-card">
                        <span>Klasifikasi Arsip</span>
                        <strong id="scopePreview">—</strong>
                        <small id="scopeDescription">Kelompok KKA ditentukan oleh Jenis Arsip.</small>
                    </div>

                    <div class="field span-all kka-search-field">
                        <span>Cari Kode / Nama Klasifikasi</span>
                        <div class="toolbar-search kka-search-box"><?= ui_icon('search') ?><input type="search" id="classificationSearch" placeholder="Contoh: pajak, perjalanan dinas, kepegawaian..." autocomplete="off"></div>
                        <div class="kka-search-results" id="classificationSearchResults" hidden></div>
                        <small class="field-help">Ketik minimal 2 karakter, lalu pilih hasil yang sesuai.</small>
                    </div>

                    <label class="field" id="parentField">
                        <span>Kelompok Klasifikasi (KKA) <i>*</i></span>
                        <select name="classification_parent" id="classificationParent" data-old="<?= e($oldData['classification_parent'] ?? '') ?>" required disabled><option value="">Pilih jenis arsip dahulu</option></select>
                        <input type="hidden" name="classification_parent" id="classificationParentMirror" value="KU" disabled>
                        <small class="field-help" id="parentLockHelp">Pilih kelompok KKA.</small>
                        <?php if (!empty($errs['classification_parent'])): ?><small class="field-error"><?= e($errs['classification_parent']) ?></small><?php endif; ?>
                    </label>

                    <label class="field">
                        <span>Kode Klasifikasi <i>*</i></span>
                        <select name="classification_child" id="classificationChild" data-old="<?= e($oldData['classification_child'] ?? '') ?>" required disabled><option value="">Pilih kelompok KKA dahulu</option></select>
                        <?php if (!empty($errs['classification_child'])): ?><small class="field-error"><?= e($errs['classification_child']) ?></small><?php endif; ?>
                    </label>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-head"><span class="section-step green">03</span><div><h2>Tujuan & Perihal</h2><p>Satu data surat dapat digunakan untuk satu tujuan atau banyak tujuan sekaligus. Setiap tujuan akan memperoleh nomor surat yang berbeda.</p></div></div>

                <div class="form-grid form-grid-2">
                    <label class="field span-all"><span>Perihal <i>*</i></span><input name="subject" value="<?= old('subject') ?>" placeholder="Perihal / keperluan surat yang sama untuk seluruh tujuan" required><?php if (!empty($errs['subject'])): ?><small class="field-error"><?= e($errs['subject']) ?></small><?php endif; ?></label>
                </div>

                <div class="recipient-builder" id="recipientBuilder">
                    <div class="recipient-builder-head">
                        <div><strong>Daftar Tujuan Surat</strong><small>Kamu boleh memakai satu atau beberapa cara di bawah. Nama yang sama otomatis tidak digandakan.</small></div>
                        <div class="recipient-count"><span id="recipientCount">0</span><small>tujuan</small></div>
                    </div>

                    <?php if (!empty($errs['recipients'])): ?><div class="recipient-error"><?= e($errs['recipients']) ?></div><?php endif; ?>

                    <div class="recipient-methods">
                        <section class="recipient-method-card">
                            <div class="recipient-method-title"><span>1</span><div><strong>Ketik satu tujuan</strong><small>Untuk surat tunggal atau menambahkan satu nama manual.</small></div></div>
                            <label class="field">
                                <span>Tujuan manual</span>
                                <input name="recipient" id="recipientSingle" value="<?= old('recipient') ?>" placeholder="Contoh: Hessel Josef Imanuel / Kepala Dinas ..." autocomplete="off">
                            </label>
                        </section>

                        <section class="recipient-method-card">
                            <div class="recipient-method-title"><span>2</span><div><strong>Paste banyak nama</strong><small>Satu nama per baris. Cocok untuk 20, 45, hingga 100 orang.</small></div></div>
                            <label class="field">
                                <span>Daftar nama</span>
                                <textarea name="recipients_bulk" id="recipientsBulk" rows="7" placeholder="Andi Saputra&#10;Budi Santoso&#10;Citra Wijaya"><?= e($oldData['recipients_bulk'] ?? '') ?></textarea>
                            </label>
                            <small class="field-help">List bernomor seperti “1. Andi” juga dapat ditempel; nomor urut akan dibersihkan saat disimpan.</small>
                        </section>

                        <section class="recipient-method-card recipient-account-card span-all">
                            <div class="recipient-method-title"><span>3</span><div><strong>Pilih dari akun aktif PENA MAS</strong><small>Shortcut untuk nama yang sudah memiliki akun. Ini bukan master pegawai resmi, jadi tujuan lain tetap bisa diketik manual.</small></div></div>

                            <?php if ($recipientCandidates): ?>
                                <div class="recipient-picker-toolbar">
                                    <div class="toolbar-search recipient-user-search"><?= ui_icon('search') ?><input type="search" id="recipientUserSearch" placeholder="Cari nama, email, atau tim kerja..." autocomplete="off"></div>
                                    <button class="btn btn-secondary btn-sm" type="button" id="selectAllRecipientUsers">Pilih Semua Hasil</button>
                                    <button class="btn btn-ghost btn-sm" type="button" id="clearRecipientUsers">Kosongkan</button>
                                </div>
                                <div class="recipient-user-list" id="recipientUserList">
                                    <?php foreach ($recipientCandidates as $candidate): ?>
                                        <?php
                                            $searchText = strtolower(trim(($candidate['name'] ?? '').' '.($candidate['email'] ?? '').' '.($candidate['work_team_name'] ?? '')));
                                            $checked = in_array((int)$candidate['id'],$selectedRecipientIds,true);
                                        ?>
                                        <label class="recipient-user-item" data-recipient-user data-search="<?= e($searchText) ?>">
                                            <input type="checkbox" name="selected_user_ids[]" value="<?= (int)$candidate['id'] ?>" data-recipient-checkbox data-recipient-name="<?= e($candidate['name']) ?>" <?= $checked ? 'checked' : '' ?>>
                                            <span class="avatar avatar-sm"><?= e(user_initials($candidate['name'])) ?></span>
                                            <span class="recipient-user-copy"><strong><?= e($candidate['name']) ?></strong><small><?= e($candidate['work_team_name'] ?? 'Tanpa tim') ?> - <?= e($candidate['email']) ?></small></span>
                                            <span class="role-badge role-<?= strtolower(e($candidate['role'])) ?>"><?= e($recipientRoleLabels[$candidate['role']] ?? $candidate['role']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="recipient-picker-footer"><span id="recipientPickerVisibleCount"><?= count($recipientCandidates) ?></span> akun ditampilkan - tombol “Pilih Semua Hasil” hanya memilih hasil pencarian yang sedang terlihat.</div>
                            <?php else: ?>
                                <div class="empty-inline">Belum ada akun aktif yang dapat dipilih. Gunakan input manual atau paste banyak nama.</div>
                            <?php endif; ?>
                        </section>
                    </div>
                </div>

                <div class="form-grid form-grid-2 recipient-common-fields">
                    <label class="field span-all"><span>Catatan Tambahan</span><textarea name="notes" rows="4" placeholder="Catatan yang sama untuk seluruh surat yang dibuat sekaligus..."><?= old('notes') ?></textarea></label>
                </div>
            </div>

            <div class="form-actions-sticky">
                <div class="form-security-note"><?= ui_icon('lock') ?><span>Sistem memeriksa kembali data surat sebelum nomor disimpan.</span></div>
                <div><a class="btn btn-secondary" href="/letters">Batal</a><button class="btn btn-primary btn-lg" type="submit"><?= ui_icon('mail-plus') ?><span id="generateButtonLabel">Buat & Simpan Nomor</span></button></div>
            </div>
        </form>
    </section>

    <aside class="create-aside">
        <section class="panel preview-panel sticky-panel">
            <span class="eyebrow">PRATINJAU NOMOR</span>
            <h3>Perkiraan format nomor</h3>
            <p id="rulePreviewText">Pilih jenis surat untuk melihat bentuk nomor yang akan digunakan.</p>
            <div class="number-preview dynamic-number-preview" id="numberPreview">?-???/62710/KKA.000/<?= date('Y') ?></div>
            <div class="sequence-rule-card" id="sequenceRuleCard"><strong>Urutan Nomor</strong><span>Pilih jenis surat.</span></div>
            <div class="preview-legend">
                <div><i class="blue"></i><span>Awalan dan nomor urut</span></div>
                <div><i class="green"></i><span>Kode unit</span></div>
                <div><i class="orange"></i><span>Klasifikasi KKA</span></div>
            </div>
            <div class="preview-info"><span><?= ui_icon('info') ?></span><p>Kode unit 62710 dan 62711 memiliki urutan nomor masing-masing sehingga nomor tetap tertib dan tidak ganda.</p></div>
        </section>
    </aside>
</div>
