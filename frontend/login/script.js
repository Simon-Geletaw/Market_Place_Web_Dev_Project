/**
 * ============================================================
 * ServiceLink — Login Page Controller
 * ============================================================
 */
import Api from '../core/api.js?v=2';
import Auth from '../core/auth.js';
import Toast from '../core/toast.js';

const DOM = {
  form: document.getElementById('loginForm'),
  formStatus: document.getElementById('loginFormStatus'),
  emailGroup: document.getElementById('emailGroup'),
  emailInput: document.getElementById('loginEmail'),
  emailError: document.getElementById('emailError'),
  passwordGroup: document.getElementById('passwordGroup'),
  passwordInput: document.getElementById('loginPassword'),
  passwordError: document.getElementById('passwordError'),
  passwordToggle: document.getElementById('passwordToggle'),
  toggleIconShow: null,
  toggleIconHide: null,
  submitBtn: document.getElementById('loginSubmitBtn'),
  btnText: null,
  btnSpinner: null,
};

function populateNestedDOM() {
  if (DOM.passwordToggle) {
    DOM.toggleIconShow = DOM.passwordToggle.querySelector('.password-toggle__icon--show');
    DOM.toggleIconHide = DOM.passwordToggle.querySelector('.password-toggle__icon--hide');
  }
  if (DOM.submitBtn) {
    DOM.btnText = DOM.submitBtn.querySelector('.btn__text');
    DOM.btnSpinner = DOM.submitBtn.querySelector('.btn__spinner');
  }
}

const Validators = {
  email(value) {
    const trimmed = value.trim();
    if (!trimmed) return { valid: false, message: 'Email address is required.' };
    const emailRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
    if (!emailRegex.test(trimmed)) return { valid: false, message: 'Please enter a valid email address.' };
    return { valid: true, message: '' };
  },
  password(value) {
    if (!value || value.length < 1) return { valid: false, message: 'Password is required.' };
    return { valid: true, message: '' };
  },
};

const Sanitizer = {
  stripTags(input) {
    if (typeof input !== 'string') return '';
    return input.replace(/<[^>]*>/g, '');
  }
};

const UI = {
  showError(groupEl, inputEl, errorEl, message) {
    if (!groupEl || !inputEl || !errorEl) return;
    inputEl.classList.add('form-input--error');
    inputEl.classList.remove('form-input--success');
    errorEl.textContent = message;
    groupEl.classList.remove('form-group--error');
    void groupEl.offsetWidth;
    groupEl.classList.add('form-group--error');
  },
  clearError(groupEl, inputEl, errorEl) {
    if (!groupEl || !inputEl || !errorEl) return;
    inputEl.classList.remove('form-input--error');
    groupEl.classList.remove('form-group--error');
    errorEl.textContent = '';
  },
  setSuccess(inputEl) {
    if (!inputEl) return;
    inputEl.classList.remove('form-input--error');
    inputEl.classList.add('form-input--success');
  },
  announceStatus(message) {
    if (DOM.formStatus) DOM.formStatus.textContent = message;
  },
  setLoading(isLoading) {
    if (!DOM.submitBtn) return;
    if (isLoading) {
      DOM.submitBtn.disabled = true;
      DOM.submitBtn.classList.add('btn--loading');
      if (DOM.btnText) DOM.btnText.textContent = 'Logging In...';
      if (DOM.btnSpinner) DOM.btnSpinner.style.display = 'inline-flex';
    } else {
      DOM.submitBtn.disabled = false;
      DOM.submitBtn.classList.remove('btn--loading');
      if (DOM.btnText) DOM.btnText.textContent = 'Log In';
      if (DOM.btnSpinner) DOM.btnSpinner.style.display = 'none';
    }
  },
  togglePasswordVisibility() {
    const input = DOM.passwordInput;
    const toggle = DOM.passwordToggle;
    if (!input || !toggle) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    if (DOM.toggleIconShow) DOM.toggleIconShow.style.display = isPassword ? 'none' : 'block';
    if (DOM.toggleIconHide) DOM.toggleIconHide.style.display = isPassword ? 'block' : 'none';
    toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    toggle.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
    input.focus();
  },
};

const Handlers = {
  onEmailBlur() {
    const result = Validators.email(DOM.emailInput.value);
    if (!result.valid) UI.showError(DOM.emailGroup, DOM.emailInput, DOM.emailError, result.message);
    else { UI.clearError(DOM.emailGroup, DOM.emailInput, DOM.emailError); UI.setSuccess(DOM.emailInput); }
  },
  onEmailInput() {
    if (DOM.emailInput.classList.contains('form-input--error')) {
      const result = Validators.email(DOM.emailInput.value);
      if (result.valid) { UI.clearError(DOM.emailGroup, DOM.emailInput, DOM.emailError); UI.setSuccess(DOM.emailInput); }
    }
  },
  onPasswordInput() {
    if (DOM.passwordInput.classList.contains('form-input--error')) {
      UI.clearError(DOM.passwordGroup, DOM.passwordInput, DOM.passwordError);
    }
  },
  async onSubmit(event) {
    event.preventDefault();
    const email = Sanitizer.stripTags(DOM.emailInput.value.trim());
    const password = DOM.passwordInput.value;

    const emailResult = Validators.email(email);
    const passwordResult = Validators.password(password);
    const errors = [];

    if (!emailResult.valid) {
      UI.showError(DOM.emailGroup, DOM.emailInput, DOM.emailError, emailResult.message);
      errors.push(emailResult.message);
    } else {
      UI.clearError(DOM.emailGroup, DOM.emailInput, DOM.emailError);
      UI.setSuccess(DOM.emailInput);
    }

    if (!passwordResult.valid) {
      UI.showError(DOM.passwordGroup, DOM.passwordInput, DOM.passwordError, passwordResult.message);
      errors.push(passwordResult.message);
    } else {
      UI.clearError(DOM.passwordGroup, DOM.passwordInput, DOM.passwordError);
    }

    if (errors.length > 0) {
      UI.announceStatus(`Form has ${errors.length} errors.`);
      const firstError = DOM.form.querySelector('.form-input--error');
      if (firstError) firstError.focus();
      return;
    }

    UI.setLoading(true);
    UI.announceStatus('Logging in, please wait...');

    try {
      const data = await Api.post('/auth/login', { email, password });
      UI.setLoading(false);
      if (data.success && data.data && data.data.user) {
        UI.announceStatus('Login successful! Redirecting...');

        // Store the user object in localStorage for UI rendering.
        // The PHP session cookie is set automatically by the browser.
        Auth.storeUser(data.data.user);
        Toast.success('Login successful!');

        const role = (data.data.user.role || 'customer').toLowerCase();
        setTimeout(() => {
          const base = window.location.pathname.split('/frontend/')[0];
          if (role === 'admin')    window.location.href = base + '/frontend/pages/dashboard/admin.html';
          else if (role === 'provider') window.location.href = base + '/frontend/pages/dashboard/provider.html';
          else window.location.href = base + '/frontend/pages/dashboard/customer.html';
        }, 400);
      } else {
        const msg = data.message || 'Incorrect password or email.';
        UI.showError(DOM.emailGroup, DOM.emailInput, DOM.emailError, msg);
        UI.showError(DOM.passwordGroup, DOM.passwordInput, DOM.passwordError, msg);
        UI.announceStatus(msg);
        Toast.error(msg);
      }
    } catch (error) {
      UI.setLoading(false);
      const msg = error.message || 'Unable to connect. Is the server running?';
      UI.showError(DOM.emailGroup, DOM.emailInput, DOM.emailError, msg);
      UI.showError(DOM.passwordGroup, DOM.passwordInput, DOM.passwordError, msg);
      UI.announceStatus(msg);
      Toast.error(msg);
    }
  },
};

function init() {
  if (!DOM.form || !DOM.emailInput || !DOM.passwordInput) return;
  populateNestedDOM();

  DOM.emailInput.addEventListener('blur', Handlers.onEmailBlur);
  DOM.emailInput.addEventListener('input', Handlers.onEmailInput);
  DOM.passwordInput.addEventListener('input', Handlers.onPasswordInput);
  
  if (DOM.passwordToggle) DOM.passwordToggle.addEventListener('click', UI.togglePasswordVisibility);
  
  DOM.form.addEventListener('submit', Handlers.onSubmit);
  
  DOM.passwordInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      DOM.form.dispatchEvent(new Event('submit', { cancelable: true }));
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
