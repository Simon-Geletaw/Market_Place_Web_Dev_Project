/**
 * toast.js
 * Unified Notification System
 */

const Toast = {
  _container: null,

  _init() {
    if (!this._container) {
      this._container = document.createElement('div');
      this._container.className = 'toast-container';
      // Inline styles for the container specifically, or move to components.css
      this._container.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
      `;
      document.body.appendChild(this._container);
    }
  },

  show(message, type = 'info') {
    this._init();
    
    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    
    // Basic styling for the toast, will map to BEM classes in CSS
    toast.style.cssText = `
      min-width: 250px;
      padding: 12px 16px;
      background: ${type === 'error' ? '#ef4444' : type === 'success' ? '#10b981' : '#334155'};
      color: white;
      border-radius: 8px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      font-family: var(--font-family-base, sans-serif);
      font-size: 14px;
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.3s, transform 0.3s;
      display: flex;
      justify-content: space-between;
      align-items: center;
    `;

    toast.innerHTML = `
      <span>${message}</span>
      <button style="background:none;border:none;color:white;cursor:pointer;font-size:16px;">&times;</button>
    `;

    toast.querySelector('button').onclick = () => this._remove(toast);

    this._container.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
      toast.style.opacity = '1';
      toast.style.transform = 'translateY(0)';
    });

    // Auto remove
    setTimeout(() => {
      this._remove(toast);
    }, 4000);
  },

  _remove(toast) {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(20px)';
    setTimeout(() => {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 300);
  },

  success(msg) { this.show(msg, 'success'); },
  error(msg)   { this.show(msg, 'error'); },
  info(msg)    { this.show(msg, 'info'); }
};

export default Toast;
