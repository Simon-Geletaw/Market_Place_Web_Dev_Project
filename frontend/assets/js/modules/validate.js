/* validate.js — form validation utilities */
const Validate = {
  required: (v) => v && v.trim().length > 0,
  email: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v),
  minLen: (v, n) => v && v.trim().length >= n,
  phone: (v) => /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/.test(v.trim()),
  number: (v, min, max) => { const n = Number(v); return !isNaN(n) && n >= min && n <= max; },

  field(el, test, msg) {
    const err = el.parentElement.querySelector('.form-error') || (() => {
      const d = document.createElement('div'); d.className = 'form-error'; el.parentElement.appendChild(d); return d;
    })();
    if (!test) { el.classList.add('error'); err.textContent = msg; return false; }
    el.classList.remove('error'); err.textContent = ''; return true;
  },

  passwordStrength(pw) {
    let score = 0;
    if (pw.length >= 8) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    return score <= 1 ? 'weak' : score <= 2 ? 'medium' : 'strong';
  }
};
window.Validate = Validate;
