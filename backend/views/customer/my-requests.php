<?php
/**
 * Customer: My Requests
 *
 * Lists all service requests owned by the logged-in customer.
 * Shows status, offer count, and links to the detail page for each request.
 * Data from GET /api/requests with optional status filter and sort.
 */
ob_start();
?>
<section class="page-my-requests">
  <div class="page-header">
    <h1>My Requests</h1>
    <a href="/views/customer/create-request.php" class="btn btn-primary">+ New Request</a>
  </div>

  <!-- Filters & Sort -->
  <div class="filter-bar">
    <select id="filter-status" aria-label="Filter by status">
      <option value="">All Statuses</option>
      <option value="Requested">Requested</option>
      <option value="Negotiating">Negotiating</option>
      <option value="Assigned">Assigned</option>
      <option value="Completed">Completed</option>
      <option value="Reviewed">Reviewed</option>
    </select>
    <select id="filter-sort" aria-label="Sort order">
      <option value="desc">Newest First</option>
      <option value="asc">Oldest First</option>
    </select>
    <button id="btn-filter" class="btn btn-secondary">Apply</button>
  </div>

  <!-- Request list -->
  <div id="requests-list" aria-live="polite">
    <p class="loading-text">Loading your requests…</p>
  </div>
</section>

<style>
.page-my-requests { padding: 24px 0; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h1 { margin: 0; font-size: 1.6rem; }

.filter-bar {
  display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
  background: var(--color-surface); border: 1px solid var(--color-border);
  border-radius: 8px; padding: 14px 16px; margin-bottom: 24px;
}
.filter-bar select {
  padding: 8px 10px; border: 1px solid var(--color-border); border-radius: 6px;
  font-size: 0.9rem; min-width: 160px;
}

/* Request table-style list */
.request-table { width: 100%; border-collapse: collapse; background: var(--color-surface); border-radius: 10px; overflow: hidden; border: 1px solid var(--color-border); }
.request-table th {
  text-align: left; padding: 12px 16px; font-size: 0.82rem; font-weight: 600;
  color: var(--color-text-secondary); background: var(--color-muted);
  border-bottom: 1px solid var(--color-border); text-transform: uppercase; letter-spacing: 0.04em;
}
.request-table td { padding: 14px 16px; border-bottom: 1px solid var(--color-border); font-size: 0.9rem; vertical-align: middle; }
.request-table tr:last-child td { border-bottom: none; }
.request-table tr:hover td { background: #f9f9f9; }

.request-desc { font-weight: 500; color: var(--color-text); }
.request-category { font-size: 0.82rem; color: var(--color-text-secondary); margin-top: 2px; }

.status-badge {
  display: inline-block; font-size: 0.75rem; font-weight: 600;
  padding: 3px 9px; border-radius: 12px; white-space: nowrap;
}
.status-badge.Requested   { background: #e8f4fd; color: #0066cc; }
.status-badge.Negotiating { background: #fff3cd; color: #856404; }
.status-badge.Assigned    { background: #d4edda; color: #155724; }
.status-badge.Completed   { background: #e2e3e5; color: #383d41; }
.status-badge.Reviewed    { background: #d1ecf1; color: #0c5460; }

.offer-pill {
  display: inline-block; background: var(--color-muted); border: 1px solid var(--color-border);
  border-radius: 12px; padding: 2px 8px; font-size: 0.8rem; color: var(--color-text-secondary);
}
.offer-pill.has-offers { background: #fff3cd; border-color: #ffc107; color: #856404; font-weight: 600; }

.btn { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.88rem; font-weight: 500; transition: background 0.15s; text-decoration: none; display: inline-block; }
.btn-primary { background: var(--color-primary); color: #fff; }
.btn-primary:hover { background: var(--color-primary-hover); }
.btn-secondary { background: #e9ecef; color: var(--color-text); }
.btn-secondary:hover { background: #dee2e6; }
.btn-sm { padding: 5px 12px; font-size: 0.82rem; }

.empty-state { text-align: center; padding: 48px 16px; color: var(--color-text-secondary); background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 10px; }
.empty-state h3 { margin: 0 0 8px; }
.loading-text { color: var(--color-text-secondary); }

@media (max-width: 640px) {
  .request-table thead { display: none; }
  .request-table, .request-table tbody, .request-table tr, .request-table td { display: block; width: 100%; }
  .request-table tr { border-bottom: 1px solid var(--color-border); padding: 12px 0; }
  .request-table td { border: none; padding: 4px 16px; }
  .request-table td::before { content: attr(data-label) ': '; font-weight: 600; font-size: 0.8rem; color: var(--color-text-secondary); }
}
</style>

<script>
(function () {
  'use strict';

  const listEl     = document.getElementById('requests-list');
  const statusSel  = document.getElementById('filter-status');
  const sortSel    = document.getElementById('filter-sort');
  const btnFilter  = document.getElementById('btn-filter');

  async function api(path) {
    const res = await fetch(path, { credentials: 'include' });
    return res.json();
  }

  async function load() {
    listEl.innerHTML = '<p class="loading-text">Loading…</p>';
    const params = new URLSearchParams();
    if (statusSel.value) params.set('status', statusSel.value);
    params.set('sort', sortSel.value);

    try {
      const res = await api('/api/requests?' + params.toString());
      render(res.data || []);
    } catch (_) {
      listEl.innerHTML = '<p class="empty-state">Failed to load requests. Please refresh.</p>';
    }
  }

  function render(requests) {
    if (!requests.length) {
      listEl.innerHTML = `
        <div class="empty-state">
          <h3>No requests found</h3>
          <p>Post your first service request to get started.</p>
        </div>`;
      return;
    }

    const rows = requests.map(r => {
      const desc     = (r.description || r.title || '').substring(0, 80);
      const date     = r.created_at ? new Date(r.created_at).toLocaleDateString() : '—';
      const budget   = r.budget ? `ETB ${Number(r.budget).toLocaleString()}` : '—';
      const offerCls = r.offer_count > 0 ? 'has-offers' : '';

      return `
        <tr>
          <td data-label="Request">
            <div class="request-desc">${escHtml(desc)}${desc.length === 80 ? '…' : ''}</div>
            <div class="request-category">${escHtml(r.category_name || '')}</div>
          </td>
          <td data-label="Status"><span class="status-badge ${r.status}">${r.status}</span></td>
          <td data-label="Offers"><span class="offer-pill ${offerCls}">${r.offer_count} offer${r.offer_count === 1 ? '' : 's'}</span></td>
          <td data-label="Budget">${escHtml(budget)}</td>
          <td data-label="Location">${escHtml(r.location || '—')}</td>
          <td data-label="Date">${escHtml(date)}</td>
          <td data-label="Action">
            <a href="/views/customer/request-detail.php?id=${encodeURIComponent(r.id)}" class="btn btn-secondary btn-sm">View</a>
          </td>
        </tr>`;
    }).join('');

    listEl.innerHTML = `
      <table class="request-table">
        <thead>
          <tr>
            <th>Request</th>
            <th>Status</th>
            <th>Offers</th>
            <th>Budget</th>
            <th>Location</th>
            <th>Created</th>
            <th></th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>`;
  }

  function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  btnFilter.addEventListener('click', load);
  load();
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
