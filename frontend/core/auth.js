/**
 * auth.js
 * Authentication and Session Management
 *
 * The backend uses PHP sessions (cookies). The browser sends the session cookie
 * automatically with every same-origin request — no Authorization header needed.
 *
 * This module stores only the USER OBJECT (not a token) in localStorage for
 * UI rendering (name, role, avatar, etc.). The real auth state is the
 * live PHP session validated by GET /auth/me.
 */

import State from './state.js';

const USER_KEY = 'service_marketplace_user';

const Auth = {
  /** In-memory cache after load() */
  user: null,

  /** Read cached user from localStorage (synchronous, for UI hints). */
  getUser() {
    if (this.user) return this.user;
    const str = localStorage.getItem(USER_KEY);
    if (!str) return null;
    try { return JSON.parse(str); } catch { return null; }
  },

  /**
   * Public method — called by login/script.js after a successful login response.
   * Stores the user object returned by the backend.
   */
  storeUser(user) {
    this._setSession(user);
  },

  /**
   * Validate the PHP session against the backend and refresh user state.
   * Must be called at the top of every protected page init() before any
   * guarded logic runs.
   *
   * @returns {Promise<boolean>} true if authenticated
   */
  async load() {
    try {
      const base = this._resolveApiBase();
      const res  = await fetch(base + '/auth/me', {
        method: 'GET',
        credentials: 'include',
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
      // Network error — fall back to localStorage cache for resilience
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
   * Synchronous auth check (after load() has run).
   * For quick UI checks only — always call load() for real protection.
   */
  isAuthenticated() {
    return !!this.getUser();
  },

  /**
   * Route guard — call after load() in protected page init().
   * Redirects to login if not authenticated, or to own dashboard if wrong role.
   *
   * @param {string[]} allowedRoles - e.g. ['customer'] or [] for any authenticated user
   * @returns {boolean} true if authorized
   */
  requireAuth(allowedRoles = []) {
    const user = this.getUser();
    if (!user) {
      this._redirectToLogin();
      return false;
    }
    if (allowedRoles.length > 0 && !allowedRoles.includes(user.role)) {
      window.location.href = this._resolveRoot(
        `/frontend/pages/dashboard/${user.role}.html`
      );
      return false;
    }
    return true;
  },

  /**
   * Log out the user. Calls the backend to destroy the session,
   * then clears local state and redirects to login.
   */
  async logout() {
    try {
      await fetch(this._resolveApiBase() + '/auth/logout', {
        method: 'POST',
        credentials: 'include',
      });
    } catch { /* fire and forget */ }
    this._clearSession();
    this._redirectToLogin();
  },

  // ------------------------------------------------------------------
  // Private helpers
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

  _resolveApiBase() {
    if (typeof window.__API_BASE__ === 'string' && window.__API_BASE__.length > 0) {
      return window.__API_BASE__;
    }
    const origin = window.location.origin;
    const parts  = window.location.pathname.split('/frontend/');
    const prefix = parts.length > 1 ? parts[0] : '';
    return `${origin}${prefix}/backend/public/api`;
  },

  _resolveRoot(path) {
    const origin = window.location.origin;
    const parts  = window.location.pathname.split('/frontend/');
    const prefix = parts.length > 1 ? parts[0] : '';
    return `${origin}${prefix}${path}`;
  },

  _redirectToLogin() {
    window.location.href = this._resolveRoot('/frontend/login/index.html');
  },

  /** @deprecated — session-based, no token. Kept for compatibility. */
  getToken() { return null; },
};

export default Auth;
