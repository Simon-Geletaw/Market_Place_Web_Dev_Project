/**
 * api.js
 * Centralized HTTP client.
 *
 * Uses session cookies (credentials: 'same-origin') — no Authorization token needed.
 * All 401 responses trigger Auth.logout() to clear stale local state.
 */

import Auth from './auth.js';
import Toast from './toast.js';

// Resolve backend base URL. Allow override via window.__API_BASE__ for non-standard deployments.
const explicitBase = typeof window.__API_BASE__ === 'string' ? window.__API_BASE__ : '';

function resolveBase() {
  const origin = window.location.origin;
  const path   = window.location.pathname;
  const prefix = path.includes('/frontend/') ? path.split('/frontend/')[0] : '';
  return `${origin}${prefix}/backend/public`;
}

const BASE_URL = explicitBase.length > 0 ? explicitBase : resolveBase();

const Api = {
  async _request(method, path, data = null, isFile = false) {
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
      res = await fetch(BASE_URL + path, opts);
    } catch {
      throw { status: 0, message: 'Network error. Check your connection.' };
    }

    let json;
    try {
      json = await res.json();
    } catch {
      json = { success: false, message: 'Server returned invalid JSON.' };
    }

    if (res.status === 401) {
      Auth.logout();
      throw { status: 401, message: 'Session expired. Please log in again.' };
    }

    if (!res.ok) {
      throw { status: res.status, message: json.message || 'Request failed.', data: json };
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
