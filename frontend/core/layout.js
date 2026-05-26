/**
 * layout.js
 * Renders the shared application shell (Navbar + Sidebar) dynamically.
 *
 * Paths are resolved dynamically from window.location so the app works
 * correctly under any XAMPP sub-directory (e.g. /Market_Place_Web_Dev_Project/).
 */

import Auth from './auth.js';

/**
 * Resolve the root prefix for URL building.
 * e.g. returns "/Market_Place_Web_Dev_Project" if served from there, or "" for root.
 */
function getRootPrefix() {
  const parts = window.location.pathname.split('/frontend/');
  return parts.length > 1 ? parts[0] : '';
}

function url(path) {
  return getRootPrefix() + path;
}

/* ── SVG Icon Library ── */
const ICONS = {
  dashboard:  '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>',
  requests:   '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
  browse:     '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
  reviews:    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
  settings:   '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>',
  offers:     '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>',
  assigned:   '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
  completed:  '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
  overview:   '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>',
  shield:     '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
  audit:      '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
  metrics:    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
  bell:       '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>',
};

function icon(name) {
  return `<span class="sidebar__icon">${ICONS[name] || ''}</span>`;
}

function escHtml(s) {
  const d = document.createElement('div');
  d.textContent = s || '';
  return d.innerHTML;
}

function getInitials(name) {
  if (!name) return 'U';
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  return parts[0][0].toUpperCase();
}

const Layout = {
  renderNavbar(containerId = 'app-header', activeMenu = '') {
    const container = document.getElementById(containerId);
    if (!container) return;

    const user   = Auth.getUser();
    const isAuth = !!user;
    const role   = user ? user.role.toLowerCase() : '';

    container.innerHTML = `
      <div class="navbar-container">
        <a href="${url('/frontend/landing_page/index.html')}" class="navbar__logo" aria-label="ServiceLink home">
          <svg class="navbar__logo-icon" width="34" height="34" viewBox="0 0 32 32" fill="none">
            <rect width="32" height="32" rx="8" fill="#0066CC"/>
            <path d="M10 16L14.5 20.5L22 11" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span class="navbar__logo-text">Service<span class="navbar__logo-accent">Link</span></span>
        </a>
        <nav class="navbar__nav">
          ${role === 'provider' ? `
          <a href="${url('/frontend/pages/marketplace.html')}"
             class="navbar__link ${activeMenu === 'browse' ? 'active' : ''}">Browse</a>
          ` : ''}
          ${isAuth ? `
          <a href="${url('/frontend/pages/dashboard/' + role + '.html')}"
             class="navbar__link ${activeMenu === 'dashboard' ? 'active' : ''}">Dashboard</a>
          ` : ''}
        </nav>
        <div class="navbar__actions">
          ${isAuth ? `
            <div class="navbar__user">
              <div class="navbar__avatar">${getInitials(user.name)}</div>
              <div style="display:flex;flex-direction:column;line-height:1.3">
                <strong style="font-size:13px">${escHtml(user.name || 'User')}</strong>
                <span style="font-size:11px;color:var(--color-text-muted);text-transform:capitalize">${escHtml(role)}</span>
              </div>
            </div>
            <button class="btn btn--secondary btn--sm" id="navLogoutBtn">Sign Out</button>
          ` : `
            <a href="${url('/frontend/login/index.html')}" class="btn btn--ghost">Log in</a>
            <a href="${url('/frontend/register/index.html')}" class="btn btn--primary">Sign up</a>
          `}
        </div>
      </div>
    `;
    container.className = 'app-navbar';

    if (isAuth) {
      document.getElementById('navLogoutBtn')?.addEventListener('click', () => Auth.logout());
    }
  },

  renderSidebar(containerId = 'app-sidebar', role = 'customer', activeItem = '') {
    const container = document.getElementById(containerId);
    if (!container) return;

    const menus = {
      customer: [
        { id: 'dashboard',     icon: 'dashboard', label: 'Dashboard',       href: url('/frontend/pages/dashboard/customer.html') },
        { id: 'requests',      icon: 'requests',  label: 'My Requests',     href: url('/frontend/pages/customer/my-requests.html') },
        { id: 'notifications', icon: 'bell',      label: 'Notifications',   href: url('/frontend/pages/notifications.html') },
        { id: 'reviews',       icon: 'reviews',   label: 'Reviews Given',   href: url('/frontend/pages/customer/reviews-given.html') },
        { id: 'settings',      icon: 'settings',  label: 'Settings',        href: url('/frontend/pages/settings.html') },
      ],
      provider: [
        { id: 'dashboard',     icon: 'dashboard', label: 'Dashboard',        href: url('/frontend/pages/dashboard/provider.html') },
        { id: 'browse',        icon: 'browse',    label: 'Browse Requests',  href: url('/frontend/pages/marketplace.html') },
        { id: 'my-offers',     icon: 'offers',    label: 'My Offers',        href: url('/frontend/pages/provider/my-offers.html') },
        { id: 'assigned',      icon: 'assigned',  label: 'Assigned Jobs',    href: url('/frontend/pages/provider/assigned-jobs.html') },
        { id: 'completed',     icon: 'completed', label: 'Completed Jobs',   href: url('/frontend/pages/provider/completed-jobs.html') },
        { id: 'notifications', icon: 'bell',      label: 'Notifications',    href: url('/frontend/pages/notifications.html') },
        { id: 'reviews',       icon: 'reviews',   label: 'Reviews',          href: url('/frontend/pages/provider/reviews-received.html') },
        { id: 'settings',      icon: 'settings',  label: 'Settings',         href: url('/frontend/pages/settings.html') },
      ],
      admin: [
        { id: 'dashboard',     icon: 'overview',  label: 'Overview',              href: url('/frontend/pages/dashboard/admin.html') },
        { id: 'users',         icon: 'browse',    label: 'User Directory',        href: url('/frontend/pages/admin/users.html') },
        { id: 'verification',  icon: 'shield',    label: 'Provider Verification', href: url('/frontend/pages/admin/verification.html') },
        { id: 'audit-logs',    icon: 'audit',     label: 'Audit Logs',            href: url('/frontend/pages/admin/audit-logs.html') },
        { id: 'metrics',       icon: 'metrics',   label: 'Metrics',               href: url('/frontend/pages/admin/metrics.html') },
        { id: 'settings',      icon: 'settings',  label: 'Settings',              href: url('/frontend/pages/settings.html') },
      ],
    };

    const items = menus[role] || menus.customer;
    const roleLabel = { customer: 'Customer', provider: 'Provider', admin: 'Admin' }[role] || 'Menu';

    container.innerHTML = `
      <div class="sidebar">
        <div class="sidebar__label">${escHtml(roleLabel)} Menu</div>
        ${items.map(item => `
          <a href="${item.href}" class="sidebar__item ${activeItem === item.id ? 'active' : ''}" id="sidebarItem-${item.id}">
            ${icon(item.icon)}
            <span class="sidebar__item-label">${escHtml(item.label)}</span>
            ${item.id === 'notifications' ? '<span class="notif-badge" id="sidebarNotifBadge" style="display:none">0</span>' : ''}
          </a>
        `).join('')}
        <div class="sidebar__footer">
          <button class="btn btn--secondary btn--sm btn--full" id="sidebarLogoutBtn">Sign Out</button>
        </div>
      </div>
    `;
    container.className = 'app-sidebar';
    document.getElementById('sidebarLogoutBtn')?.addEventListener('click', () => Auth.logout());
  },

  renderShell(role = 'customer', activeSidebar = '', activeNavbar = 'dashboard') {
    this.renderNavbar('app-header', activeNavbar);
    this.renderSidebar('app-sidebar', role, activeSidebar);
    this.loadNotifBadge();
  },

  async loadNotifBadge() {
    const badge = document.getElementById('sidebarNotifBadge');
    if (!badge) return;

    const refresh = async () => {
      try {
        const origin = window.location.origin;
        const parts  = window.location.pathname.split('/frontend/');
        const prefix = parts.length > 1 ? parts[0] : '';
        const res    = await fetch(`${origin}${prefix}/backend/public/api/notifications`, {
          credentials: 'include'
        });
        if (!res.ok) return;
        const json   = await res.json();
        const items  = Array.isArray(json.data) ? json.data : [];
        const unread = items.filter(n => n.is_read === false || n.is_read === 0).length;
        if (unread > 0) {
          badge.textContent  = unread > 99 ? '99+' : String(unread);
          badge.style.display = 'inline-flex';
        } else {
          badge.style.display = 'none';
        }
      } catch {
        // Silent fail — badge just won't appear
      }
    };

    await refresh();
    const timer = setInterval(refresh, 60_000);
    window.addEventListener('pagehide', () => clearInterval(timer), { once: true });
  },
};

export default Layout;
