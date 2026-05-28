'use strict';

document.addEventListener('DOMContentLoaded', () => {

  /* ── Element refs ── */
  const navLinks   = document.querySelectorAll('.navbar__link[data-tab]');
  const tabs       = document.querySelectorAll('.tab-section');
  const statNums   = document.querySelectorAll('.stats-bar__num[data-stat]');

  /* ─────────────────────────────────────────────────────────
     TAB NAVIGATION
     Show only the active section; clicking a nav link switches tabs.
     ───────────────────────────────────────────────────────── */
  function showTab(tabId) {
    tabs.forEach(t => {
      t.hidden = t.dataset.tab !== tabId;
      t.classList.toggle('tab--active', t.dataset.tab === tabId);
    });

    navLinks.forEach(l => {
      l.classList.toggle('active', l.dataset.tab === tabId);
    });

    // Persist in URL hash for deep-linking
    history.replaceState(null, '', '#' + tabId);

    // Trigger stats animation when reviews/stats tab opens
    if (tabId === 'reviews') fetchAndAnimateStats();
  }

  navLinks.forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      showTab(link.dataset.tab);
    });
  });

  // Boot to correct tab (hash or default = home)
  const initTab = location.hash.slice(1) || 'home';
  const validTabs = Array.from(tabs).map(t => t.dataset.tab);
  showTab(validTabs.includes(initTab) ? initTab : 'home');

  /* ─────────────────────────────────────────────────────────
     LIVE STATS — fetch real counts from the backend
     ───────────────────────────────────────────────────────── */
  let statsFetched = false;

  async function fetchAndAnimateStats() {
    if (statsFetched) return;
    statsFetched = true;

    try {
      const res  = await fetch('http://localhost/Market_Place_Web_Dev_Project/backend/public/index.php/api/stats');
      const json = await res.json();
      const data = json.data ?? {};

      // Map API fields → DOM elements
      const map = {
        customers:  data.customers  ?? 0,
        providers:  data.providers  ?? 0,
        avg_rating: data.avg_rating ?? 0,
      };

      statNums.forEach(el => {
        const key    = el.dataset.stat;
        const target = map[key] ?? 0;
        const isDecimal = !Number.isInteger(target);
        animateCount(el, target, isDecimal);
      });

    } catch (err) {
      // Fallback: show placeholder dashes on network error
      statNums.forEach(el => { el.textContent = '—'; });
      console.warn('[stats] Could not reach API:', err);
    }
  }

  function animateCount(el, target, decimal) {
    const duration = 1800;
    const start    = performance.now();
    (function tick(now) {
      const t = Math.min((now - start) / duration, 1);
      const v = (1 - Math.pow(1 - t, 3)) * target;
      el.textContent = decimal ? v.toFixed(1) : Math.floor(v).toLocaleString();
      if (t < 1) requestAnimationFrame(tick);
    })(start);
  }
});
