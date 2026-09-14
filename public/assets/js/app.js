(() => {
  'use strict';

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const escapeHtml = (v) => String(v ?? '').replace(/[&<>'"]/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));

  // Theme
  const root = document.documentElement;
  const storedTheme = localStorage.getItem('penamas-theme');
  if (storedTheme === 'light' || storedTheme === 'dark') root.dataset.theme = storedTheme;
  const themeToggle = $('[data-theme-toggle]');
  const themeIcon = $('[data-theme-icon]');
  const iconMoon = '<svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/></svg>';
  const iconSun = '<svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/></svg>';
  const syncThemeIcon = () => { if (themeIcon) themeIcon.innerHTML = root.dataset.theme === 'light' ? iconSun : iconMoon; };
  syncThemeIcon();
  themeToggle?.addEventListener('click', () => {
    root.dataset.theme = root.dataset.theme === 'light' ? 'dark' : 'light';
    localStorage.setItem('penamas-theme', root.dataset.theme);
    syncThemeIcon();
  });

  // Responsive sidebar
  const sidebar = $('[data-sidebar]');
  const overlay = $('[data-sidebar-overlay]');
  const setSidebar = (open) => {
    sidebar?.classList.toggle('open', open);
    overlay?.classList.toggle('open', open);
    document.body.style.overflow = open ? 'hidden' : '';
  };
  $('[data-sidebar-open]')?.addEventListener('click', () => setSidebar(true));
  $('[data-sidebar-close]')?.addEventListener('click', () => setSidebar(false));
  overlay?.addEventListener('click', () => setSidebar(false));
  window.addEventListener('resize', () => { if (window.innerWidth > 960) setSidebar(false); });

  // Global search shortcut
  const globalSearchInput = $('.global-search input');
  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k' && globalSearchInput) {
      e.preventDefault(); globalSearchInput.focus(); globalSearchInput.select();
    }
  });

  // Generic password visibility (login + account/admin forms)
  const togglePassword = (button) => {
    const selector = button.dataset.passwordToggle;
    const input = selector ? $(selector) : null;
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    button.classList.toggle('is-visible', input.type === 'text');
    button.setAttribute('aria-label', input.type === 'text' ? 'Sembunyikan password' : 'Tampilkan password');
  };
  $$('[data-password-toggle]').forEach((button) => button.addEventListener('click', () => togglePassword(button)));
  const legacyPassword = $('#passwordInput');
  $('#passwordToggle')?.addEventListener('click', () => {
    if (!legacyPassword) return;
    legacyPassword.type = legacyPassword.type === 'password' ? 'text' : 'password';
  });

  // Flash toast
  $$('[data-toast]').forEach((toast) => {
    const hide = () => { toast.classList.add('is-hiding'); setTimeout(() => toast.remove(), 260); };
    $('[data-toast-close]', toast)?.addEventListener('click', hide);
    setTimeout(hide, 6500);
  });

  // Filters
  const filterPanel = $('[data-filter-panel]');
  const filterToggle = $('[data-filter-toggle]');
  if (filterPanel && filterToggle) {
    const hasValue = $$('select,input', filterPanel).some((el) => el.value && el.value !== new Date().getFullYear().toString());
    filterPanel.hidden = !hasValue;
    filterToggle.addEventListener('click', () => { filterPanel.hidden = !filterPanel.hidden; });
  }

  // Confirmation
  $$('[data-confirm]').forEach((el) => {
    const eventName = el.tagName === 'FORM' ? 'submit' : 'click';
    el.addEventListener(eventName, (event) => {
      const message = el.dataset.confirm || 'Lanjutkan tindakan ini?';
      if (!window.confirm(message)) event.preventDefault();
    });
  });

  // Loading states
  $$('[data-loading-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (event.defaultPrevented || !form.checkValidity()) return;
      form.classList.add('is-loading');
      const submit = $('button[type="submit"]', form);
      if (submit) submit.setAttribute('aria-busy', 'true');
    });
  });

  // Copy
  $$('[data-copy-target]').forEach((button) => {
    button.addEventListener('click', async () => {
      const target = $(button.dataset.copyTarget);
      if (!target) return;
      const value = target.textContent.trim();
      try { await navigator.clipboard.writeText(value); }
      catch (_) {
        const ta = document.createElement('textarea'); ta.value = value; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove();
      }
      const tip = document.createElement('div'); tip.className = 'copy-feedback'; tip.textContent = 'Nomor disalin'; document.body.appendChild(tip); setTimeout(() => tip.remove(), 1800);
    });
  });

  // =====================================================================
  // GENERATE SURAT KELUAR
  // =====================================================================
  const budget = $('#usesBudget');
  const archive = $('#archiveType');
  const archiveMirror = $('#archiveTypeMirror');
  const parent = $('#classificationParent');
  const parentMirror = $('#classificationParentMirror');
  const child = $('#classificationChild');
  const scopePreview = $('#scopePreview');
  const scopeDescription = $('#scopeDescription');
  const typeSelect = $('#letterType');
  const sensitivity = $('#sensitivity');
  const letterDate = $('#letterDate');
  const numberPreview = $('#numberPreview');
  const rulePreviewText = $('#rulePreviewText');
  const dateRuleHelp = $('#dateRuleHelp');
  const sequenceRuleCard = $('#sequenceRuleCard');
  const archiveField = $('#archiveField');
  const parentField = $('#parentField');
  const archiveLockHelp = $('#archiveLockHelp');
  const parentLockHelp = $('#parentLockHelp');
  const classificationSearch = $('#classificationSearch');
  const classificationSearchResults = $('#classificationSearchResults');

  if (archive && parent && child) {
    const fill = (select, rows, placeholder, selected = '') => {
      select.innerHTML = `<option value="">${escapeHtml(placeholder)}</option>` + rows.map((r) => {
        const description = r.description ? ` data-description="${escapeHtml(r.description)}"` : '';
        return `<option value="${escapeHtml(r.code)}"${description}${String(r.code) === String(selected) ? ' selected' : ''}>${escapeHtml(r.code)} — ${escapeHtml(r.name)}</option>`;
      }).join('');
      select.disabled = false;
    };

    const isBudgetLocked = () => budget?.value === 'Y';

    const syncLockedInputs = () => {
      const locked = isBudgetLocked();
      if (archiveMirror) archiveMirror.disabled = !locked;
      if (parentMirror) parentMirror.disabled = !locked;
      if (locked) {
        archive.value = 'FASILITATIF';
        if (archiveMirror) archiveMirror.value = 'FASILITATIF';
        archive.disabled = true;
        archiveField?.classList.add('is-auto-locked');
        if (archiveLockHelp) archiveLockHelp.textContent = 'Otomatis Fasilitatif karena surat menggunakan anggaran.';
      } else {
        archive.disabled = false;
        archiveField?.classList.remove('is-auto-locked');
        if (archiveLockHelp) archiveLockHelp.textContent = 'Jenis arsip menentukan kelompok KKA yang tersedia.';
      }
    };

    const previewTokens = () => {
      const typeOption = typeSelect?.selectedOptions?.[0];
      const sensOption = sensitivity?.selectedOptions?.[0];
      const prefix = sensOption?.dataset?.prefix || 'B';
      const unit = typeOption?.dataset?.unit || '62710';
      const group = (isBudgetLocked() ? 'KU' : parent.value) || 'KKA';
      const code = child.value || '000';
      let year = new Date().getFullYear().toString();
      if (letterDate?.value) year = letterDate.value.slice(0, 4);
      return {'{PREFIX}':prefix,'{SEQ}':'???','{SEQ3}':'???','{SEQ4}':'????','{UNIT}':unit,'{KKA}':`${group}.${code}`,'{YEAR}':year};
    };

    const refreshNumberPreview = () => {
      if (!numberPreview) return;
      const typeOption = typeSelect?.selectedOptions?.[0];
      const pattern = typeOption?.dataset?.pattern || '{PREFIX}-{SEQ3}/{UNIT}/{KKA}/{YEAR}';
      const rule = typeOption?.dataset?.rule || '—';
      const unit = typeOption?.dataset?.unit || '—';
      let preview = pattern;
      Object.entries(previewTokens()).forEach(([token, value]) => { preview = preview.split(token).join(value); });
      numberPreview.textContent = preview;
      if (rulePreviewText) rulePreviewText.textContent = typeOption?.value ? `Rule aktif: ${rule} • ${pattern}` : 'Pilih jenis surat untuk melihat pattern yang digunakan.';
      if (sequenceRuleCard) sequenceRuleCard.innerHTML = typeOption?.value
        ? `<strong>Jalur sequence</strong><span>${escapeHtml(unit)} mempunyai urutan independen per tahun.</span>`
        : '<strong>Jalur sequence</strong><span>Pilih jenis surat.</span>';
    };

    const syncDateRule = () => {
      if (!letterDate) return;
      const option = typeSelect?.selectedOptions?.[0];
      const mode = option?.dataset?.dateMode || 'ANY';
      const unit = option?.dataset?.unit || '';
      const today = new Date();
      const yyyy = today.getFullYear();
      const mm = String(today.getMonth()+1).padStart(2,'0');
      const dd = String(today.getDate()).padStart(2,'0');
      const todayValue = `${yyyy}-${mm}-${dd}`;
      if (mode === 'NO_PAST') {
        letterDate.min = todayValue;
        if (letterDate.value && letterDate.value < todayValue) letterDate.value = todayValue;
        if (dateRuleHelp) dateRuleHelp.textContent = `Jalur ${unit || '62710'}: tanggal sebelum hari ini (H-) tidak diperbolehkan.`;
      } else {
        letterDate.removeAttribute('min');
        if (dateRuleHelp) dateRuleHelp.textContent = option?.value ? `Jalur ${unit}: tanggal surat dapat dipilih sesuai kebutuhan pencatatan.` : 'Pilih jenis surat untuk melihat aturan tanggal dan jalur sequence.';
      }
      refreshNumberPreview();
    };

    async function loadItems(groupCode, selected = '') {
      child.disabled = true;
      child.innerHTML = '<option value="">Memuat...</option>';
      refreshNumberPreview();
      if (!groupCode) { child.innerHTML = '<option value="">Pilih kelompok KKA dahulu</option>'; return; }
      try {
        const res = await fetch(`/api/classification-items?group=${encodeURIComponent(groupCode)}`, {headers:{'Accept':'application/json'}});
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Gagal memuat kode klasifikasi');
        fill(child, json.data, 'Pilih kode klasifikasi', selected);
        refreshNumberPreview();
      } catch (_) {
        child.innerHTML = '<option value="">Gagal memuat data</option>';
      }
    }

    async function loadGroups(restore = false, forcedParent = '', forcedChild = '') {
      const archiveType = isBudgetLocked() ? 'FASILITATIF' : archive.value;
      if (scopePreview) scopePreview.textContent = archiveType || '—';
      if (scopeDescription) scopeDescription.textContent = isBudgetLocked() ? 'Anggaran aktif: Fasilitatif + KU dikunci otomatis.' : 'Kelompok KKA ditentukan oleh Jenis Arsip.';
      parent.disabled = true;
      child.disabled = true;
      parent.innerHTML = '<option value="">Pilih jenis arsip dahulu</option>';
      child.innerHTML = '<option value="">Pilih kelompok KKA dahulu</option>';
      refreshNumberPreview();
      if (!archiveType) return;

      try {
        const res = await fetch(`/api/classification-groups?archive_type=${encodeURIComponent(archiveType)}`, {headers:{'Accept':'application/json'}});
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Gagal memuat kelompok klasifikasi');
        const selectedParent = forcedParent || (restore ? (parent.dataset.old || '') : '') || (isBudgetLocked() ? 'KU' : '');
        fill(parent, json.data, 'Pilih kelompok KKA', selectedParent);

        if (isBudgetLocked()) {
          parent.value = 'KU';
          parent.disabled = true;
          if (parentMirror) { parentMirror.disabled = false; parentMirror.value = 'KU'; }
          parentField?.classList.add('is-auto-locked');
          if (parentLockHelp) parentLockHelp.textContent = 'Otomatis KU karena surat menggunakan anggaran.';
        } else {
          if (parentMirror) parentMirror.disabled = true;
          parent.disabled = false;
          parentField?.classList.remove('is-auto-locked');
          if (parentLockHelp) parentLockHelp.textContent = 'Pilih kelompok KKA.';
        }

        const parentValue = isBudgetLocked() ? 'KU' : selectedParent;
        if (parentValue) {
          const selectedChild = forcedChild || (restore ? (child.dataset.old || '') : '');
          await loadItems(parentValue, selectedChild);
        }
        if (restore) { parent.dataset.old=''; child.dataset.old=''; }
      } catch (_) {
        parent.innerHTML = '<option value="">Gagal memuat data</option>';
      }
      refreshNumberPreview();
    }

    async function applyBudgetRules(restore = false) {
      syncLockedInputs();
      if (isBudgetLocked()) {
        archive.value = 'FASILITATIF';
        await loadGroups(restore, 'KU', restore ? (child.dataset.old || '') : '');
      } else if (archive.value) {
        await loadGroups(restore);
      } else {
        parent.disabled = true;
        child.disabled = true;
        parent.innerHTML = '<option value="">Pilih jenis arsip dahulu</option>';
        child.innerHTML = '<option value="">Pilih kelompok KKA dahulu</option>';
      }
      refreshNumberPreview();
    }

    budget?.addEventListener('change', () => applyBudgetRules(false));
    archive.addEventListener('change', () => loadGroups(false));
    parent.addEventListener('change', () => loadItems(parent.value));
    child.addEventListener('change', refreshNumberPreview);
    typeSelect?.addEventListener('change', () => { syncDateRule(); refreshNumberPreview(); });
    sensitivity?.addEventListener('change', refreshNumberPreview);
    letterDate?.addEventListener('change', refreshNumberPreview);

    // KKA keyword search
    let searchTimer = null;
    classificationSearch?.addEventListener('input', () => {
      clearTimeout(searchTimer);
      const q = classificationSearch.value.trim();
      if (!classificationSearchResults) return;
      if (q.length < 2) { classificationSearchResults.hidden = true; classificationSearchResults.innerHTML = ''; return; }
      searchTimer = setTimeout(async () => {
        classificationSearchResults.hidden = false;
        classificationSearchResults.innerHTML = '<div class="kka-result-status">Mencari klasifikasi...</div>';
        const params = new URLSearchParams({q});
        if (isBudgetLocked()) {
          params.set('archive_type','FASILITATIF'); params.set('group','KU');
        } else if (archive.value) {
          params.set('archive_type',archive.value);
        }
        try {
          const res = await fetch(`/api/classification-search?${params.toString()}`, {headers:{'Accept':'application/json'}});
          const json = await res.json();
          if (!json.success) throw new Error('Gagal mencari');
          if (!json.data.length) {
            classificationSearchResults.innerHTML = '<div class="kka-result-status">Tidak ada klasifikasi yang cocok.</div>';
            return;
          }
          classificationSearchResults.innerHTML = json.data.map((r) => `
            <button type="button" class="kka-result-item" data-archive="${escapeHtml(r.archive_type)}" data-group="${escapeHtml(r.group_code)}" data-code="${escapeHtml(r.code)}">
              <span><strong>${escapeHtml(r.group_code)}.${escapeHtml(r.code)}</strong><em>${escapeHtml(r.archive_type)}</em></span>
              <b>${escapeHtml(r.name)}</b>
              <small>${escapeHtml(r.group_name)}</small>
            </button>`).join('');
        } catch (_) {
          classificationSearchResults.innerHTML = '<div class="kka-result-status">Gagal mengambil hasil pencarian.</div>';
        }
      }, 280);
    });

    classificationSearchResults?.addEventListener('click', async (e) => {
      const button = e.target.closest('.kka-result-item');
      if (!button) return;
      const selectedArchive = button.dataset.archive || '';
      const selectedGroup = button.dataset.group || '';
      const selectedCode = button.dataset.code || '';
      if (!isBudgetLocked()) archive.value = selectedArchive;
      await loadGroups(false, selectedGroup, selectedCode);
      if (!isBudgetLocked()) parent.value = selectedGroup;
      child.value = selectedCode;
      classificationSearch.value = `${selectedGroup}.${selectedCode} — ${$('b', button)?.textContent || ''}`;
      classificationSearchResults.hidden = true;
      refreshNumberPreview();
    });

    document.addEventListener('click', (e) => {
      if (classificationSearchResults && classificationSearch && !classificationSearchResults.contains(e.target) && e.target !== classificationSearch) {
        classificationSearchResults.hidden = true;
      }
    });

    syncDateRule();
    syncLockedInputs();
    if (budget?.value === 'Y' || archive.value) applyBudgetRules(true); else refreshNumberPreview();
  }

  // =====================================================================
  // BATCH TUJUAN SURAT
  // =====================================================================
  const recipientBuilder = $('#recipientBuilder');
  if (recipientBuilder) {
    const recipientSingle = $('#recipientSingle');
    const recipientsBulk = $('#recipientsBulk');
    const recipientUserSearch = $('#recipientUserSearch');
    const recipientUserItems = $$('[data-recipient-user]', recipientBuilder);
    const recipientCheckboxes = $$('[data-recipient-checkbox]', recipientBuilder);
    const recipientCount = $('#recipientCount');
    const visibleCount = $('#recipientPickerVisibleCount');
    const selectAll = $('#selectAllRecipientUsers');
    const clearAll = $('#clearRecipientUsers');
    const generateLabel = $('#generateButtonLabel');
    const letterForm = $('#letterForm');

    const cleanRecipient = (value) => String(value || '')
      .trim()
      .replace(/^\s*\d+\s*[.\-)]\s*/u, '')
      .replace(/\s+/gu, ' ')
      .trim();

    const collectRecipients = () => {
      const names = [];
      const single = cleanRecipient(recipientSingle?.value);
      if (single) names.push(single);

      String(recipientsBulk?.value || '').split(/\r?\n/u).forEach((line) => {
        const name = cleanRecipient(line);
        if (name) names.push(name);
      });

      recipientCheckboxes.forEach((checkbox) => {
        if (checkbox.checked) {
          const name = cleanRecipient(checkbox.dataset.recipientName);
          if (name) names.push(name);
        }
      });

      const seen = new Set();
      return names.filter((name) => {
        const key = name.toLocaleLowerCase('id-ID');
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
      });
    };

    const refreshRecipientSummary = () => {
      const total = collectRecipients().length;
      if (recipientCount) recipientCount.textContent = String(total);
      if (generateLabel) {
        generateLabel.textContent = total > 1
          ? `Generate ${total} Nomor Sekaligus`
          : 'Generate & Simpan Nomor';
      }
      recipientBuilder.classList.toggle('has-batch', total > 1);
    };

    const filterRecipientUsers = () => {
      const q = String(recipientUserSearch?.value || '').trim().toLocaleLowerCase('id-ID');
      let shown = 0;
      recipientUserItems.forEach((item) => {
        const haystack = String(item.dataset.search || '').toLocaleLowerCase('id-ID');
        const show = !q || haystack.includes(q);
        item.hidden = !show;
        if (show) shown += 1;
      });
      if (visibleCount) visibleCount.textContent = String(shown);
    };

    recipientSingle?.addEventListener('input', refreshRecipientSummary);
    recipientsBulk?.addEventListener('input', refreshRecipientSummary);
    recipientCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', refreshRecipientSummary));
    recipientUserSearch?.addEventListener('input', filterRecipientUsers);

    selectAll?.addEventListener('click', () => {
      recipientUserItems.forEach((item) => {
        if (item.hidden) return;
        const checkbox = $('[data-recipient-checkbox]', item);
        if (checkbox) checkbox.checked = true;
      });
      refreshRecipientSummary();
    });

    clearAll?.addEventListener('click', () => {
      recipientCheckboxes.forEach((checkbox) => { checkbox.checked = false; });
      refreshRecipientSummary();
    });

    letterForm?.addEventListener('submit', (event) => {
      const total = collectRecipients().length;
      if (total < 1) {
        event.preventDefault();
        letterForm.classList.remove('is-loading');
        const submit = $('button[type="submit"]', letterForm);
        submit?.removeAttribute('aria-busy');
        recipientBuilder.classList.add('recipient-builder-invalid');
        recipientBuilder.scrollIntoView({behavior:'smooth',block:'center'});
        window.setTimeout(() => recipientBuilder.classList.remove('recipient-builder-invalid'), 1800);
        return;
      }
      if (total > 200) {
        event.preventDefault();
        letterForm.classList.remove('is-loading');
        const submit = $('button[type="submit"]', letterForm);
        submit?.removeAttribute('aria-busy');
        window.alert('Maksimal 200 tujuan dalam satu kali generate.');
        return;
      }
      if (total > 1 && !window.confirm(`Generate ${total} nomor surat berbeda dalam satu batch?`)) {
        event.preventDefault();
        letterForm.classList.remove('is-loading');
        const submit = $('button[type="submit"]', letterForm);
        submit?.removeAttribute('aria-busy');
      }
    });

    filterRecipientUsers();
    refreshRecipientSummary();
  }
  // Profile dropdown toggle
const profileChip = $('#profileChip');
const profileDropdown = $('#profileDropdown');
if (profileChip && profileDropdown) {
    profileChip.addEventListener('click', (e) => {
        e.stopPropagation();
        profileDropdown.classList.toggle('show');
    });
    
    document.addEventListener('click', () => {
        profileDropdown.classList.remove('show');
    });
    
    profileDropdown.addEventListener('click', (e) => {
        e.stopPropagation();
    });
}
  // =====================================================================
  // NOTIFICATION
  // =====================================================================
  const notificationButton = $('#notificationButton');
  const notificationPanel = $('#notificationPanel');
  const notificationDot = $('#notificationDot');
  const notificationList = $('#notificationList');
  const notificationSummary = $('#notificationSummary');

  const csrfToken = $('meta[name="csrf-token"]')?.getAttribute('content') || '';

  let notificationOpen = false;

  /**
   * Format tanggal notifikasi.
   */
  const formatNotificationDate = (value) => {
    if (!value) return '';

    const date = new Date(String(value).replace(' ', 'T'));

    if (Number.isNaN(date.getTime())) {
      return String(value);
    }

    return date.toLocaleString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  /**
   * Update titik kuning pada tombol bell.
   */
  const updateNotificationDot = (count) => {
    if (!notificationDot) return;

    notificationDot.hidden = Number(count) <= 0;
  };

  /**
   * Tampilkan loading.
   */
  const showNotificationLoading = () => {
    if (!notificationList) return;

    notificationList.innerHTML = `
      <div class="notification-loading">
        <span class="notification-loading-spinner"></span>
        <span>Memuat notifikasi...</span>
      </div>
    `;
  };

  /**
   * Tampilkan kondisi kosong.
   */
  const showNotificationEmpty = () => {
    if (!notificationList) return;

    notificationList.innerHTML = `
      <div class="notification-empty">
        <div class="notification-empty-icon">
          ${iconMail}
        </div>
        <strong>Tidak ada notifikasi</strong>
        <span>
          Belum ada surat keluar biasa yang masuk ke notifikasi.
        </span>
      </div>
    `;
  };

  /**
   * Icon sederhana untuk notifikasi.
   */
  const iconMail = `
    <svg class="ui-icon"
         viewBox="0 0 24 24"
         fill="none"
         stroke="currentColor"
         stroke-width="1.8"
         stroke-linecap="round"
         stroke-linejoin="round">
      <rect x="3" y="5" width="18" height="14" rx="2"/>
      <path d="m3 7 9 6 9-6"/>
    </svg>
  `;

  const iconCheck = `
    <svg class="ui-icon"
         viewBox="0 0 24 24"
         fill="none"
         stroke="currentColor"
         stroke-width="2"
         stroke-linecap="round"
         stroke-linejoin="round">
      <path d="m5 12 4 4L19 6"/>
    </svg>
  `;

  const iconTrash = `
    <svg class="ui-icon"
         viewBox="0 0 24 24"
         fill="none"
         stroke="currentColor"
         stroke-width="1.8"
         stroke-linecap="round"
         stroke-linejoin="round">
      <path d="M3 6h18"/>
      <path d="M8 6V4h8v2"/>
      <path d="M19 6l-1 14H6L5 6"/>
      <path d="M10 11v5M14 11v5"/>
    </svg>
  `;

  /**
   * Render daftar notifikasi.
   */
  const renderNotifications = (notifications) => {
    if (!notificationList) return;

    if (!Array.isArray(notifications) || notifications.length === 0) {
      showNotificationEmpty();
      return;
    }

    notificationList.innerHTML = notifications.map((item) => {
      const id = Number(item.id);
      const isRead = Number(item.is_read) === 1;

      return `
        <article
          class="notification-item${isRead ? '' : ' is-unread'}"
          data-notification-id="${id}"
        >
          <div class="notification-item-main">
            <div class="notification-item-icon">
              ${iconMail}
            </div>

            <div class="notification-item-content">
              <div class="notification-item-title-row">
                <strong>Surat keluar baru</strong>
                ${
                  !isRead
                    ? '<span class="notification-unread-dot" aria-label="Belum dibaca"></span>'
                    : ''
                }
              </div>

              <span class="notification-letter-number">
                ${escapeHtml(item.letter_number || '-')}
              </span>

              <span class="notification-subject">
                ${escapeHtml(item.subject || 'Tanpa perihal')}
              </span>

              ${
                item.recipient
                  ? `
                    <span class="notification-recipient">
                      Tujuan: ${escapeHtml(item.recipient)}
                    </span>
                  `
                  : ''
              }

              <time class="notification-date">
                ${escapeHtml(formatNotificationDate(item.created_at))}
              </time>
            </div>
          </div>

          <div class="notification-actions">
            <button
              type="button"
              class="notification-action notification-read-btn"
              data-notification-action="read"
              data-notification-id="${id}"
              aria-label="Tandai sudah dibaca"
              title="Tandai sudah dibaca"
              ${isRead ? 'disabled' : ''}
            >
              ${iconCheck}
            </button>

            <button
              type="button"
              class="notification-action notification-delete-btn"
              data-notification-action="delete"
              data-notification-id="${id}"
              aria-label="Hapus notifikasi"
              title="Hapus notifikasi"
            >
              ${iconTrash}
            </button>
          </div>
        </article>
      `;
    }).join('');
  };

  /**
   * Ambil notifikasi dari server.
   */
  const loadNotifications = async () => {
    if (!notificationList) return;

    try {
      const response = await fetch('/api/notifications', {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        cache: 'no-store'
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const result = await response.json();

      if (!result.success) {
        throw new Error('Gagal mengambil notifikasi.');
      }

      const count = Number(result.count || 0);
      const data = Array.isArray(result.data) ? result.data : [];

      updateNotificationDot(count);
      renderNotifications(data);

      if (notificationSummary) {
        notificationSummary.textContent = count > 0
          ? `${count} belum dibaca`
          : 'Tidak ada yang belum dibaca';
      }

    } catch (error) {
      console.error('Notification error:', error);

      if (notificationSummary) {
        notificationSummary.textContent = 'Gagal memuat notifikasi';
      }

      if (notificationList) {
        notificationList.innerHTML = `
          <div class="notification-empty notification-error">
            <strong>Notifikasi tidak dapat dimuat</strong>
            <span>
              Silakan refresh halaman dan coba lagi.
            </span>
          </div>
        `;
      }
    }
  };

  /**
   * Buka/tutup popup.
   */
  const setNotificationPanel = (open) => {
    if (!notificationPanel) return;

    notificationOpen = open;
    notificationPanel.hidden = !open;

    if (open) {
      showNotificationLoading();
      loadNotifications();
    }
  };

  /**
   * Tandai surat sebagai sudah dibaca.
   */
  const markNotificationAsRead = async (id, button) => {
    if (!id) return;

    button?.setAttribute('disabled', 'disabled');

    try {
      const body = new URLSearchParams();
      body.append('_token', csrfToken);

      const response = await fetch(
        `/api/notifications/${encodeURIComponent(id)}/read`,
        {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
          },
          credentials: 'same-origin',
          body: body.toString()
        }
      );

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const result = await response.json();

      if (!result.success) {
        throw new Error('Gagal menandai notifikasi.');
      }

      updateNotificationDot(Number(result.count || 0));

      const item = document.querySelector(
        `.notification-item[data-notification-id="${CSS.escape(String(id))}"]`
      );

      if (item) {
        item.classList.remove('is-unread');

        const unreadDot = $('.notification-unread-dot', item);
        unreadDot?.remove();

        const readButton = $('[data-notification-action="read"]', item);

        if (readButton) {
          readButton.disabled = true;
        }
      }

      if (notificationSummary) {
        const count = Number(result.count || 0);
        notificationSummary.textContent = count > 0
          ? `${count} belum dibaca`
          : 'Tidak ada yang belum dibaca';
      }

    } catch (error) {
      console.error('Mark notification read error:', error);

      button?.removeAttribute('disabled');

      window.alert(
        'Notifikasi gagal ditandai sebagai sudah dibaca.'
      );
    }
  };

  /**
   * Hapus notifikasi dari daftar user.
   *
   * Catatan:
   * Ini TIDAK menghapus surat dari outgoing_letters.
   * Hanya menghapus notifikasi user.
   */
  const deleteNotification = async (id, button) => {
    if (!id) return;

    button?.setAttribute('disabled', 'disabled');

    try {
      const body = new URLSearchParams();
      body.append('_token', csrfToken);

      const response = await fetch(
        `/api/notifications/${encodeURIComponent(id)}/delete`,
        {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
          },
          credentials: 'same-origin',
          body: body.toString()
        }
      );

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const result = await response.json();

      if (!result.success) {
        throw new Error('Gagal menghapus notifikasi.');
      }

      updateNotificationDot(Number(result.count || 0));

      const item = document.querySelector(
        `.notification-item[data-notification-id="${CSS.escape(String(id))}"]`
      );

      item?.remove();

      if (
        notificationList &&
        !notificationList.querySelector('.notification-item')
      ) {
        showNotificationEmpty();
      }

      if (notificationSummary) {
        const count = Number(result.count || 0);

        notificationSummary.textContent = count > 0
          ? `${count} belum dibaca`
          : 'Tidak ada yang belum dibaca';
      }

    } catch (error) {
      console.error('Delete notification error:', error);

      button?.removeAttribute('disabled');

      window.alert(
        'Notifikasi gagal dihapus.'
      );
    }
  };

  /**
   * Tombol bell.
   */
  notificationButton?.addEventListener('click', (event) => {
    event.stopPropagation();

    setNotificationPanel(!notificationOpen);
  });

  /**
   * Klik di dalam popup tidak menutup popup.
   */
  notificationPanel?.addEventListener('click', (event) => {
    event.stopPropagation();

    const button = event.target.closest(
      '[data-notification-action]'
    );

    if (!button) return;

    const action = button.dataset.notificationAction;
    const id = Number(button.dataset.notificationId);

    if (!id) return;

    if (action === 'read') {
      markNotificationAsRead(id, button);
    }

    if (action === 'delete') {
      deleteNotification(id, button);
    }
  });

  /**
   * Klik di luar popup menutup popup.
   */
  document.addEventListener('click', () => {
    if (!notificationOpen) return;

    setNotificationPanel(false);
  });

  /**
   * ESC untuk menutup popup.
   */
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && notificationOpen) {
      setNotificationPanel(false);
    }
  });

  /**
   * Ambil jumlah notifikasi saat halaman dibuka.
   * Ini membuat titik kuning langsung muncul jika ada surat baru.
   */
  if (notificationButton) {
    loadNotifications();
  }

  /**
   * Refresh jumlah notifikasi setiap 30 detik.
   * Tidak membuka popup secara otomatis.
   */
  if (notificationButton) {
    window.setInterval(() => {
      loadNotifications();
    }, 30000);
  }
})();
