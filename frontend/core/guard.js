/**
 * guard.js
 *
 * Inline auth guard for protected pages.
 * Checks localStorage for a cached user object (synchronous, prevents FOUC).
 * The actual session validity is confirmed asynchronously by Auth.load() on each page.
 *
 * Usage — add to <html> tag:
 *   <html data-role="customer">   // restrict to a specific role
 *   <html data-role="">           // any authenticated user
 *
 * If the user object is missing, redirect immediately to login.
 * Role check against data-role prevents customers from loading provider pages.
 *
 * NOTE: This is a UI guard only (prevents flash-of-wrong-content).
 *       All real authorization is enforced server-side via middleware.
 */

(function() {
  var USER_KEY = 'service_marketplace_user';
  var userStr  = localStorage.getItem(USER_KEY);

  function loginUrl() {
    var origin   = window.location.origin;
    var pathParts = window.location.pathname.split('/frontend/');
    var prefix   = pathParts.length > 1 ? pathParts[0] : '';
    return origin + prefix + '/frontend/login/index.html';
  }

  // No cached user — redirect immediately
  if (!userStr) {
    window.location.href = loginUrl();
    return;
  }

  var user;
  try {
    user = JSON.parse(userStr);
  } catch (e) {
    localStorage.removeItem(USER_KEY);
    window.location.href = loginUrl();
    return;
  }

  if (!user || !user.id || !user.role) {
    localStorage.removeItem(USER_KEY);
    window.location.href = loginUrl();
    return;
  }

  // Role guard: html[data-role] restricts which role can access this page
  var requiredRole = document.documentElement.getAttribute('data-role');
  if (requiredRole && user.role !== requiredRole) {
    // Wrong role — redirect to their own dashboard
    var origin = window.location.origin;
    var pathParts = window.location.pathname.split('/frontend/');
    var prefix = pathParts.length > 1 ? pathParts[0] : '';
    window.location.href = origin + prefix + '/frontend/pages/dashboard/' + user.role + '.html';
    return;
  }
})();
