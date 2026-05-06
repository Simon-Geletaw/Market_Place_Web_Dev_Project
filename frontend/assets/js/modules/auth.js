/* auth.js — session state & role-based routing */
const Auth = {
  user: null,

  async load() {
    try {
      const res = await api.get('/auth/me');
      Auth.user = res.data;
    } catch {
      Auth.user = null;
    }
    return Auth.user;
  },

  isLoggedIn() { return !!Auth.user; },

  role() { return Auth.user?.role || null; },

  requireAuth() {
    if (!Auth.user) { window.location.href = '/frontend/pages/auth/login.html'; return false; }
    return true;
  },

  requireRole(role) {
    if (!Auth.requireAuth()) return false;
    if (Auth.user.role !== role) {
      const map = { customer: 'customer', provider: 'provider', admin: 'admin' };
      window.location.href = `/frontend/pages/dashboard/${map[Auth.user.role] || 'customer'}.html`;
      return false;
    }
    return true;
  },

  async logout() {
    try { await api.post('/auth/logout'); } catch {}
    Auth.user = null;
    window.location.href = '/frontend/landing_page/index.html';
  },

  renderNavUser(el) {
    if (!el || !Auth.user) return;
    el.innerHTML = `<div class="navbar__avatar">${Auth.user.full_name?.[0] || 'U'}</div>
      <span style="font-size:13px;font-weight:500">${Auth.user.full_name || 'User'}</span>`;
  }
};
window.Auth = Auth;
