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

function ensureLeadingSlash(path) {
  return path.startsWith('/') ? path : `/${path}`;
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
          <div class="navbar__logo-icon">S</div>
          <span class="navbar__logo-text">Service<span class="navbar__logo-accent">Link</span></span>
        </a>
        <nav class="navbar__nav">
          <a href="${url('/frontend/pages/marketplace.html')}"
             class="navbar__link ${activeMenu === 'browse' ? 'active' : ''}">Browse</a>
          ${isAuth ? `
          <a href="${url('/frontend/pages/dashboard/' + role + '.html')}"
             class="navbar__link ${activeMenu === 'dashboard' ? 'active' : ''}">Dashboard</a>
          ` : ''}
        </nav>
        <div class="navbar__actions">
          ${isAuth ? `
            <div class="navbar__user">
              <strong>${escHtml(user.name || user.first_name || 'User')}</strong>
              <span class="badge badge--requested">${escHtml(user.role)}</span>
            </div>
            <button class="btn btn--secondary btn--sm" id="navLogoutBtn">Logout</button>
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
        { id: 'dashboard', label: '📊 Dashboard',       href: url('/frontend/pages/dashboard/customer.html') },
        { id: 'requests',  label: '📋 My Requests',     href: url('/frontend/pages/customer/my-requests.html') },
        { id: 'browse',    label: '🔍 Browse Services', href: url('/frontend/pages/marketplace.html') },
        { id: 'reviews',   label: '⭐ Reviews Given',   href: url('/frontend/pages/customer/reviews-given.html') },
        { id: 'settings',  label: '⚙️ Settings',        href: url('/frontend/pages/settings.html') },
      ],
      provider: [
        { id: 'dashboard', label: '📊 Dashboard',       href: url('/frontend/pages/dashboard/provider.html') },
        { id: 'browse',    label: '🔍 Browse Requests', href: url('/frontend/pages/marketplace.html') },
        { id: 'my-offers', label: '📤 My Offers',       href: url('/frontend/pages/provider/my-offers.html') },
        { id: 'assigned',  label: '✅ Assigned Jobs',   href: url('/frontend/pages/provider/assigned-jobs.html') },
        { id: 'completed', label: '🎉 Completed Jobs',  href: url('/frontend/pages/provider/completed-jobs.html') },
        { id: 'reviews',   label: '⭐ Reviews Received',href: url('/frontend/pages/provider/reviews-received.html') },
        { id: 'settings',  label: '⚙️ Settings',        href: url('/frontend/pages/settings.html') },
      ],
      admin: [
        { id: 'dashboard',     label: '📊 Overview',             href: url('/frontend/pages/dashboard/admin.html') },
        { id: 'verification',  label: '🛡️ Provider Verification', href: url('/frontend/pages/admin/verification.html') },
        { id: 'audit-logs',    label: '📋 Audit Logs',           href: url('/frontend/pages/admin/audit-logs.html') },
        { id: 'metrics',       label: '📈 Metrics',              href: url('/frontend/pages/admin/metrics.html') },
        { id: 'settings',      label: '⚙️ Settings',             href: url('/frontend/pages/settings.html') },
      ],
    };

    const items = menus[role] || menus.customer;

    container.innerHTML = `
      <div class="sidebar">
        <div class="sidebar__label">${escHtml(role.toUpperCase())} MENU</div>
        ${items.map(item => `
          <a href="${item.href}" class="sidebar__item ${activeItem === item.id ? 'active' : ''}">
            ${item.label}
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
  },
};

function escHtml(s) {
  const d = document.createElement('div');
  d.textContent = s || '';
  return d.innerHTML;
}

export default Layout;
