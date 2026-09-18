document.addEventListener('DOMContentLoaded', function () {
  const tbody      = document.getElementById('amendments-tbody');
  const modalEl    = document.getElementById('amendmentModal');
  const modal      = new bootstrap.Modal(modalEl);
  const modalTitle = document.getElementById('amendmentModalTitle');
  const formAlert  = document.getElementById('amendment-form-alert');

  let dragSrcId = null;

  // ── Utilities ───────────────────────────────────────────────────────────────
  function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
  }

  function fmtDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString(undefined, {
      dateStyle: 'medium', timeStyle: 'short',
    });
  }

  async function api(action, payload = {}, method = 'POST') {
    if (method === 'GET') {
      const qs  = new URLSearchParams(payload).toString();
      const url = '../api/admin_amendments.php' + (qs ? '?' + qs : '');
      const res = await fetch(url);
      return res.json();
    }
    const res = await fetch('../api/admin_amendments.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action, ...payload }),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Request failed');
    return data;
  }

  // ── Stats / Settings render ─────────────────────────────────────────────────
  function renderStats(stats, activeCount) {
    document.getElementById('a-stat-checked-in').textContent  = stats.checked_in;
    document.getElementById('a-stat-submitted').textContent   = stats.ballots_submitted;
    document.getElementById('a-stat-turnout').textContent     = stats.turnout_rate + '%';
    document.getElementById('a-stat-active').textContent      = activeCount;
  }

  function renderSettings(settings) {
    document.getElementById('s-convention-name').value          = settings.convention_name;
    document.getElementById('s-voting-open').checked            = !!Number(settings.voting_open);
    document.getElementById('s-results-public').checked         = !!Number(settings.results_public);
  }

  // ── Amendments table ─────────────────────────────────────────────────────────
  function renderRows(amendments) {
    if (!amendments.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No amendments yet. Click "New Amendment" to add one.</td></tr>';
      return;
    }
    tbody.innerHTML = amendments.map(a => `
      <tr draggable="true" data-id="${a.id}">
        <td class="text-muted" style="cursor:grab;">⠿</td>
        <td class="fw-semibold">${escapeHtml(a.proposal_no)}</td>
        <td>${escapeHtml(a.article_no)}</td>
        <td>${escapeHtml(a.section)}</td>
        <td>${escapeHtml(a.page_no)}</td>
        <td class="text-muted" style="max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(a.summary || '—')}</td>
        <td>
          <span class="badge rounded-pill ${Number(a.is_active) ? 'badge-active' : 'badge-inactive'}">
            ${Number(a.is_active) ? 'Active' : 'Hidden'}
          </span>
        </td>
        <td class="text-end">
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-forest btn-edit"   data-id="${a.id}">Edit</button>
            <button class="btn btn-outline-secondary btn-toggle" data-id="${a.id}">${Number(a.is_active) ? 'Hide' : 'Show'}</button>
            <button class="btn btn-outline-danger btn-delete" data-id="${a.id}">Delete</button>
          </div>
        </td>
      </tr>
    `).join('');

    attachRowEvents(amendments);
    attachDragEvents();
  }

  let cache = { amendments: [] };

  function attachRowEvents(amendments) {
    tbody.querySelectorAll('.btn-edit').forEach(btn => {
      btn.addEventListener('click', () =>
        openEdit(amendments.find(a => a.id == btn.dataset.id))
      );
    });
    tbody.querySelectorAll('.btn-toggle').forEach(btn => {
      btn.addEventListener('click', async () => {
        await api('toggle_active', { id: btn.dataset.id });
        loadAll();
      });
    });
    tbody.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm('Delete this amendment permanently? This also removes any votes already cast on it.')) return;
        await api('delete', { id: btn.dataset.id });
        loadAll();
      });
    });
  }

  function attachDragEvents() {
    const rows = Array.from(tbody.querySelectorAll('tr[draggable="true"]'));
    rows.forEach(row => {
      row.addEventListener('dragstart', () => { dragSrcId = row.dataset.id; row.style.opacity = '0.5'; });
      row.addEventListener('dragend',   () => { row.style.opacity = '1'; });
      row.addEventListener('dragover',  (e) => e.preventDefault());
      row.addEventListener('drop', async (e) => {
        e.preventDefault();
        const targetId = row.dataset.id;
        if (targetId === dragSrcId) return;
        const ids  = rows.map(r => r.dataset.id);
        const from = ids.indexOf(dragSrcId);
        const to   = ids.indexOf(targetId);
        ids.splice(to, 0, ids.splice(from, 1)[0]);
        await api('reorder', { order: ids });
        loadAll();
      });
    });
  }

  // ── Amendment modal ──────────────────────────────────────────────────────────
  function openEdit(a) {
    modalTitle.textContent = `Edit Proposal ${a.proposal_no}`;
    document.getElementById('f-id').value                    = a.id;
    document.getElementById('f-proposal-no').value           = a.proposal_no;
    document.getElementById('f-article-no').value            = a.article_no;
    document.getElementById('f-section').value               = a.section;
    document.getElementById('f-page-no').value               = a.page_no;
    document.getElementById('f-summary').value               = a.summary || '';
    document.getElementById('f-proposed-amendment').value    = a.proposed_amendment;
    document.getElementById('f-original-text').value         = a.original_text || '';
    document.getElementById('f-rationale').value             = a.rationale || '';
    formAlert.classList.add('d-none');
    modal.show();
  }

  document.getElementById('btn-new-amendment').addEventListener('click', () => {
    modalTitle.textContent = 'New Amendment';
    document.getElementById('amendment-form').reset();
    document.getElementById('f-id').value = '';
    formAlert.classList.add('d-none');
  });

  document.getElementById('btn-save-amendment').addEventListener('click', async () => {
    const id      = document.getElementById('f-id').value;
    const payload = {
      proposal_no:        document.getElementById('f-proposal-no').value.trim(),
      article_no:         document.getElementById('f-article-no').value.trim(),
      section:            document.getElementById('f-section').value.trim(),
      page_no:            document.getElementById('f-page-no').value.trim(),
      summary:            document.getElementById('f-summary').value.trim(),
      proposed_amendment: document.getElementById('f-proposed-amendment').value.trim(),
      original_text:      document.getElementById('f-original-text').value.trim(),
      rationale:          document.getElementById('f-rationale').value.trim(),
    };

    if (!payload.proposal_no || !payload.article_no || !payload.section ||
        !payload.page_no     || !payload.proposed_amendment) {
      formAlert.textContent = 'Please fill in all required fields (marked with *).';
      formAlert.classList.remove('d-none');
      return;
    }

    try {
      if (id) {
        await api('update', { id, ...payload });
      } else {
        await api('create', payload);
      }
      modal.hide();
      loadAll();
    } catch (err) {
      formAlert.textContent = err.message;
      formAlert.classList.remove('d-none');
    }
  });

  // ── Settings form ────────────────────────────────────────────────────────────
  const settingsForm  = document.getElementById('settings-form');
  const settingsAlert = document.getElementById('settings-alert');
  settingsForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    await api('update_settings', {
      convention_name: document.getElementById('s-convention-name').value.trim(),
      voting_open:     document.getElementById('s-voting-open').checked,
      results_public:  document.getElementById('s-results-public').checked,
    });
    settingsAlert.textContent = 'Settings saved.';
    settingsAlert.classList.remove('d-none');
    setTimeout(() => settingsAlert.classList.add('d-none'), 2500);
  });

  // ── Comments section ─────────────────────────────────────────────────────────
  const commentsSection = document.getElementById('comments-section');

  function renderComments(grouped) {
    if (!grouped || !grouped.length) {
      commentsSection.innerHTML = `
        <div class="card-panel p-4 text-center text-muted" style="font-size:.9rem;">
          No comments have been submitted yet.
        </div>`;
      return;
    }

    commentsSection.innerHTML = grouped.map(group => `
      <div class="admin-comments-group card-panel mb-3">
        <div class="admin-comments-group-header">
          <span class="fw-bold">${escapeHtml(group.proposal_no)}</span>
          ${group.summary ? `<span class="text-muted ms-2" style="font-size:.85rem;">${escapeHtml(group.summary)}</span>` : ''}
          <span class="badge admin-comments-badge ms-auto">${group.comments.length} comment${group.comments.length !== 1 ? 's' : ''}</span>
        </div>
        <div class="admin-comments-list">
          ${group.comments.map((c, i) => `
            <div class="admin-comment-item ${i < group.comments.length - 1 ? 'admin-comment-item--divider' : ''}">
              <div class="admin-comment-meta">
                <span class="admin-comment-author">${escapeHtml(c.full_name)}</span>
                <span class="admin-comment-church">${escapeHtml(c.local_church)} · ${escapeHtml(c.area)}</span>
                <span class="admin-comment-time ms-auto">${fmtDate(c.voted_at)}</span>
              </div>
              <div class="admin-comment-text">${escapeHtml(c.comment_text)}</div>
            </div>
          `).join('')}
        </div>
      </div>
    `).join('');
  }

  async function loadComments() {
    commentsSection.innerHTML = '<div class="text-muted py-3 text-center" style="font-size:.9rem;">Loading comments…</div>';
    try {
      const data = await api('', { action: 'get_comments' }, 'GET');
      renderComments(data.grouped);
    } catch {
      commentsSection.innerHTML = '<div class="alert alert-danger">Could not load comments.</div>';
    }
  }

  document.getElementById('btn-refresh-comments').addEventListener('click', loadComments);

  // ── Load everything ──────────────────────────────────────────────────────────
  async function loadAll() {
    const data = await api('', {}, 'GET');
    cache = data;
    renderStats(data.stats, data.amendments.filter(a => Number(a.is_active)).length);
    renderSettings(data.settings);
    renderRows(data.amendments);
  }

  loadAll();
  loadComments();

  // Refresh stats every 10s
  setInterval(async () => {
    const data = await api('', {}, 'GET');
    renderStats(data.stats, data.amendments.filter(a => Number(a.is_active)).length);
  }, 10000);
});
