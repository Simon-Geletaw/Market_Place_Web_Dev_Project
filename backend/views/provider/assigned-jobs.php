<?php
/**
 * Provider: Assigned & Completed Jobs
 *
 * Shows the provider's active (Assigned) jobs and their completed history.
 * Provider can mark an Assigned job as complete from this page.
 * Data from GET /api/provider/jobs/assigned and GET /api/provider/jobs/completed.
 */
ob_start();
?>
<section class="page-jobs">
  <div class="page-header">
    <h1>My Jobs</h1>
  </div>

  <!-- Tab switcher -->
  <div class="tab-bar" role="tablist">
    <button class="tab active" role="tab" aria-selected="true"  data-tab="assigned"  id="tab-assigned">Assigned</button>
    <button class="tab"        role="tab" aria-selected="false" data-tab="completed" id="tab-completed">Completed</button>
  </div>

  <!-- Assigned jobs panel -->
  <div id="panel-assigned" class="tab-panel" role="tabpanel" aria-labelledby="tab-assigned">
    <div id="assigned-list" class="job-list" aria-live="polite">
      <p class="loading-text">Loading assigned jobs…</p>
    </div>
  </div>

  <!-- Completed jobs panel -->
  <div id="panel-completed" class="tab-panel" role="tabpanel" aria-labelledby="tab-completed" hidden>
    <div id="completed-list" class="job-list" aria-live="polite">
      <p class="loading-text">Loading completed jobs…</p>
    </div>
  </div>
</section>

<!-- Mark Complete Modal -->
<div id="complete-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="complete-modal-title" hidden>
  <div class="modal-backdrop"></div>
  <div class="modal-box">
    <h2 id="complete-modal-title">Mark Job as Completed</h2>
    <p class="modal-desc">Confirm that you have finished this job. The customer will be notified and can leave a review.</p>

    <form id="complete-form" novalidate>
      <input type="hidden" id="complete-request-id">

      <label for="completion-photo">Completion Photo URL <span style="font-weight:400;color:var(--color-text-secondary)">(optional)</span></label>
      <input id="completion-photo" type="text" placeholder="https://… or leave blank">

      <div id="complete-error" class="form-error" role="alert" hidden></div>

      <div class="modal-actions">
        <button type="submit" class="btn btn-success" id="btn-confirm-complete">Confirm Completion</button>
        <button type="button" class="btn btn-ghost"   id="btn-cancel-complete">Cancel</button>
      </div>
    </form>
  </div>
</div>

<style>
.page-jobs { padding: 24px 0; }
.page-header { margin-bottom: 20px; }
.page-header h1 { margin: 0; font-size: 1.6rem; }

/* Tabs */
.tab-bar { display: flex; gap: 4px; border-bottom: 2px solid var(--color-border); margin-bottom: 24px; }
.tab {
  padding: 10px 20px; border: none; background: none; cursor: pointer;
  font-size: 0.95rem; font-weight: 500; color: var(--color-text-secondary);
  border-bottom: 2px solid transparent; margin-bottom: -2px; transition: color 0.15s, border-color 0.15s;
}
.tab.active { color: var(--color-primary); border-bottom-color: var(--color-primary); }
.tab-panel[hidden] { display: none; }

/* Job cards */
.job-list { display: flex; flex-direction: column; gap: 14px; }
.job-card {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 10px; padding: 20px; display: grid;
  grid-template-columns: 1fr auto; gap: 12px; align-items: start;
}
.job-card-info { display: flex; flex-direction: column; gap: 6px; }
.job-card-title { font-weight: 600; font-size: 1rem; margin: 0; }
.job-meta { display: flex; flex-wrap: wrap; gap: 12px; font-size: 0.85rem; color: var(--color-text-secondary); }
.job-price { font-size: 1rem; font-weight: 700; color: var(--color-text); }
.job-customer { font-size: 0.85rem; color: var(--color-text-secondary); }
.job-card-actions { display: flex; flex-direction: column; gap: 8px; align-items: flex-end; }

.status-badge {
  font-size: 0.75rem; font-weight: 600; padding: 3px 8px; border-radius: 12px;
}
.status-badge.Assigned  { background: #d4edda; color: #155724; }
.status-badge.Completed { background: #e2e3e5; color: #383d41; }

.btn { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.88rem; font-weight: 500; transition: background 0.15s; }
.btn-success { background: var(--color-success); color: #fff; }
.btn-success:hover { background: #218838; }
.btn-ghost { background: transparent; color: var(--color-text-secondary); }
.btn-ghost:hover { background: #f0f0f0; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }

.modal { position: fixed; inset: 0; z-index: 1000; display: flex; align-items: center; justify-content: center; }
.modal[hidden] { display: none; }
.modal-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.45); }
.modal-box {
  position: relative; background: var(--color-surface); border-radius: 12px;
  padding: 28px; width: min(460px, calc(100vw - 32px));
  box-shadow: 0 8px 32px rgba(0,0,0,0.18);
}
.modal-box h2 { margin: 0 0 6px; font-size: 1.2rem; }
.modal-desc { color: var(--color-text-secondary); font-size: 0.9rem; margin: 0 0 18px; }
.modal-box label { display: block; font-size: 0.88rem; font-weight: 600; margin: 14px 0 4px; }
.modal-box input[type="text"] {
  width: 100%; padding: 9px 11px; border: 1px solid var(--color-border);
  border-radius: 6px; font-size: 0.9rem;
}
.modal-actions { display: flex; gap: 10px; margin-top: 20px; }
.form-error { background: #fde8e8; color: #c0392b; border-radius: 6px; padding: 9px 12px; font-size: 0.88rem; }
.empty-state { text-align: center; padding: 48px 16px; color: var(--color-text-secondary); }
.empty-state h3 { margin: 0 0 8px; }
.loading-text { color: var(--color-text-secondary); }
</style>

<script>
(function () {
  'use strict';

  // ── DOM refs ───────────────────────────────────────────────────────
  const tabs          = document.querySelectorAll('.tab');
  const assignedList  = document.getElementById('assigned-list');
  const completedList = document.getElementById('completed-list');
  const modal         = document.getElementById('complete-modal');
  const completeForm  = document.getElementById('complete-form');
  const completeReqId = document.getElementById('complete-request-id');
  const photoInput    = document.getElementById('completion-photo');
  const completeError = document.getElementById('complete-error');
  const btnConfirm    = document.getElementById('btn-confirm-complete');
  const btnCancel     = document.getElementById('btn-cancel-complete');

  // ── API helper ─────────────────────────────────────────────────────
  async function api(method, path, body) {
    const opts = { method, credentials: 'include', headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(path, opts);
    return res.json();
  }

  // ── Bootstrap ──────────────────────────────────────────────────────
  async function init() {
    await Promise.all([loadAssigned(), loadCompleted()]);
  }

  async function loadAssigned() {
    assignedList.innerHTML = '<p class="loading-text">Loading…</p>';
    try {
      const res = await api('GET', '/api/provider/jobs/assigned');
      renderJobs(assignedList, res.data || [], 'assigned');
    } catch (_) {
      assignedList.innerHTML = '<p class="empty-state">Failed to load jobs.</p>';
    }
  }

  async function loadCompleted() {
    completedList.innerHTML = '<p class="loading-text">Loading…</p>';
    try {
      const res = await api('GET', '/api/provider/jobs/completed');
      renderJobs(completedList, res.data || [], 'completed');
    } catch (_) {
      completedList.innerHTML = '<p class="empty-state">Failed to load jobs.</p>';
    }
  }

  // ── Render ─────────────────────────────────────────────────────────
  function renderJobs(container, jobs, type) {
    if (!jobs.length) {
      const label = type === 'assigned' ? 'assigned jobs' : 'completed jobs';
      container.innerHTML = `<div class="empty-state"><h3>No ${label} yet</h3></div>`;
      return;
    }

    container.innerHTML = jobs.map(j => {
      const date  = j.preferred_date ? new Date(j.preferred_date).toLocaleDateString() : 'Flexible';
      const price = j.accepted_price ? `ETB ${Number(j.accepted_price).toLocaleString()}` : '—';
      const desc  = (j.description || j.title || '').substring(0, 100);

      return `
        <div class="job-card" data-id="${j.id}">
          <div class="job-card-info">
            <h3 class="job-card-title">${escHtml(j.category_name || 'Service Request')}</h3>
            <p style="margin:0;font-size:0.88rem;color:var(--color-text-secondary)">${escHtml(desc)}${desc.length === 100 ? '…' : ''}</p>
            <div class="job-meta">
              <span>📍 ${escHtml(j.location || '—')}</span>
              <span>📅 ${escHtml(date)}</span>
              ${j.customer_name ? `<span>👤 ${escHtml(j.customer_name)}</span>` : ''}
              ${j.customer_phone ? `<span>📞 ${escHtml(j.customer_phone)}</span>` : ''}
            </div>
          </div>
          <div class="job-card-actions">
            <span class="status-badge ${j.status}">${j.status}</span>
            <span class="job-price">${price}</span>
            ${type === 'assigned'
              ? `<button class="btn btn-success btn-complete" data-id="${j.id}">Mark Complete</button>`
              : ''
            }
          </div>
        </div>`;
    }).join('');

    if (type === 'assigned') {
      container.querySelectorAll('.btn-complete').forEach(btn => {
        btn.addEventListener('click', () => openCompleteModal(btn.dataset.id));
      });
    }
  }

  // ── Complete modal ─────────────────────────────────────────────────
  function openCompleteModal(requestId) {
    completeReqId.value = requestId;
    photoInput.value    = '';
    hideError();
    modal.hidden = false;
    btnConfirm.focus();
  }

  function closeCompleteModal() {
    modal.hidden = true;
    completeForm.reset();
    hideError();
  }

  function showError(msg) { completeError.textContent = msg; completeError.hidden = false; }
  function hideError()    { completeError.hidden = true; completeError.textContent = ''; }

  completeForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideError();

    const requestId = completeReqId.value;
    const photo     = photoInput.value.trim() || null;

    btnConfirm.disabled = true;
    btnConfirm.textContent = 'Saving…';

    try {
      const res = await api('POST', `/api/requests/${requestId}/complete`, { completion_photo: photo });
      if (res.success) {
        closeCompleteModal();
        // Remove the card from assigned list and reload completed
        const card = assignedList.querySelector(`[data-id="${requestId}"]`);
        if (card) card.remove();
        if (!assignedList.querySelector('.job-card')) {
          assignedList.innerHTML = '<div class="empty-state"><h3>No assigned jobs yet</h3></div>';
        }
        await loadCompleted();
      } else {
        showError(res.message || 'Failed to mark complete.');
      }
    } catch (_) {
      showError('Network error. Please try again.');
    } finally {
      btnConfirm.disabled = false;
      btnConfirm.textContent = 'Confirm Completion';
    }
  });

  // ── Tabs ───────────────────────────────────────────────────────────
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); });
      tab.classList.add('active');
      tab.setAttribute('aria-selected', 'true');

      document.getElementById('panel-assigned').hidden  = tab.dataset.tab !== 'assigned';
      document.getElementById('panel-completed').hidden = tab.dataset.tab !== 'completed';
    });
  });

  // ── Modal close ────────────────────────────────────────────────────
  btnCancel.addEventListener('click', closeCompleteModal);
  modal.querySelector('.modal-backdrop').addEventListener('click', closeCompleteModal);
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) closeCompleteModal(); });

  function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  init();
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
