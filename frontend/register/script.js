/**
 * ============================================================
 * ServiceLink - Registration Page Controller
 * ============================================================
 */
import Api from '../core/api.js';
import Toast from '../core/toast.js';

const DOM = {
  form: document.getElementById('registerForm'),
  formStatus: document.getElementById('registerFormStatus'),
  nameGroup: document.getElementById('nameGroup'),
  nameInput: document.getElementById('registerName'),
  nameError: document.getElementById('nameError'),
  emailGroup: document.getElementById('emailGroup'),
  emailInput: document.getElementById('registerEmail'),
  emailError: document.getElementById('emailError'),
  passwordGroup: document.getElementById('passwordGroup'),
  passwordInput: document.getElementById('registerPassword'),
  passwordError: document.getElementById('passwordError'),
  strengthFill: document.getElementById('passwordStrengthFill'),
  strengthLabel: document.getElementById('passwordStrengthLabel'),
  reqLength: document.getElementById('reqLength'),
  reqUppercase: document.getElementById('reqUppercase'),
  reqNumber: document.getElementById('reqNumber'),
  passwordToggle: document.getElementById('passwordToggle'),
  toggleIconShow: null,
  toggleIconHide: null,
  confirmGroup: document.getElementById('confirmPasswordGroup'),
  confirmInput: document.getElementById('registerConfirmPassword'),
  confirmError: document.getElementById('confirmPasswordError'),
  roleGroup: document.getElementById('roleGroup'),
  phoneGroup: document.getElementById('phoneGroup'),
  phoneInput: document.getElementById('registerPhone'),
  phoneError: document.getElementById('phoneError'),
  locationGroup: document.getElementById('locationGroup'),
  locationInput: document.getElementById('registerLocation'),
  locationError: document.getElementById('locationError'),
  termsGroup: document.getElementById('termsGroup'),
  termsInput: document.getElementById('registerTerms'),
  termsError: document.getElementById('termsError'),
  submitBtn: document.getElementById('registerSubmitBtn'),
  btnText: null,
  btnSpinner: null,
};

function populateNestedDOM() {
  const toggle = DOM.passwordToggle;
  if (toggle) {
    DOM.toggleIconShow = toggle.querySelector('.password-toggle__icon--show');
    DOM.toggleIconHide = toggle.querySelector('.password-toggle__icon--hide');
  }
  if (DOM.submitBtn) {
    DOM.btnText = DOM.submitBtn.querySelector('.btn__text');
    DOM.btnSpinner = DOM.submitBtn.querySelector('.btn__spinner');
  }
}

const Validators = {
  name(value) {
    const trimmed = value.trim();
    if (!trimmed) return { valid: false, message: 'Full name is required.' };
    if (trimmed.length < 2) return { valid: false, message: 'Name must be at least 2 characters.' };
    const nameRegex = /^[a-zA-ZÀ-ÿ\u1200-\u137F\s'-]+$/;
    if (!nameRegex.test(trimmed)) return { valid: false, message: 'Name can only contain letters, spaces, hyphens, and apostrophes.' };
    return { valid: true, message: '' };
  },
  email(value) {
    const trimmed = value.trim();
    if (!trimmed) return { valid: false, message: 'Email address is required.' };
    const emailRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
    if (!emailRegex.test(trimmed)) return { valid: false, message: 'Please enter a valid email address.' };
    return { valid: true, message: '' };
  },
  password(value) {
    if (!value) return { valid: false, message: 'Password is required.' };
    if (value.length < 8) return { valid: false, message: 'Password must be at least 8 characters.' };
    if (!/[A-Z]/.test(value)) return { valid: false, message: 'Password must contain at least one uppercase letter.' };
    if (!/\d/.test(value)) return { valid: false, message: 'Password must contain at least one number.' };
    return { valid: true, message: '' };
  },
  confirmPassword(password, confirmValue) {
    if (!confirmValue) return { valid: false, message: 'Please confirm your password.' };
    if (password !== confirmValue) return { valid: false, message: 'Passwords do not match.' };
    return { valid: true, message: '' };
  },
  phone(value) {
    const trimmed = value.trim();
    if (!trimmed) return { valid: false, message: 'Phone number is required.' };
    const phoneRegex = /^(?:\+251|0)(?:9|7)\d{8}$/;
    const digitsOnly = trimmed.replace(/[\s\-()]/g, '');
    if (!phoneRegex.test(digitsOnly)) return { valid: false, message: 'Enter a valid Ethiopian phone number (e.g., +251 911 234 567).' };
    return { valid: true, message: '' };
  },
  location(value) {
    if (!value) return { valid: false, message: 'Please select your sub-city.' };
    return { valid: true, message: '' };
  },
  terms(isChecked) {
    if (!isChecked) return { valid: false, message: 'You must agree to the Terms of Service and Privacy Policy.' };
    return { valid: true, message: '' };
  },
};

const PasswordStrength = {
  analyze(password) {
    const requirements = {
      length: password.length >= 8,
      uppercase: /[A-Z]/.test(password),
      number: /\d/.test(password),
    };
    const metCount = Object.values(requirements).filter(Boolean).length;
    let level = 'weak';
    let score = 0;
    if (metCount === 1) { score = 1; level = 'weak'; }
    else if (metCount === 2) { score = 2; level = 'fair'; }
    else if (metCount === 3) { score = 3; level = 'strong'; }
    return { score, level, requirements };
  },
};

const Sanitizer = {
  stripTags(input) {
    if (typeof input !== 'string') return '';
    return input.replace(/<[^>]*>/g, '');
  },
  escapeHTML(input) {
    if (typeof input !== 'string') return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return input.replace(/[&<>"']/g, (char) => map[char]);
  },
};

const UI = {
  showError(groupEl, inputEl, errorEl, message) {
    if (!groupEl || !errorEl) return;
    if (inputEl) {
      inputEl.classList.add('form-input--error');
      inputEl.classList.remove('form-input--success');
    }
    errorEl.textContent = message;
    groupEl.classList.remove('form-group--error');
    void groupEl.offsetWidth;
    groupEl.classList.add('form-group--error');
  },
  showFieldsetError(groupEl, errorEl, message) {
    this.showError(groupEl, null, errorEl, message);
  },
  clearError(groupEl, inputEl, errorEl) {
    if (!groupEl || !errorEl) return;
    if (inputEl) inputEl.classList.remove('form-input--error');
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
      if (DOM.btnText) DOM.btnText.textContent = 'Creating Account...';
      if (DOM.btnSpinner) DOM.btnSpinner.style.display = 'inline-flex';
    } else {
      DOM.submitBtn.disabled = false;
      DOM.submitBtn.classList.remove('btn--loading');
      if (DOM.btnText) DOM.btnText.textContent = 'Create Account';
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
  updatePasswordStrength(password) {
    const analysis = PasswordStrength.analyze(password);
    if (DOM.strengthFill) {
      DOM.strengthFill.className = 'password-strength__fill';
      if (password.length > 0) DOM.strengthFill.classList.add(`password-strength__fill--${analysis.level}`);
    }
    if (DOM.strengthLabel) {
      DOM.strengthLabel.className = 'password-strength__label';
      if (password.length > 0) {
        const labels = { weak: 'Weak', fair: 'Fair', strong: 'Strong' };
        DOM.strengthLabel.textContent = labels[analysis.level];
        DOM.strengthLabel.classList.add(`password-strength__label--${analysis.level}`);
      } else {
        DOM.strengthLabel.textContent = '';
      }
    }
    if (DOM.reqLength) DOM.reqLength.dataset.met = analysis.requirements.length.toString();
    if (DOM.reqUppercase) DOM.reqUppercase.dataset.met = analysis.requirements.uppercase.toString();
    if (DOM.reqNumber) DOM.reqNumber.dataset.met = analysis.requirements.number.toString();
  },
};

const Handlers = {
  onNameBlur() {
    const result = Validators.name(DOM.nameInput.value);
    if (!result.valid) UI.showError(DOM.nameGroup, DOM.nameInput, DOM.nameError, result.message);
    else { UI.clearError(DOM.nameGroup, DOM.nameInput, DOM.nameError); UI.setSuccess(DOM.nameInput); }
  },
  onNameInput() {
    if (DOM.nameInput.classList.contains('form-input--error')) {
      const result = Validators.name(DOM.nameInput.value);
      if (result.valid) { UI.clearError(DOM.nameGroup, DOM.nameInput, DOM.nameError); UI.setSuccess(DOM.nameInput); }
    }
  },
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
    const password = DOM.passwordInput.value;
    UI.updatePasswordStrength(password);
    if (DOM.passwordInput.classList.contains('form-input--error')) {
      const result = Validators.password(password);
      if (result.valid) { UI.clearError(DOM.passwordGroup, DOM.passwordInput, DOM.passwordError); UI.setSuccess(DOM.passwordInput); }
    }
    if (DOM.confirmInput && DOM.confirmInput.value) {
      const confirmResult = Validators.confirmPassword(password, DOM.confirmInput.value);
      if (confirmResult.valid) { UI.clearError(DOM.confirmGroup, DOM.confirmInput, DOM.confirmError); UI.setSuccess(DOM.confirmInput); }
      else if (DOM.confirmInput.classList.contains('form-input--error') || DOM.confirmInput.classList.contains('form-input--success')) {
        UI.showError(DOM.confirmGroup, DOM.confirmInput, DOM.confirmError, confirmResult.message);
      }
    }
  },
  onPasswordBlur() {
    const result = Validators.password(DOM.passwordInput.value);
    if (!result.valid && DOM.passwordInput.value.length > 0) UI.showError(DOM.passwordGroup, DOM.passwordInput, DOM.passwordError, result.message);
  },
  onConfirmBlur() {
    if (!DOM.confirmInput.value && !DOM.passwordInput.value) return;
    const result = Validators.confirmPassword(DOM.passwordInput.value, DOM.confirmInput.value);
    if (!result.valid) UI.showError(DOM.confirmGroup, DOM.confirmInput, DOM.confirmError, result.message);
    else { UI.clearError(DOM.confirmGroup, DOM.confirmInput, DOM.confirmError); UI.setSuccess(DOM.confirmInput); }
  },
  onConfirmInput() {
    if (DOM.confirmInput.classList.contains('form-input--error')) {
      const result = Validators.confirmPassword(DOM.passwordInput.value, DOM.confirmInput.value);
      if (result.valid) { UI.clearError(DOM.confirmGroup, DOM.confirmInput, DOM.confirmError); UI.setSuccess(DOM.confirmInput); }
    }
  },
  onPhoneBlur() {
    const result = Validators.phone(DOM.phoneInput.value);
    if (!result.valid) UI.showError(DOM.phoneGroup, DOM.phoneInput, DOM.phoneError, result.message);
    else { UI.clearError(DOM.phoneGroup, DOM.phoneInput, DOM.phoneError); UI.setSuccess(DOM.phoneInput); }
  },
  onPhoneInput() {
    if (DOM.phoneInput.classList.contains('form-input--error')) {
      const result = Validators.phone(DOM.phoneInput.value);
      if (result.valid) { UI.clearError(DOM.phoneGroup, DOM.phoneInput, DOM.phoneError); UI.setSuccess(DOM.phoneInput); }
    }
  },
  onLocationChange() {
    const result = Validators.location(DOM.locationInput.value);
    if (result.valid) { UI.clearError(DOM.locationGroup, DOM.locationInput, DOM.locationError); UI.setSuccess(DOM.locationInput); }
  },
  onTermsChange() {
    if (DOM.termsInput.checked) UI.clearError(DOM.termsGroup, null, DOM.termsError);
  },
  async onSubmit(event) {
    event.preventDefault();

    const data = {
      name: Sanitizer.stripTags(DOM.nameInput.value.trim()),
      email: Sanitizer.stripTags(DOM.emailInput.value.trim()),
      password: DOM.passwordInput.value,
      confirmPassword: DOM.confirmInput.value,
      role: document.querySelector('input[name="role"]:checked')?.value || 'customer',
      phone: Sanitizer.stripTags(DOM.phoneInput.value.trim()),
      location: DOM.locationInput.value,
      terms: DOM.termsInput.checked,
    };

    const errors = [];

    const nameResult = Validators.name(data.name);
    if (!nameResult.valid) { UI.showError(DOM.nameGroup, DOM.nameInput, DOM.nameError, nameResult.message); errors.push(nameResult.message); }
    else { UI.clearError(DOM.nameGroup, DOM.nameInput, DOM.nameError); UI.setSuccess(DOM.nameInput); }

    const emailResult = Validators.email(data.email);
    if (!emailResult.valid) { UI.showError(DOM.emailGroup, DOM.emailInput, DOM.emailError, emailResult.message); errors.push(emailResult.message); }
    else { UI.clearError(DOM.emailGroup, DOM.emailInput, DOM.emailError); UI.setSuccess(DOM.emailInput); }

    const passwordResult = Validators.password(data.password);
    if (!passwordResult.valid) { UI.showError(DOM.passwordGroup, DOM.passwordInput, DOM.passwordError, passwordResult.message); errors.push(passwordResult.message); }
    else { UI.clearError(DOM.passwordGroup, DOM.passwordInput, DOM.passwordError); UI.setSuccess(DOM.passwordInput); }

    const confirmResult = Validators.confirmPassword(data.password, data.confirmPassword);
    if (!confirmResult.valid) { UI.showError(DOM.confirmGroup, DOM.confirmInput, DOM.confirmError, confirmResult.message); errors.push(confirmResult.message); }
    else { UI.clearError(DOM.confirmGroup, DOM.confirmInput, DOM.confirmError); UI.setSuccess(DOM.confirmInput); }

    const phoneResult = Validators.phone(data.phone);
    if (!phoneResult.valid) { UI.showError(DOM.phoneGroup, DOM.phoneInput, DOM.phoneError, phoneResult.message); errors.push(phoneResult.message); }
    else { UI.clearError(DOM.phoneGroup, DOM.phoneInput, DOM.phoneError); UI.setSuccess(DOM.phoneInput); }

    const locationResult = Validators.location(data.location);
    if (!locationResult.valid) { UI.showError(DOM.locationGroup, DOM.locationInput, DOM.locationError, locationResult.message); errors.push(locationResult.message); }
    else { UI.clearError(DOM.locationGroup, DOM.locationInput, DOM.locationError); UI.setSuccess(DOM.locationInput); }

    const termsResult = Validators.terms(data.terms);
    if (!termsResult.valid) { UI.showFieldsetError(DOM.termsGroup, DOM.termsError, termsResult.message); errors.push(termsResult.message); }
    else { UI.clearError(DOM.termsGroup, null, DOM.termsError); }

    if (errors.length > 0) {
      UI.announceStatus(`Form has ${errors.length} error${errors.length > 1 ? 's' : ''}. ${errors[0]}`);
      const firstError = DOM.form.querySelector('.form-input--error');
      if (firstError) firstError.focus();
      return;
    }

    UI.setLoading(true);
    UI.announceStatus('Creating your account, please wait...');

    try {
      const result = await Api.post('/auth/register', data);
      UI.setLoading(false);
      
      if (result.success) {
        const container = document.querySelector('.auth-form-container');
        if (container) {
          const header = container.querySelector('.auth-form__header');
          const footer = container.querySelector('.auth-form__footer');
          UI.announceStatus(result.message || 'Account created successfully!');
          
          DOM.form.innerHTML = `
            <div style="text-align:center; padding: 32px 0;">
              <svg width="56" height="56" viewBox="0 0 24 24" fill="none" style="margin: 0 auto 16px;">
                <circle cx="12" cy="12" r="10" stroke="#28A745" stroke-width="2"/>
                <path d="M8 12l3 3 5-5" stroke="#28A745" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <h2 style="font-size:1.25rem; font-weight:700; color:#1A1A1A; margin-bottom:8px;">Account Created!</h2>
              <p style="font-size:0.9rem; color:#666; margin-bottom: 24px;">Welcome to ServiceLink, ${Sanitizer.escapeHTML(data.name)}!</p>
              <a href="../login/index.html" 
                 style="display:inline-block; padding:12px 32px; background:#0066CC; color:#fff; border-radius:8px; font-weight:600; text-decoration:none; font-size:0.95rem;">
                Go to Login
              </a>
            </div>
          `;
          if (header) header.style.display = 'none';
          if (footer) footer.style.display = 'none';
          Toast.success('Account created successfully!');
        }
      } else {
        const errorMsg = result.message || 'Registration failed. Please try again.';
        UI.announceStatus(errorMsg);
        Toast.error(errorMsg);
        
        if (result.errors && typeof result.errors === 'object') {
           Object.keys(result.errors).forEach(key => {
              const msg = Array.isArray(result.errors[key]) ? result.errors[key][0] : result.errors[key];
              if (DOM[`${key}Group`] && DOM[`${key}Error`]) {
                 UI.showError(DOM[`${key}Group`], DOM[`${key}Input`], DOM[`${key}Error`], msg);
              }
           });
        } else {
           UI.showError(DOM.emailGroup, DOM.emailInput, DOM.emailError, errorMsg);
        }
      }
    } catch (error) {
      UI.setLoading(false);
      console.error('Registration error:', error);
      UI.announceStatus('Unable to connect to service.');
      UI.showError(DOM.emailGroup, DOM.emailInput, DOM.emailError, error.message || 'Connection failed.');
      Toast.error(error.message || 'Connection failed.');
    }
  },
};

function init() {
  if (!DOM.form) return;
  populateNestedDOM();

  if (DOM.nameInput) {
    DOM.nameInput.addEventListener('blur', Handlers.onNameBlur);
    DOM.nameInput.addEventListener('input', Handlers.onNameInput);
  }
  if (DOM.emailInput) {
    DOM.emailInput.addEventListener('blur', Handlers.onEmailBlur);
    DOM.emailInput.addEventListener('input', Handlers.onEmailInput);
  }
  if (DOM.passwordInput) {
    DOM.passwordInput.addEventListener('input', Handlers.onPasswordInput);
    DOM.passwordInput.addEventListener('blur', Handlers.onPasswordBlur);
  }
  if (DOM.confirmInput) {
    DOM.confirmInput.addEventListener('blur', Handlers.onConfirmBlur);
    DOM.confirmInput.addEventListener('input', Handlers.onConfirmInput);
  }
  if (DOM.passwordToggle) DOM.passwordToggle.addEventListener('click', UI.togglePasswordVisibility);
  if (DOM.phoneInput) {
    DOM.phoneInput.addEventListener('blur', Handlers.onPhoneBlur);
    DOM.phoneInput.addEventListener('input', Handlers.onPhoneInput);
  }
  if (DOM.locationInput) DOM.locationInput.addEventListener('change', Handlers.onLocationChange);
  if (DOM.termsInput) DOM.termsInput.addEventListener('change', Handlers.onTermsChange);

  DOM.form.addEventListener('submit', Handlers.onSubmit);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
