/**
 * auth.js
 * Authentication and Session Management
 *
 * Backend uses PHP sessions (cookies). Frontend stores only the user object
 * in localStorage for UI rendering (name, role). The actual auth state is
 * the session cookie maintained by the browser automatically.
 *
 * Auth.load() calls GET /api/auth/me to validate the session is still alive.
 */

import State from './state.js';

const USER_KEY = 'service_marketplace_user';

const Auth = {
  /** In-memory user reference after load() completes */
  user: null,

  /** Read the cached user from localStorage (for synchronous access). */
  getUser() {
    if (this.user) return this.user;
    const str = localStorage.getItem(USER_KEY);
    if (!str) return null;
    try {
      return JSON.parse(str);
    } catch {
      return null;
    }
  },

  /**
   * Validate the PHP session against the backend and refresh user state.
   * Called once on page load before any protected logic runs.
   *
   * @returns {Promise<boolean>} true if authenticated
   */
  async load() {
    try {
      // Resolve base URL the same way api.js does (avoid circular dep)
      const base = window.__API_BASE__ ||
        `${window.location.origin}${
          window.location.pathname.includes('/frontend/')
            ? window.location.pathname.split('/frontend/')[0]
            : ''
        }/backend/public`;

      const res  = await fetch(base + '/api/auth/me', {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
      });

      if (!res.ok) {
        this._clearSession();
        return false;
      }

      const json = await res.json();
      if (json.success && json.data && json.data.user) {
        this._setSession(json.data.user);
        return true;
      }

      this._clearSession();
      return false;
    } catch {
      // Network error — keep existing cached user for offline-ish resilience
      const cached = this.getUser();
      if (cached) {
        this.user = cached;
        State.set('user', cached);
        return true;
      }
      return false;
    }
  },

  /**
   * Check if a user is currently loaded (synchronous, no network).
   * Use this only for UI hints — always call load() for real auth checks.
   */
  isAuthenticated() {
    return !!this.getUser();
  },

  /**
   * Guard method — call this at the top of every protected page init().
   * Relies on load() having been called first.
   *
   * @param {string[]} allowedRoles - e.g. ['customer'] or [] for any role
   * @returns {boolean} true if authorized
   */
  requireAuth(allowedRoles = []) {
    const user = this.getUser();
    if (!user) {
      this._redirectToLogin();
      return false;
    }
    if (allowedRoles.length > 0 && !allowedRoles.includes(user.role)) {
      window.location.href = this._resolveRoot('/frontend/pages/404.html');
      return false;
    }
    return true;
  },

  /**
   * Clear session and redirect to login.
   * Called on 401 responses or explicit logout.
   */
  async logout() {
    try {
      const base = window.__API_BASE__ ||
        `${window.location.origin}${
          window.location.pathname.includes('/frontend/')
            ? window.location.pathname.split('/frontend/')[0]
            : ''
        }/backend/public`;

      await fetch(base + '/api/auth/logout', {
        method: 'POST',
        credentials: 'same-origin',
      });
    } catch {
      // Fire and forget — clear local state regardless
    }
    this._clearSession();
    this._redirectToLogin();
  },

  // ------------------------------------------------------------------
  // Internal helpers
  // ------------------------------------------------------------------

  _setSession(user) {
    this.user = user;
    localStorage.setItem(USER_KEY, JSON.stringify(user));
    State.set('user', user);
  },

  _clearSession() {
    this.user = null;
    localStorage.removeItem(USER_KEY);
    State.set('user', null);
  },

  /**
   * Resolve an absolute path accounting for sub-directory deployment.
   */
  _resolveRoot(path) {
    const origin = window.location.origin;
    const prefix = window.location.pathname.includes('/frontend/')
      ? window.location.pathname.split('/frontend/')[0]
      : '';
    return `${origin}${prefix}${path}`;
  },

  _redirectToLogin() {
    window.location.href = this._resolveRoot('/frontend/login/index.html');
  },

  /** Legacy compatibility: getToken() now always returns null (session-based). */
  getToken() { return null; },
};

export default Auth;
