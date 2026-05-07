/* ============================================================
   api.js — Fetch wrapper for PHP backend
   Base URL: /api  (served by XAMPP Apache)
   ============================================================ */
const BASE_URL = '/api';

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
