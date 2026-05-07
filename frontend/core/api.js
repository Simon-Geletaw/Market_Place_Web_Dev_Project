/**
 * api.js
 * Enhanced Fetch API wrapper with interceptors and error handling.
 */

import Auth from './auth.js';
import Toast from './toast.js';

const explicitBase = typeof window.__API_BASE__ === 'string' ? window.__API_BASE__ : '';
const currentPath = window.location.pathname || '';
const basePrefix = currentPath.includes('/frontend/') ? currentPath.split('/frontend/')[0] : '';
const resolvedBase = `${window.location.origin}${basePrefix}/backend/public`;
const BASE_URL = explicitBase.length > 0 ? explicitBase : resolvedBase;

const Api = {
  async _request(method, path, data = null, isFile = false) {
    const opts = {
      method,
      headers: {},
      credentials: 'same-origin' // Adjust if CORS is needed
    };

    // Inject Auth Token
    const token = Auth.getToken();
    if (token) {
      opts.headers['Authorization'] = `Bearer ${token}`;
    }

    if (data) {
      if (isFile) {
        opts.body = data; // FormData
      } else {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(data);
      }
    }

    try {
      const res = await fetch(BASE_URL + path, opts);
      
      let json;
      try {
        json = await res.json();
      } catch (e) {
        json = { success: false, message: 'Server error: Invalid JSON response' };
      }

      // Handle 401 Unauthorized globally
      if (res.status === 401) {
        Auth.logout();
        throw { status: 401, message: 'Session expired. Please log in again.' };
      }

      if (!res.ok) {
        throw { status: res.status, message: json.message || 'Request failed', data: json };
      }

      return json;
    } catch (error) {
      // Normalize network errors vs API errors
      if (!error.status) {
        console.error('Network Error:', error);
        throw { status: 500, message: 'Network error. Please check your connection.' };
      }
      throw error;
    }
  },

  get:    (path)           => Api._request('GET', path),
  post:   (path, data)     => Api._request('POST', path, data),
  put:    (path, data)     => Api._request('PUT', path, data),
  delete: (path)           => Api._request('DELETE', path),
  upload: (path, formData) => Api._request('POST', path, formData, true),
};

export default Api;
