/**
 * layout.js
 * Renders the shared application shell (Navbar, Sidebar) dynamically
 */

import Auth from './auth.js';

const Layout = {
  renderNavbar(containerId = 'app-header', activeMenu = '') {
    const container = document.getElementById(containerId);
    if (!container) return;

    const user = Auth.getUser();
    const isAuth = !!user;

    const navbarHTML = `
      <div class="navbar-container">
        <a href="/frontend/landing_page/index.html" class="navbar__logo">
          <div class="navbar__logo-icon">S</div>Service<span class="navbar__logo-accent">Link</span>
        </a>
        <nav class="navbar__nav">
          <a href="/frontend/pages/marketplace.html" class="navbar__link ${activeMenu === 'browse' ? 'active' : ''}">Browse</a>
          ${isAuth ? `<a href="/frontend/pages/dashboard/${user.role}.html" class="navbar__link ${activeMenu === 'dashboard' ? 'active' : ''}">Dashboard</a>` : ''}
        </nav>
        <div class="navbar__actions">
          ${isAuth ? `
            <div class="navbar__user">
              <strong>${user.first_name || user.name || 'User'}</strong>
              <span class="badge badge--requested">${user.role}</span>
            </div>
            <button class="btn btn--secondary btn--sm" id="navLogoutBtn">Logout</button>
          ` : `
            <a href="/frontend/login/index.html" class="btn btn--ghost">Log in</a>
            <a href="/frontend/register/index.html" class="btn btn--primary">Sign up</a>
          `}
        </div>
      </div>
    `;

    container.innerHTML = navbarHTML;
    container.className = 'app-navbar';

    if (isAuth) {
      document.getElementById('navLogoutBtn').addEventListener('click', () => Auth.logout());
    }
  },

  renderSidebar(containerId = 'app-sidebar', role = 'customer', activeItem = '') {
    const container = document.getElementById(containerId);
    if (!container) return;

    let items = [];

    if (role === 'customer') {
      items = [
        { id: 'dashboard', label: '📊 Dashboard', href: '/frontend/pages/dashboard/customer.html' },
        { id: 'requests', label: '📋 My Requests', href: '/frontend/pages/customer/my-requests.html' },
        { id: 'browse', label: '🔍 Browse Services', href: '/frontend/pages/marketplace.html' },
        { id: 'reviews', label: '⭐ Reviews Given', href: '/frontend/pages/customer/reviews-given.html' },
        { id: 'settings', label: '⚙️ Settings', href: '/frontend/pages/settings.html' },
      ];
    } else if (role === 'provider') {
      items = [
        { id: 'dashboard', label: '📊 Dashboard', href: '/frontend/pages/dashboard/provider.html' },
        { id: 'browse', label: '🔍 Browse Requests', href: '/frontend/pages/marketplace.html' },
        { id: 'offers', label: '📤 My Offers', href: '/frontend/pages/provider/my-offers.html' },
        { id: 'assigned', label: '✅ Assigned Jobs', href: '/frontend/pages/provider/assigned-jobs.html' },
        { id: 'completed', label: '🎉 Completed Jobs', href: '/frontend/pages/provider/completed-jobs.html' },
        { id: 'reviews', label: '⭐ Reviews Received', href: '/frontend/pages/provider/reviews-received.html' },
        { id: 'settings', label: '⚙️ Settings', href: '/frontend/pages/settings.html' },
      ];
    } else if (role === 'admin') {
      items = [
        { id: 'dashboard', label: '📊 Overview', href: '/frontend/pages/dashboard/admin.html' },
        { id: 'verification', label: '🛡️ Provider Verification', href: '/frontend/pages/admin/verification.html' },
        { id: 'audit', label: '📋 Audit Logs', href: '/frontend/pages/admin/audit-logs.html' },
        { id: 'metrics', label: '📈 System Metrics', href: '/frontend/pages/admin/metrics.html' },
        { id: 'settings', label: '⚙️ Settings', href: '/frontend/pages/settings.html' },
      ];
    }

    const sidebarHTML = `
      <div class="sidebar">
        <div class="sidebar__label">${role.toUpperCase()} MENU</div>
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

    container.innerHTML = sidebarHTML;
    container.className = 'app-sidebar';

    document.getElementById('sidebarLogoutBtn').addEventListener('click', () => Auth.logout());
  },

  renderShell(role = 'customer', activeSidebar = '', activeNavbar = 'dashboard') {
    // Ensure the body has the right basic structure
    let header = document.getElementById('app-header');
    if (!header) {
      header = document.createElement('header');
      header.id = 'app-header';
      document.body.prepend(header);
    }

    let shell = document.querySelector('.app-shell');
    if (!shell) {
      console.warn('No .app-shell found. Wrapping existing content.');
      const mainContent = document.body.innerHTML;
      document.body.innerHTML = '';
      document.body.appendChild(header);
      
      shell = document.createElement('div');
      shell.className = 'app-shell';
      
      const sidebar = document.createElement('aside');
      sidebar.id = 'app-sidebar';
      
      const main = document.createElement('main');
      main.className = 'app-content';
      main.innerHTML = mainContent; // Move everything into main

      shell.appendChild(sidebar);
      shell.appendChild(main);
      document.body.appendChild(shell);
    }

    this.renderNavbar('app-header', activeNavbar);
    this.renderSidebar('app-sidebar', role, activeSidebar);
  }
};

export default Layout;
