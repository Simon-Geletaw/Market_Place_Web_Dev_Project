<?php
/**
 * Provider: Browse Open Requests
 *
 * Displays all open service requests (status: Requested or Negotiating).
 * Provider can filter by category/location and submit an offer inline.
 * All data is fetched from GET /api/marketplace via JavaScript.
 */
ob_start();
?>
<section class="page-browse">
  <div class="page-header">
    <h1>Browse Open Requests</h1>
    <p class="page-subtitle">Find jobs that match your skills and location.</p>
  </div>

  <!-- Filters -->
  <div class="filter-bar">
    <select id="filter-category" aria-label="Filter by category">
      <option value="">All Categories</option>
    </select>
    <input id="filter-location" type="text" placeholder="Filter by location…" aria-label="Filter by location">
    <button id="btn-filter" class="btn btn-secondary">Apply Filters</button>
    <button id="btn-clear" class="btn btn-ghost">Clear</button>
  </div>

  <!-- Results -->
  <div id="requests-list" class="card-grid" aria-live="polite">
    <p class="loading-text">Loading requests…</p>
  </div>
</section>

<!-- Offer Modal -->
<div id="offer-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title" hidden>
  <div class="modal-backdrop"></div>
  <div class="modal-box">
    <h2 id="modal-title">Submit an Offer</h2>
    <p id="modal-request-desc" class="modal-desc"></p>

    <form id="offer-form" novalidate>
      <input type="hidden" id="offer-request-id">

      <label for="offer-price">Your Price (ETB) <span aria-hidden="true">*</span></label>
      <input id="offer-price" type="number" min="1" step="0.01" required placeholder="e.g. 500">

      <label for="offer-message">Message to Customer <span aria-hidden="true">*</span></label>
      <textarea id="offer-message" rows="3" required placeholder="Describe your experience and availability…"></textarea>

      <div id="offer-error" class="form-error" role="alert" hidden></div>

      <div class="modal-actions">
        <button type="submit" class="btn btn-primary" id="btn-submit-offer">Submit Offer</button>
        <button type="button" class="btn btn-ghost" id="btn-cancel-offer">Cancel</button>
      </div>
    </form>
  </div>
</div>

<style>
.page-browse { padding: 24px 0; }
.page-header { margin-bottom: 24px; }
.page-header h1 { margin: 0 0 4px; font-size: 1.6rem; }
.page-subtitle { margin: 0; color: var(--color-text-secondary); }

.filter-bar {
  display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 8px; padding: 14px 16px; margin-bottom: 24px;
}
.filter-bar select,
.filter-bar input[type="text"] {
  flex: 1; min-width: 160px; padding: 8px 10px;
  border: 1px solid var(--color-border); border-radius: 6px;
  font-size: 0.9rem;
}

.card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
.loading-text { color: var(--color-text-secondary); }

.request-card {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 10px; padding: 18px; display: flex; flex-direction: column; gap: 10px;
}
.request-card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
.request-card-title { font-weight: 600; font-size: 1rem; margin: 0; }
.status-badge {
  font-size: 0.75rem; font-weight: 600; padding: 3px 8px; border-radius: 12px;
  white-space: nowrap;
}
.status-badge.Requested  { background: #e8f4fd; color: #0066cc; }
.status-badge.Negotiating { background: #fff3cd; color: #856404; }

.request-meta { display: flex; flex-wrap: wrap; gap: 8px; font-size: 0.85rem; color: var(--color-text-secondary); }
.meta-item::before { content: ''; }
.request-budget { font-weight: 600; color: var(--color-text); }
.request-card-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 4px; }
.offer-count { font-size: 0.82rem; color: var(--color-text-secondary); }

.btn { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.9rem; font-weight: 500; transition: background 0.15s; }
.btn-primary { background: var(--color-primary); color: #fff; }
.btn-primary:hover { background: var(--color-primary-hover); }
.btn-secondary { background: #e9ecef; color: var(--color-text); }
.btn-secondary:hover { background: #dee2e6; }
.btn-ghost { background: transparent; color: var(--color-text-secondary); }
.btn-ghost:hover { background: #f0f0f0; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }

/* Modal */
.modal { position: fixed; inset: 0; z-index: 1000; display: flex; align-items: center; justify-content: center; }
.modal[hidden] { display: none; }
.modal-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.45); }
.modal-box {
  position: relative; background: var(--color-surface); border-radius: 12px;
  padding: 28px; width: min(480px, calc(100vw - 32px)); max-height: 90vh; overflow-y: auto;
  box-shadow: 0 8px 32px rgba(0,0,0,0.18);
}
.modal-box h2 { margin: 0 0 6px; font-size: 1.2rem; }
.modal-desc { color: var(--color-text-secondary); font-size: 0.9rem; margin: 0 0 18px; }
.modal-box label { display: block; font-size: 0.88rem; font-weight: 600; margin: 14px 0 4px; }
.modal-box input[type="number"],
.modal-box textarea {
  width: 100%; padding: 9px 11px; border: 1px solid var(--color-border);
  border-radius: 6px; font-size: 0.9rem; font-family: inherit;
}
.modal-box textarea { resize: vertical; }
.modal-actions { display: flex; gap: 10px; margin-top: 20px; }
.form-error { background: #fde8e8; color: #c0392b; border-radius: 6px; padding: 9px 12px; font-size: 0.88rem; }

.empty-state { grid-column: 1/-1; text-align: center; padding: 48px 16px; color: var(--color-text-secondary); }
.empty-state h3 { margin: 0 0 8px; font-size: 1.1rem; }
.already-offered { font-size: 0.8rem; color: var(--color-success); font-weight: 600; }
</style>

<script>
(function () {
  'use strict';

  // ── State ──────────────────────────────────────────────────────────
  let allRequests = [];
  let myOfferRequestIds = new Set(); // requests this provider already bid on

  // ── DOM refs ───────────────────────────────────────────────────────
  const listEl        = document.getElementById('requests-list');
  const catFilter     = document.getElementById('filter-category');
  const locFilter     = document.getElementById('filter-location');
  const btnFilter     = document.getElementById('btn-filter');
  const btnClear      = document.getElementById('btn-clear');
  const modal         = document.getElementById('offer-modal');
  const modalDesc     = document.getElementById('modal-request-desc');
  const offerForm     = document.getElementById('offer-form');
  const offerReqId    = document.getElementById('offer-request-id');
  const offerPrice    = document.getElementById('offer-price');
  const offerMessage  = document.getElementById('offer-message');
  const offerError    = document.getElementById('offer-error');
  const btnSubmit     = document.getElementById('btn-submit-offer');
  const btnCancel     = document.getElementById('btn-cancel-offer');

  // ── API helpers ────────────────────────────────────────────────────
  async function api(method, path, body) {
    const opts = { method, credentials: 'include', headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(path, opts);
    return res.json();
  }

  // ── Bootstrap ──────────────────────────────────────────────────────
  async function init() {
    await Promise.all([loadCategories(), loadMyOffers(), loadRequests()]);
  }

  async function loadCategories() {
    try {
      const res = await api('GET', '/api/categories');
      const cats = res.data || [];
      cats.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id || c.CATEGORY_ID;
        opt.textContent = c.name || c.NAME;
        catFilter.appendChild(opt);
      });
    } catch (_) { /* non-critical */ }
  }

  async function loadMyOffers() {
    try {
      const res = await api('GET', '/api/offers');
      const offers = res.data || [];
      myOfferRequestIds = new Set(offers.map(o => o.request_id));
    } catch (_) { /* non-critical */ }
  }

  async function loadRequests() {
    listEl.innerHTML = '<p class="loading-text">Loading requests…</p>';
    try {
      const params = new URLSearchParams();
      if (catFilter.value) params.set('category_id', catFilter.value);
      if (locFilter.value.trim()) params.set('location', locFilter.value.trim());

      const res = await api('GET', '/api/marketplace?' + params.toString());
      allRequests = res.data || [];
      renderRequests(allRequests);
    } catch (e) {
      listEl.innerHTML = '<p class="empty-state">Failed to load requests. Please refresh.</p>';
    }
  }

  // ── Render ─────────────────────────────────────────────────────────
  function renderRequests(requests) {
    if (!requests.length) {
      listEl.innerHTML = '<div class="empty-state"><h3>No open requests found</h3><p>Try adjusting your filters.</p></div>';
      return;
    }

    listEl.innerHTML = requests.map(r => {
      const alreadyOffered = myOfferRequestIds.has(r.id);
      const budget = r.budget ? `ETB ${Number(r.budget).toLocaleString()}` : 'Budget not set';
      const date   = r.preferred_date ? new Date(r.preferred_date).toLocaleDateString() : 'Flexible';
      const desc   = (r.description || r.title || '').substring(0, 120);

      return `
        <article class="request-card" data-id="${r.id}">
          <div class="request-card-header">
            <h3 class="request-card-title">${escHtml(r.category_name || 'Service Request')}</h3>
            <span class="status-badge ${r.status}" data-status="${r.status}">${r.status}</span>
          </div>
          <p style="margin:0;font-size:0.9rem;color:var(--color-text-secondary)">${escHtml(desc)}${desc.length === 120 ? '…' : ''}</p>
          <div class="request-meta">
            <span>📍 ${escHtml(r.location || 'Location not set')}</span>
            <span>📅 ${escHtml(date)}</span>
            <span class="request-budget">💰 ${budget}</span>
          </div>
          <div class="request-card-footer">
            <span class="offer-count">${r.offer_count || 0} offer${r.offer_count === 1 ? '' : 's'}</span>
            ${alreadyOffered
              ? '<span class="already-offered">✓ Offer submitted</span>'
              : `<button class="btn btn-primary btn-offer" data-id="${r.id}" data-desc="${escAttr(desc)}">Make Offer</button>`
            }
          </div>
        </article>`;
    }).join('');

    // Attach offer button listeners
    listEl.querySelectorAll('.btn-offer').forEach(btn => {
      btn.addEventListener('click', () => openModal(btn.dataset.id, btn.dataset.desc));
    });
  }

  // ── Modal ──────────────────────────────────────────────────────────
  function openModal(requestId, desc) {
    offerReqId.value   = requestId;
    modalDesc.textContent = desc || '';
    offerPrice.value   = '';
    offerMessage.value = '';
    hideError();
    modal.hidden = false;
    offerPrice.focus();
  }

  function closeModal() {
    modal.hidden = true;
    offerForm.reset();
    hideError();
  }

  function showError(msg) {
    offerError.textContent = msg;
    offerError.hidden = false;
  }

  function hideError() {
    offerError.hidden = true;
    offerError.textContent = '';
  }

  // ── Submit offer ───────────────────────────────────────────────────
  offerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideError();

    const requestId = offerReqId.value;
    const price     = parseFloat(offerPrice.value);
    const message   = offerMessage.value.trim();

    if (!price || price <= 0) { showError('Price must be greater than zero.'); return; }
    if (!message)             { showError('Please add a message to the customer.'); return; }

    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Submitting…';

    try {
      const res = await api('POST', `/api/requests/${requestId}/offers`, { price, message });
      if (res.success) {
        closeModal();
        myOfferRequestIds.add(requestId);
        // Update the card in-place without full reload
        const card = listEl.querySelector(`[data-id="${requestId}"]`);
        if (card) {
          const btn = card.querySelector('.btn-offer');
          if (btn) {
            const span = document.createElement('span');
            span.className = 'already-offered';
            span.textContent = '✓ Offer submitted';
            btn.replaceWith(span);
          }
        }
      } else {
        showError(res.message || 'Failed to submit offer.');
      }
    } catch (_) {
      showError('Network error. Please try again.');
    } finally {
      btnSubmit.disabled = false;
      btnSubmit.textContent = 'Submit Offer';
    }
  });

  // ── Event listeners ────────────────────────────────────────────────
  btnFilter.addEventListener('click', loadRequests);
  btnClear.addEventListener('click', () => {
    catFilter.value = '';
    locFilter.value = '';
    loadRequests();
  });
  btnCancel.addEventListener('click', closeModal);
  modal.querySelector('.modal-backdrop').addEventListener('click', closeModal);
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) closeModal(); });

  // ── Utilities ──────────────────────────────────────────────────────
  function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function escAttr(str) {
    return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  init();
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
