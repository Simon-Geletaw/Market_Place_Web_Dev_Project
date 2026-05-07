/**
 * guard.js
 * Include this script in the <head> of protected pages to prevent FOUC (Flash of Unauthenticated Content).
 * It runs synchronously before the DOM paints.
 */

(function() {
  const TOKEN_KEY = 'service_marketplace_token';
  const USER_KEY = 'service_marketplace_user';
  
  const token = localStorage.getItem(TOKEN_KEY);
  const userStr = localStorage.getItem(USER_KEY);
  
  if (!token || !userStr) {
    // Unauthenticated, redirect immediately
    // Calculate path depth to redirect to /frontend/login/index.html
    const depth = window.location.pathname.split('/').length - 1;
    let prefix = '';
    for(let i=0; i < depth - 2; i++) prefix += '../';
    
    window.location.href = '/frontend/login/index.html';
    return;
  }

  try {
    const user = JSON.parse(userStr);
    
    // Check Role if specified via a data attribute on the html tag: <html data-role="customer">
    const requiredRole = document.documentElement.getAttribute('data-role');
    if (requiredRole && user.role !== requiredRole) {
      window.location.href = '/frontend/pages/404.html';
    }
  } catch(e) {
    window.location.href = '/frontend/login/index.html';
  }
})();
