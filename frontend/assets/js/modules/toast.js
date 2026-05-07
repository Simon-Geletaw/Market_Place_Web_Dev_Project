/* toast.js — notification helper */
const Toast = (() => {
  let container;
  function getContainer() {
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    return container;
  }
  function show(message, type = 'info', duration = 3500) {
    const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
    const t = document.createElement('div');
    t.className = `toast toast--${type}`;
    t.innerHTML = `<span>${icons[type] || ''}</span> <span>${message}</span>`;
    getContainer().appendChild(t);
    setTimeout(() => { t.style.animation = 'slideIn 0.2s ease reverse'; setTimeout(() => t.remove(), 200); }, duration);
  }
  return { show, success: (m, d) => show(m,'success',d), error: (m, d) => show(m,'error',d), warning: (m, d) => show(m,'warning',d) };
})();
window.Toast = Toast;
