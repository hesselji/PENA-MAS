<section class="page-heading">
    <div>
        <span class="eyebrow">SURAT MASUK</span>
        <h1>Agenda surat masuk</h1>
        <p>Catat dan telusuri surat yang diterima dengan mudah.</p>
    </div>
    <a class="btn btn-primary" href="/incoming/create"><?= ui_icon('plus') ?><span>Tambah Surat Masuk</span></a>
</section>

<section class="panel agenda-panel">
    <form method="get" class="agenda-toolbar">
        <div class="toolbar-search"><?= ui_icon('search') ?><input name="q" value="<?= e($filters['q']) ?>" placeholder="Cari nomor, asal, perihal, kepada..."></div>
        <label class="incoming-year-filter"><span>Tahun</span><input name="year" value="<?= e($filters['year']) ?>" inputmode="numeric" placeholder="2026"></label>
        <div class="incoming-filter-actions">

            <button
                type="submit"
                class="btn btn-primary incoming-search-btn">
                Cari
            </button>

            <a
                href="/incoming"
                class="incoming-reset-btn"
                title="Reset pencarian"
                aria-label="Reset pencarian">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true">
                    <path d="M3 12a9 9 0 1 0 3-6.7" />
                    <path d="M3 4v6h6" />
                </svg>
            </a>

        </div>

    </form>

    <div class="agenda-summary-row">
        <div><span class="summary-dot green"></span><b><?= count($letters) ?></b><span>surat masuk ditampilkan</span></div>
    </div>

    <div class="data-table-wrap">
        <table class="data-table incoming-table">
            <thead>
                <tr>
                    <th>Nomor Surat</th>
                    <th>Tanggal Surat</th>
                    <th>Diterima</th>
                    <th>Asal</th>
                    <th>Perihal & Kepada</th>
                    <th>Dicatat Oleh</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$letters): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state"><span><?= ui_icon('mail') ?></span>
                                <h3>Belum ada surat masuk</h3>
                                <p>Tambahkan surat masuk pertama untuk mengisi agenda.</p><a class="btn btn-primary" href="/incoming/create">Tambah Surat Masuk</a>
                            </div>
                        </td>
                    </tr>
                    <?php else: foreach ($letters as $r): ?>
                        <tr>
                            <td><a class="number-link" href="/incoming/<?= (int)$r['id'] ?>"><?= e($r['letter_number']) ?></a></td>
                            <td><?= e(format_date_id($r['letter_date'])) ?></td>
                            <td><strong><?= e(format_date_id($r['received_date'])) ?></strong><small><?= e(format_date_id($r['recorded_at'], true)) ?></small></td>
                            <td><span class="team-chip"><?= e($r['origin']) ?></span></td>
                            <td><strong class="cell-title"><?= e($r['subject']) ?></strong><small><?= e($r['recipient']) ?></small></td>
                            <td><?= e(display_user_name($r['creator_name'])) ?></td>
                            <td class="text-right"><a class="icon-btn row-action" href="/incoming/<?= (int)$r['id'] ?>" title="Lihat detail"><?= ui_icon('eye') ?></a></td>
                        </tr>
                <?php endforeach;
                endif; ?>
            </tbody>
        </table>
    </div>
</section>