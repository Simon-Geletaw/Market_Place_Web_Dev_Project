/* ============================================================
  api.js — Fetch wrapper for PHP backend
  Base URL: resolved to /backend/public from frontend pages
  ============================================================ */
const explicitBase = typeof window.__API_BASE__ === 'string' ? window.__API_BASE__ : '';
const currentPath = window.location.pathname || '';
const basePrefix = currentPath.includes('/frontend/') ? currentPath.split('/frontend/')[0] : '';
const resolvedBase = `${window.location.origin}${basePrefix}/backend/public`;
const BASE_URL = explicitBase.length > 0 ? explicitBase : resolvedBase;

const api = {
  async _request(method, path, data = null, isFile = false) {
    const opts = {
      method,
      headers: {},
      credentials: 'same-origin'
    };
    if (data) {
      if (isFile) {
        opts.body = data; // FormData
      } else {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(data);
      }
    }
    const res = await fetch(BASE_URL + path, opts);
    const json = await res.json().catch(() => ({ success: false, message: 'Server error' }));
    if (!res.ok) throw { status: res.status, message: json.message || 'Request failed', data: json };
    return json;
  },

  get:    (path)           => api._request('GET', path),
  post:   (path, data)     => api._request('POST', path, data),
  put:    (path, data)     => api._request('PUT', path, data),
  delete: (path)           => api._request('DELETE', path),
  upload: (path, formData) => api._request('POST', path, formData, true),
};

window.api = api;
