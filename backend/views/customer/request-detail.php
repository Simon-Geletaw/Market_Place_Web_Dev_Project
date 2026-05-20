<?php
/**
 * Customer: Request Detail
 *
 * Shows full details of a single service request.
 * - If status is Negotiating: shows all offers with Accept / Reject / Counter actions.
 * - If status is Completed: shows a review form.
 * - If status is Assigned/Reviewed: shows read-only info.
 *
 * The request ID is read from ?id= in the query string.
 * All data is fetched via the API using JavaScript.
 */
ob_start();
?>
<section class="page-request-detail">
  <!-- Back link -->
  <a href="/views/customer/my-requests.php" class="back-link">← Back to My Requests</a>

  <!-- Request summary card -->
  <div id="request-summary" class="summary-card" aria-live="polite">
    <p class="loading-text">Loading request…</p>
  </div>

  <!-- Offers section (shown when Negotiating) -->
  <div id="offers-section" hidden>
    <h2 class="section-title">Offers <span id="offer-count-badge" class="count-badge"></span></h2>
    <div id="offers-list" class="offers-list" aria-live="polite"></div>
  </div>

  <!-- Review section (shown when Completed and not yet reviewed) -->
  <div id="review-section" hidden>
    <h2 class="section-title">Leave a Review</h2>
    <div class="review-card">
      <p class="review-intro">How did the job go? Your rating helps other customers choose great providers.</p>
      <form id="review-form" novalidate>
        <div class="star-rating" role="group" aria-label="Rating">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <button type="button" class="star" data-value="<?= $i ?>" aria-label="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">★</button>
          <?php endfor; ?>
          <span id="rating-label" class="rating-label">Select a rating</span>
        </div>
        <input type="hidden" id="review-rating" value="0">

        <label for="review-comment">Comment <span style="font-weight:400;color:var(--color-text-secondary)">(optional)</span></label>
        <textarea id="review-comment" rows="3" placeholder="Share your experience with this provider…"></textarea>

        <div id="review-error" class="form-error" role="alert" hidden></div>

        <button type="submit" class="btn btn-primary" id="btn-submit-review">Submit Review</button>
      </form>
    </div>
  </div>

  <!-- Reviewed confirmation (shown when already reviewed) -->
  <div id="reviewed-section" hidden>
    <div class="reviewed-banner">
      <span class="reviewed-icon">✓</span>
      <div>
        <strong>Review submitted</strong>
        <p>You have already reviewed this job.</p>
      </div>
    </div>
  </div>
</section>

<!-- Accept Modal (collects confirmed location + date/time) -->
<div id="accept-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="accept-modal-title" hidden>
  <div class="modal-backdrop"></div>
  <div class="modal-box">
    <h2 id="accept-modal-title">Confirm Job Details</h2>
    <p class="modal-desc">Confirm the location and date before accepting. The provider will be notified.</p>
    <form id="accept-form" novalidate>
      <input type="hidden" id="accept-offer-id">
      <label for="accept-location">Location <span aria-hidden="true">*</span></label>
      <input id="accept-location" type="text" required placeholder="e.g. Bole, Addis Ababa">
      <label for="accept-date">Date <span aria-hidden="true">*</span></label>
      <input id="accept-date" type="date" required>
      <label for="accept-time">Time <span aria-hidden="true">*</span></label>
      <input id="accept-time" type="time" required>
      <div id="accept-error" class="form-error" role="alert" hidden></div>
      <div class="modal-actions">
        <button type="submit" class="btn btn-success" id="btn-confirm-accept">Accept Offer</button>
        <button type="button" class="btn btn-ghost"   id="btn-cancel-accept">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Counter Modal -->
<div id="counter-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="counter-modal-title" hidden>
  <div class="modal-backdrop"></div>
  <div class="modal-box">
    <h2 id="counter-modal-title">Send Counter-Offer</h2>
    <p class="modal-desc">Propose a different price to the provider. They will be notified.</p>
    <form id="counter-form" novalidate>
      <input type="hidden" id="counter-offer-id">
      <label for="counter-price">Your Counter Price (ETB) <span aria-hidden="true">*</span></label>
      <input id="counter-price" type="number" min="1" step="0.01" required placeholder="e.g. 400">
      <label for="counter-message">Message <span style="font-weight:400;color:var(--color-text-secondary)">(optional)</span></label>
      <textarea id="counter-message" rows="2" placeholder="Explain your counter-offer…"></textarea>
      <div id="counter-error" class="form-error" role="alert" hidden></div>
      <div class="modal-actions">
        <button type="submit" class="btn btn-primary" id="btn-send-counter">Send Counter</button>
        <button type="button" class="btn btn-ghost"   id="btn-cancel-counter">Cancel</button>
      </div>
    </form>
  </div>
</div>

<style>
.page-request-detail { padding: 24px 0; max-width: 800px; }
.back-link { display: inline-block; color: var(--color-primary); text-decoration: none; font-size: 0.9rem; margin-bottom: 20px; }
.back-link:hover { text-decoration: underline; }

/* Summary card */
.summary-card {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 10px; padding: 24px; margin-bottom: 28px;
}
.summary-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px; }
.summary-title { font-size: 1.2rem; font-weight: 700; margin: 0; }
.summary-meta { display: flex; flex-wrap: wrap; gap: 14px; font-size: 0.88rem; color: var(--color-text-secondary); margin-bottom: 10px; }
.summary-desc { font-size: 0.95rem; color: var(--color-text); line-height: 1.5; margin: 0; }

/* Status badges */
.status-badge {
  display: inline-block; font-size: 0.78rem; font-weight: 600;
  padding: 4px 10px; border-radius: 12px; white-space: nowrap;
}
.status-badge.Requested   { background: #e8f4fd; color: #0066cc; }
.status-badge.Negotiating { background: #fff3cd; color: #856404; }
.status-badge.Assigned    { background: #d4edda; color: #155724; }
.status-badge.Completed   { background: #e2e3e5; color: #383d41; }
.status-badge.Reviewed    { background: #d1ecf1; color: #0c5460; }

/* Section */
.section-title { font-size: 1.1rem; font-weight: 700; margin: 0 0 14px; display: flex; align-items: center; gap: 8px; }
.count-badge { background: var(--color-primary); color: #fff; font-size: 0.75rem; padding: 2px 7px; border-radius: 10px; }

/* Offer cards */
.offers-list { display: flex; flex-direction: column; gap: 14px; margin-bottom: 28px; }
.offer-card {
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 10px; padding: 18px;
}
.offer-card.status-accepted { border-color: var(--color-success); }
.offer-card.status-rejected { opacity: 0.55; }
.offer-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 10px; }
.provider-info { display: flex; flex-direction: column; gap: 2px; }
.provider-name { font-weight: 600; font-size: 0.95rem; }
.provider-meta { font-size: 0.82rem; color: var(--color-text-secondary); }
.offer-price { font-size: 1.1rem; font-weight: 700; color: var(--color-text); }
.offer-message { font-size: 0.9rem; color: var(--color-text-secondary); margin: 0 0 12px; line-height: 1.5; }
.counter-info {
  background: #fff8e1; border: 1px solid #ffe082; border-radius: 6px;
  padding: 10px 12px; font-size: 0.88rem; margin-bottom: 12px;
}
.counter-info strong { color: #856404; }
.offer-actions { display: flex; flex-wrap: wrap; gap: 8px; }

/* Buttons */
.btn { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.88rem; font-weight: 500; transition: background 0.15s; text-decoration: none; display: inline-block; }
.btn-primary  { background: var(--color-primary); color: #fff; }
.btn-primary:hover  { background: var(--color-primary-hover); }
.btn-success  { background: var(--color-success); color: #fff; }
.btn-success:hover  { background: #218838; }
.btn-danger   { background: #dc3545; color: #fff; }
.btn-danger:hover   { background: #c82333; }
.btn-warning  { background: #ffc107; color: #212529; }
.btn-warning:hover  { background: #e0a800; }
.btn-ghost    { background: transparent; color: var(--color-text-secondary); }
.btn-ghost:hover    { background: #f0f0f0; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }

/* Review */
.review-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 10px; padding: 24px; margin-bottom: 28px; }
.review-intro { color: var(--color-text-secondary); font-size: 0.9rem; margin: 0 0 18px; }
.star-rating { display: flex; align-items: center; gap: 6px; margin-bottom: 16px; }
.star {
  background: none; border: none; font-size: 2rem; cursor: pointer;
  color: #ccc; transition: color 0.1s; padding: 0; line-height: 1;
}
.star.active, .star:hover { color: #f5a623; }
.rating-label { font-size: 0.88rem; color: var(--color-text-secondary); margin-left: 6px; }
.review-card label { display: block; font-size: 0.88rem; font-weight: 600; margin: 0 0 6px; }
.review-card textarea {
  width: 100%; padding: 9px 11px; border: 1px solid var(--color-border);
  border-radius: 6px; font-size: 0.9rem; font-family: inherit; resize: vertical;
}

/* Reviewed banner */
.reviewed-banner {
  display: flex; align-items: center; gap: 14px;
  background: #d4edda; border: 1px solid #c3e6cb; border-radius: 10px; padding: 18px 20px;
}
.reviewed-icon { font-size: 1.6rem; color: var(--color-success); }
.reviewed-banner strong { display: block; font-size: 1rem; }
.reviewed-banner p { margin: 2px 0 0; font-size: 0.88rem; color: #155724; }

/* Modal */
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
.modal-box input[type="number"],
.modal-box input[type="text"],
.modal-box input[type="date"],
.modal-box input[type="time"],
.modal-box textarea {
  width: 100%; padding: 9px 11px; border: 1px solid var(--color-border);
  border-radius: 6px; font-size: 0.9rem; font-family: inherit;
}
.modal-actions { display: flex; gap: 10px; margin-top: 20px; }
.form-error { background: #fde8e8; color: #c0392b; border-radius: 6px; padding: 9px 12px; font-size: 0.88rem; margin-top: 10px; }
.loading-text { color: var(--color-text-secondary); }
.empty-state { text-align: center; padding: 32px; color: var(--color-text-secondary); }
</style>

<script>
(function () {
  'use strict';

  // ── Read request ID from URL ────────────────────────────────────────
  const requestId = new URLSearchParams(location.search).get('id');
  if (!requestId) {
    document.getElementById('request-summary').innerHTML = '<p style="color:#c0392b">No request ID provided.</p>';
    return;
  }

  // ── DOM refs ───────────────────────────────────────────────────────
  const summaryEl      = document.getElementById('request-summary');
  const offersSection  = document.getElementById('offers-section');
  const offersList     = document.getElementById('offers-list');
  const offerCountBadge= document.getElementById('offer-count-badge');
  const reviewSection  = document.getElementById('review-section');
  const reviewedSection= document.getElementById('reviewed-section');

  // Review form
  const reviewForm     = document.getElementById('review-form');
  const reviewRating   = document.getElementById('review-rating');
  const reviewComment  = document.getElementById('review-comment');
  const reviewError    = document.getElementById('review-error');
  const btnSubmitReview= document.getElementById('btn-submit-review');
  const stars          = document.querySelectorAll('.star');
  const ratingLabel    = document.getElementById('rating-label');

  // Accept modal
  const acceptModal    = document.getElementById('accept-modal');
  const acceptForm     = document.getElementById('accept-form');
  const acceptOfferId  = document.getElementById('accept-offer-id');
  const acceptLocation = document.getElementById('accept-location');
  const acceptDate     = document.getElementById('accept-date');
  const acceptTime     = document.getElementById('accept-time');
  const acceptError    = document.getElementById('accept-error');
  const btnConfirmAccept = document.getElementById('btn-confirm-accept');
  const btnCancelAccept  = document.getElementById('btn-cancel-accept');

  // Counter modal
  const counterModal   = document.getElementById('counter-modal');
  const counterForm    = document.getElementById('counter-form');
  const counterOfferId = document.getElementById('counter-offer-id');
  const counterPrice   = document.getElementById('counter-price');
  const counterMessage = document.getElementById('counter-message');
  const counterError   = document.getElementById('counter-error');
  const btnSendCounter = document.getElementById('btn-send-counter');
  const btnCancelCounter = document.getElementById('btn-cancel-counter');

  // ── API helper ─────────────────────────────────────────────────────
  async function api(method, path, body) {
    const opts = { method, credentials: 'include', headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(path, opts);
    return res.json();
  }

  // ── Bootstrap ──────────────────────────────────────────────────────
  let currentRequest = null;

  async function init() {
    await loadRequest();
  }

  async function loadRequest() {
    summaryEl.innerHTML = '<p class="loading-text">Loading request…</p>';
    try {
      const res = await api('GET', `/api/requests/${requestId}`);
      if (!res.success) {
        summaryEl.innerHTML = `<p style="color:#c0392b">${escHtml(res.message || 'Request not found.')}</p>`;
        return;
      }
      currentRequest = res.data;
      renderSummary(currentRequest);
      await loadSections(currentRequest);
    } catch (_) {
      summaryEl.innerHTML = '<p style="color:#c0392b">Failed to load request. Please refresh.</p>';
    }
  }

  // ── Render summary ─────────────────────────────────────────────────
  function renderSummary(r) {
    const budget = r.budget ? `ETB ${Number(r.budget).toLocaleString()}` : 'Not set';
    const date   = r.preferred_date ? new Date(r.preferred_date).toLocaleDateString() : 'Flexible';
    const desc   = r.description || r.title || '';

    summaryEl.innerHTML = `
      <div class="summary-header">
        <h1 class="summary-title">${escHtml(r.category_name || 'Service Request')}</h1>
        <span class="status-badge ${r.status}" data-status="${r.status}">${r.status}</span>
      </div>
      <div class="summary-meta">
        <span>📍 ${escHtml(r.location || 'Location not set')}</span>
        <span>📅 ${escHtml(date)}</span>
        <span>💰 ${budget}</span>
        <span>📋 ${r.offer_count || 0} offer${r.offer_count === 1 ? '' : 's'}</span>
      </div>
      ${desc ? `<p class="summary-desc">${escHtml(desc)}</p>` : ''}
    `;
  }

  // ── Load contextual sections based on status ───────────────────────
  async function loadSections(r) {
    // Show offers panel when there are offers to manage
    if (r.status === 'Negotiating' || r.status === 'Assigned') {
      offersSection.hidden = false;
      await loadOffers(r.status);
    }

    // Show review form only when Completed and no review yet
    if (r.status === 'Completed') {
      offersSection.hidden = false;
      await loadOffers(r.status);
      reviewSection.hidden = false;
    }

    // Show reviewed confirmation
    if (r.status === 'Reviewed') {
      offersSection.hidden = false;
      await loadOffers(r.status);
      reviewedSection.hidden = false;
    }
  }

  // ── Load offers ────────────────────────────────────────────────────
  async function loadOffers(requestStatus) {
    offersList.innerHTML = '<p class="loading-text">Loading offers…</p>';
    try {
      const res = await api('GET', `/api/requests/${requestId}/offers`);
      const offers = res.data || [];
      offerCountBadge.textContent = offers.length;
      renderOffers(offers, requestStatus);
    } catch (_) {
      offersList.innerHTML = '<p class="empty-state">Failed to load offers.</p>';
    }
  }

  // ── Render offers ──────────────────────────────────────────────────
  function renderOffers(offers, requestStatus) {
    if (!offers.length) {
      offersList.innerHTML = '<div class="empty-state"><p>No offers yet.</p></div>';
      return;
    }

    // Determine which actions are available
    const canAct = requestStatus === 'Negotiating';

    offersList.innerHTML = offers.map(o => {
      const stars   = '★'.repeat(Math.round(o.provider_rating || 0)) + '☆'.repeat(5 - Math.round(o.provider_rating || 0));
      const verified = o.is_verified ? ' · <span style="color:var(--color-success);font-weight:600">✓ Verified</span>' : '';
      const statusCls = `status-${(o.status || '').toLowerCase()}`;

      let counterHtml = '';
      if (o.counter_price) {
        counterHtml = `
          <div class="counter-info">
            <strong>Counter-offer sent:</strong> ETB ${Number(o.counter_price).toLocaleString()}
            ${o.counter_message ? `<br><span style="color:var(--color-text-secondary)">${escHtml(o.counter_message)}</span>` : ''}
          </div>`;
      }

      let actionsHtml = '';
      if (canAct && (o.status === 'Pending' || o.status === 'Countered')) {
        actionsHtml = `
          <div class="offer-actions">
            <button class="btn btn-success btn-accept" data-id="${o.id}">Accept</button>
            <button class="btn btn-warning btn-counter" data-id="${o.id}" ${o.status === 'Countered' ? 'disabled title="Already countered"' : ''}>Counter</button>
            <button class="btn btn-danger btn-reject"  data-id="${o.id}">Reject</button>
          </div>`;
      } else if (o.status === 'Accepted') {
        actionsHtml = `<span class="status-badge Assigned" style="margin-top:4px">Accepted</span>`;
      } else if (o.status === 'Rejected') {
        actionsHtml = `<span class="status-badge" style="background:#f8d7da;color:#721c24;margin-top:4px">Rejected</span>`;
      }

      return `
        <div class="offer-card ${statusCls}" data-offer-id="${o.id}">
          <div class="offer-header">
            <div class="provider-info">
              <span class="provider-name">${escHtml(o.provider_name || 'Provider')}</span>
              <span class="provider-meta">${stars} ${Number(o.provider_rating || 0).toFixed(1)}${verified}</span>
            </div>
            <span class="offer-price">ETB ${Number(o.price).toLocaleString()}</span>
          </div>
          ${o.message ? `<p class="offer-message">${escHtml(o.message)}</p>` : ''}
          ${counterHtml}
          ${actionsHtml}
        </div>`;
    }).join('');

    // Attach action listeners
    offersList.querySelectorAll('.btn-accept').forEach(btn => {
      btn.addEventListener('click', () => handleAccept(btn.dataset.id));
    });
    offersList.querySelectorAll('.btn-reject').forEach(btn => {
      btn.addEventListener('click', () => handleReject(btn.dataset.id, btn));
    });
    offersList.querySelectorAll('.btn-counter').forEach(btn => {
      btn.addEventListener('click', () => openCounterModal(btn.dataset.id));
    });
  }

  // ── Accept offer ───────────────────────────────────────────────────
  function openAcceptModal(offerId) {
    acceptOfferId.value = offerId;
    // Pre-fill with existing request values if available
    acceptLocation.value = currentRequest.location || '';
    if (currentRequest.preferred_date) {
      const parts = currentRequest.preferred_date.split(' ');
      acceptDate.value = parts[0] || '';
      acceptTime.value = parts[1] || '';
    } else {
      acceptDate.value = '';
      acceptTime.value = '';
    }
    hideAcceptError();
    acceptModal.hidden = false;
    acceptLocation.focus();
  }

  function closeAcceptModal() {
    acceptModal.hidden = true;
    acceptForm.reset();
    hideAcceptError();
  }

  function showAcceptError(msg) { acceptError.textContent = msg; acceptError.hidden = false; }
  function hideAcceptError()    { acceptError.hidden = true; acceptError.textContent = ''; }

  acceptForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideAcceptError();

    const location = acceptLocation.value.trim();
    const date     = acceptDate.value;
    const time     = acceptTime.value;

    if (!location) { showAcceptError('Location is required.'); return; }
    if (!date)     { showAcceptError('Date is required.'); return; }
    if (!time)     { showAcceptError('Time is required.'); return; }

    btnConfirmAccept.disabled = true;
    btnConfirmAccept.textContent = 'Accepting…';

    try {
      const res = await api('PATCH', `/api/offers/${acceptOfferId.value}/accept`, { location, date, time });
      if (res.success) {
        closeAcceptModal();
        await loadRequest();
      } else {
        showAcceptError(res.message || 'Failed to accept offer.');
      }
    } catch (_) {
      showAcceptError('Network error. Please try again.');
    } finally {
      btnConfirmAccept.disabled = false;
      btnConfirmAccept.textContent = 'Accept Offer';
    }
  });

  btnCancelAccept.addEventListener('click', closeAcceptModal);
  acceptModal.querySelector('.modal-backdrop').addEventListener('click', closeAcceptModal);

  async function handleAccept(offerId) {
    openAcceptModal(offerId);
  }

  // ── Reject offer ───────────────────────────────────────────────────
  async function handleReject(offerId, btn) {
    if (!confirm('Reject this offer? The provider will be notified.')) return;

    btn.disabled = true;
    btn.textContent = 'Rejecting…';

    try {
      const res = await api('PATCH', `/api/offers/${offerId}/reject`);
      if (res.success) {
        // Remove the card from the DOM
        const card = offersList.querySelector(`[data-offer-id="${offerId}"]`);
        if (card) {
          card.querySelector('.offer-actions').innerHTML =
            `<span class="status-badge" style="background:#f8d7da;color:#721c24;margin-top:4px">Rejected</span>`;
          card.classList.add('status-rejected');
        }
      } else {
        alert(res.message || 'Failed to reject offer.');
        btn.disabled = false;
        btn.textContent = 'Reject';
      }
    } catch (_) {
      alert('Network error. Please try again.');
      btn.disabled = false;
      btn.textContent = 'Reject';
    }
  }

  // ── Counter modal ──────────────────────────────────────────────────
  function openCounterModal(offerId) {
    counterOfferId.value = offerId;
    counterPrice.value   = '';
    counterMessage.value = '';
    hideCounterError();
    counterModal.hidden = false;
    counterPrice.focus();
  }

  function closeCounterModal() {
    counterModal.hidden = true;
    counterForm.reset();
    hideCounterError();
  }

  function showCounterError(msg) { counterError.textContent = msg; counterError.hidden = false; }
  function hideCounterError()    { counterError.hidden = true; counterError.textContent = ''; }

  counterForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideCounterError();

    const offerId = counterOfferId.value;
    const price   = parseFloat(counterPrice.value);
    const message = counterMessage.value.trim();

    if (!price || price <= 0) { showCounterError('Counter price must be greater than zero.'); return; }

    btnSendCounter.disabled = true;
    btnSendCounter.textContent = 'Sending…';

    try {
      const res = await api('PATCH', `/api/offers/${offerId}/counter`, {
        counter_price: price,
        counter_message: message,
      });

      if (res.success) {
        closeCounterModal();
        // Reload offers to show updated counter info
        await loadOffers(currentRequest.status);
      } else {
        showCounterError(res.message || 'Failed to send counter-offer.');
      }
    } catch (_) {
      showCounterError('Network error. Please try again.');
    } finally {
      btnSendCounter.disabled = false;
      btnSendCounter.textContent = 'Send Counter';
    }
  });

  btnCancelCounter.addEventListener('click', closeCounterModal);
  counterModal.querySelector('.modal-backdrop').addEventListener('click', closeCounterModal);

  // ── Star rating ────────────────────────────────────────────────────
  const ratingLabels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];

  stars.forEach(star => {
    star.addEventListener('click', () => {
      const val = parseInt(star.dataset.value, 10);
      reviewRating.value = val;
      ratingLabel.textContent = ratingLabels[val] || '';
      stars.forEach(s => s.classList.toggle('active', parseInt(s.dataset.value, 10) <= val));
    });
    star.addEventListener('mouseenter', () => {
      const val = parseInt(star.dataset.value, 10);
      stars.forEach(s => s.classList.toggle('active', parseInt(s.dataset.value, 10) <= val));
    });
  });

  document.querySelector('.star-rating').addEventListener('mouseleave', () => {
    const selected = parseInt(reviewRating.value, 10) || 0;
    stars.forEach(s => s.classList.toggle('active', parseInt(s.dataset.value, 10) <= selected));
  });

  // ── Submit review ──────────────────────────────────────────────────
  reviewForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    reviewError.hidden = true;

    const rating  = parseInt(reviewRating.value, 10);
    const comment = reviewComment.value.trim();

    if (!rating || rating < 1 || rating > 5) {
      reviewError.textContent = 'Please select a rating between 1 and 5.';
      reviewError.hidden = false;
      return;
    }

    btnSubmitReview.disabled = true;
    btnSubmitReview.textContent = 'Submitting…';

    try {
      const res = await api('POST', `/api/requests/${requestId}/review`, { rating, comment });
      if (res.success) {
        reviewSection.hidden  = true;
        reviewedSection.hidden = false;
        // Update the status badge in the summary
        const badge = summaryEl.querySelector('.status-badge');
        if (badge) { badge.className = 'status-badge Reviewed'; badge.textContent = 'Reviewed'; }
      } else {
        reviewError.textContent = res.message || 'Failed to submit review.';
        reviewError.hidden = false;
      }
    } catch (_) {
      reviewError.textContent = 'Network error. Please try again.';
      reviewError.hidden = false;
    } finally {
      btnSubmitReview.disabled = false;
      btnSubmitReview.textContent = 'Submit Review';
    }
  });

  // ── Keyboard close ─────────────────────────────────────────────────
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      if (!acceptModal.hidden)  closeAcceptModal();
      if (!counterModal.hidden) closeCounterModal();
    }
  });

  // ── Utility ────────────────────────────────────────────────────────
  function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  init();
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
