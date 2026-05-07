/**
 * api.js
 * Centralized HTTP client.
 *
 * Uses session cookies (credentials: 'same-origin') — no Authorization token.
 * All paths passed to Api methods should NOT include '/api' — it is added automatically.
 * e.g. Api.post('/auth/login', data)  →  POST .../backend/public/api/auth/login
 *
 * All 401 responses trigger Auth.logout() to clear stale local state.
 */

import Auth from './auth.js';

// Resolve backend base URL. Allow override via window.__API_BASE__ for non-standard deployments.
function resolveBase() {
  const origin = window.location.origin;
  const path   = window.location.pathname;
  // Detect if served under a sub-directory (e.g. /Market_Place_Web_Dev_Project/frontend/...)
  const parts  = path.split('/frontend/');
  const prefix = parts.length > 1 ? parts[0] : '';
  return `${origin}${prefix}/backend/public/api`;
}

const BASE_URL = (typeof window.__API_BASE__ === 'string' && window.__API_BASE__.length > 0)
  ? window.__API_BASE__
  : resolveBase();

const Api = {
  async _request(method, path, data = null, isFile = false) {
    const url  = BASE_URL + path;
    const opts = {
      method,
      credentials: 'same-origin',
      headers: {},
    };

    if (data) {
      if (isFile) {
        opts.body = data; // FormData — browser sets Content-Type with boundary
      } else {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(data);
      }
    }

    let res;
    try {
      res = await fetch(url, opts);
    } catch {
      throw { status: 0, message: 'Network error. Check your connection and ensure the server is running.' };
    }

    let json;
    try {
      json = await res.json();
    } catch {
      json = { success: false, message: `Server error (${res.status}). Invalid response.` };
    }

    if (res.status === 401) {
      Auth.logout();
      throw { status: 401, message: 'Session expired. Please log in again.' };
    }

    if (!res.ok) {
      throw { status: res.status, message: json.message || `Request failed (${res.status}).`, data: json };
    }

    return json;
  },

  get:    (path)           => Api._request('GET',    path),
  post:   (path, data)     => Api._request('POST',   path, data),
  patch:  (path, data)     => Api._request('PATCH',  path, data),
  put:    (path, data)     => Api._request('PUT',    path, data),
  delete: (path)           => Api._request('DELETE', path),
  upload: (path, formData) => Api._request('POST',   path, formData, true),
};

export default Api;
