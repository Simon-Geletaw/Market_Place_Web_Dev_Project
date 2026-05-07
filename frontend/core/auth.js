/**
 * auth.js
 * Authentication and Session Management
 */

import State from './state.js';

const TOKEN_KEY = 'service_marketplace_token';
const USER_KEY = 'service_marketplace_user';

const Auth = {
  getToken() {
    return localStorage.getItem(TOKEN_KEY);
  },

  getUser() {
    const userStr = localStorage.getItem(USER_KEY);
    if (!userStr) return null;
    try {
      return JSON.parse(userStr);
    } catch (e) {
      return null;
    }
  },

  isAuthenticated() {
    return !!this.getToken() && !!this.getUser();
  },

  setSession(token, user) {
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(USER_KEY, JSON.stringify(user));
    State.set('user', user);
  },

  logout() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    State.set('user', null);
    
    // Determine login route dynamically based on current path depth
    const depth = window.location.pathname.split('/').length - 1;
    let prefix = '';
    for(let i=0; i < depth - 2; i++) prefix += '../'; // Adjust based on frontend structure
    
    // Simplest fallback for now - will be replaced by Router logic
    window.location.href = '/frontend/login/index.html'; 
  },

  requireAuth(allowedRoles = []) {
    if (!this.isAuthenticated()) {
      this.logout();
      return false;
    }
    
    const user = this.getUser();
    if (allowedRoles.length > 0 && !allowedRoles.includes(user.role)) {
      // Unauthorized for this role
      window.location.href = '/frontend/pages/404.html';
      return false;
    }
    
    return true;
  }
};

export default Auth;
