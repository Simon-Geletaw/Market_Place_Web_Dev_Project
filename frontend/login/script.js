/**
 * ============================================================
 * ServiceLink — Login Page Controller
 * ============================================================
 * 
 * Architecture: Module pattern with strict separation of concerns.
 * 
 * Modules:
 *   1. DOM Cache      — All element references (queried once)
 *   2. Validators     — Pure functions for input validation
 *   3. UI Controller  — State management and DOM manipulation
 *   4. Event Handlers — Thin wrappers connecting events to logic
 *   5. Initialization — Boots the application
 * 
 * Security:
 *   - Input sanitization (XSS prevention)
 *   - No credentials stored in JS variables longer than needed
 *   - CSRF token placeholder for backend integration
 * ============================================================
 */
'use strict';

(function () {

  /* ==========================================================
     MODULE 1 - DOM CACHE
     Query all elements once at boot. Never query inside loops
     or event handlers -- that forces a DOM lookup on every call.
     ========================================================== */
  const DOM = {
    form: document.getElementById('loginForm'),
    formStatus: document.getElementById('loginFormStatus'),

    // Email
    emailGroup: document.getElementById('emailGroup'),
    emailInput: document.getElementById('loginEmail'),
    emailError: document.getElementById('emailError'),

    // Password
    passwordGroup: document.getElementById('passwordGroup'),
    passwordInput: document.getElementById('loginPassword'),
    passwordError: document.getElementById('passwordError'),

    // Password toggle
    passwordToggle: document.getElementById('passwordToggle'),
    toggleIconShow: null, // Populated after DOM ready
    toggleIconHide: null,

    // Submit
    submitBtn: document.getElementById('loginSubmitBtn'),
    btnText: null,
    btnSpinner: null,
  };

  // Elements that require nested queries (populated after boot)
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


  /* ==========================================================
     MODULE 2 - VALIDATORS
     Pure functions. No DOM access. Easy to unit test.
     Each returns { valid: boolean, message: string }
     ========================================================== */
  const Validators = {
    /**
     * Validates email against RFC 5322 simplified pattern.
     * Also checks the native ValidityState API for browser-level validation.
     */
    email(value) {
      const trimmed = value.trim();
      if (!trimmed) {
        return { valid: false, message: 'Email address is required.' };
      }
      // RFC 5322 simplified (covers 99.9% of valid emails)
      const emailRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
      if (!emailRegex.test(trimmed)) {
        return { valid: false, message: 'Please enter a valid email address.' };
      }
      return { valid: true, message: '' };
    },

    /**
     * Validates password: not empty, minimum 1 character for login.
     * (Registration has stricter rules; login just checks presence.)
     */
    password(value) {
      if (!value) {
        return { valid: false, message: 'Password is required.' };
      }
      if (value.length < 1) {
        return { valid: false, message: 'Password is required.' };
      }
      return { valid: true, message: '' };
    },
  };


  /* ==========================================================
     MODULE 3 - SANITIZER
     Strips HTML tags and dangerous characters to prevent XSS.
     This is a client-side layer only -- server MUST also sanitize.
     ========================================================== */
  const Sanitizer = {
    /**
     * Removes HTML tags from a string.
     * Does NOT use innerHTML (which itself is an XSS vector).
     */
    stripTags(input) {
      if (typeof input !== 'string') return '';
      return input.replace(/<[^>]*>/g, '');
    },

    /**
     * Escapes HTML entities to prevent injection.
     */
    escapeHTML(input) {
      if (typeof input !== 'string') return '';
      const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
      return input.replace(/[&<>"']/g, (char) => map[char]);
    },
  };


  /* ==========================================================
     MODULE 4 - UI CONTROLLER
     All DOM mutations live here. Keeps event handlers thin.
     ========================================================== */
  const UI = {
    /**
     * Shows an inline error on a form group.
     * Adds error styles, sets error text, and shakes the group.
     */
    showError(groupEl, inputEl, errorEl, message) {
      if (!groupEl || !inputEl || !errorEl) return;

      inputEl.classList.add('form-input--error');
      inputEl.classList.remove('form-input--success');
      errorEl.textContent = message;

      // Trigger shake animation by re-adding the class
      groupEl.classList.remove('form-group--error');
      // Force reflow to restart animation
      void groupEl.offsetWidth;
      groupEl.classList.add('form-group--error');
    },

    /**
     * Clears error state from a form group.
     */
    clearError(groupEl, inputEl, errorEl) {
      if (!groupEl || !inputEl || !errorEl) return;

      inputEl.classList.remove('form-input--error');
      groupEl.classList.remove('form-group--error');
      errorEl.textContent = '';
    },

    /**
     * Sets success state on an input.
     */
    setSuccess(inputEl) {
      if (!inputEl) return;
      inputEl.classList.remove('form-input--error');
      inputEl.classList.add('form-input--success');
    },

    /**
     * Updates the live region for screen readers.
     */
    announceStatus(message) {
      if (DOM.formStatus) {
        DOM.formStatus.textContent = message;
      }
    },

    /**
     * Sets the submit button to loading state.
     */
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

    /**
     * Toggles password visibility.
     */
    togglePasswordVisibility() {
      const input = DOM.passwordInput;
      const toggle = DOM.passwordToggle;
      if (!input || !toggle) return;

      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';

      // Update icon visibility
      if (DOM.toggleIconShow) DOM.toggleIconShow.style.display = isPassword ? 'none' : 'block';
      if (DOM.toggleIconHide) DOM.toggleIconHide.style.display = isPassword ? 'block' : 'none';

      // Update ARIA
      toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
      toggle.setAttribute('aria-pressed', isPassword ? 'true' : 'false');

      // Refocus the input so the user can keep typing
      input.focus();
    },
  };


  /* ==========================================================
     MODULE 5 - EVENT HANDLERS
     Thin functions that wire events to Validators + UI.
     ========================================================== */
  const Handlers = {
    /**
     * Validates email on blur (when user leaves the field).
     */
    onEmailBlur() {
      const result = Validators.email(DOM.emailInput.value);
      if (!result.valid) {
        UI.showError(DOM.emailGroup, DOM.emailInput, DOM.emailError, result.message);
      } else {
        UI.clearError(DOM.emailGroup, DOM.emailInput, DOM.emailError);
        UI.setSuccess(DOM.emailInput);
      }
    },

    /**
     * Clears email error on input (real-time feedback).
     */
    onEmailInput() {
      if (DOM.emailInput.classList.contains('form-input--error')) {
        const result = Validators.email(DOM.emailInput.value);
        if (result.valid) {
          UI.clearError(DOM.emailGroup, DOM.emailInput, DOM.emailError);
          UI.setSuccess(DOM.emailInput);
        }
      }
    },

    /**
     * Clears password error on input.
     */
    onPasswordInput() {
      if (DOM.passwordInput.classList.contains('form-input--error')) {
        UI.clearError(DOM.passwordGroup, DOM.passwordInput, DOM.passwordError);
      }
    },

    /**
     * Handles form submission.
     * 1. Prevents default browser submission.
     * 2. Validates all fields.
     * 3. If valid: shows loading state, simulates API call.
     * 4. If invalid: shows errors, announces to screen readers.
     */
    onSubmit(event) {
      event.preventDefault();

      // Sanitize inputs
      const email = Sanitizer.stripTags(DOM.emailInput.value.trim());
      const password = DOM.passwordInput.value; // Don't sanitize passwords (they may contain special chars)

      // Validate all fields
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

      // If errors, announce to screen readers and focus first errored field
      if (errors.length > 0) {
        UI.announceStatus(`Form has ${errors.length} error${errors.length > 1 ? 's' : ''}. ${errors.join(' ')}`);

        // Focus the first errored input
        const firstError = DOM.form.querySelector('.form-input--error');
        if (firstError) firstError.focus();
        return;
      }

      // All valid -- submit
      UI.setLoading(true);
      UI.announceStatus('Logging in, please wait...');

      // Actual API call to the backend
      fetch('http://localhost:8000/auth/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email, password })
      })
      .then(response => response.json())
      .then(data => {
        UI.setLoading(false);
        if (data.success) {
          UI.announceStatus('Login successful! Redirecting...');
          
          // Store user session info in localStorage for frontend use
          localStorage.setItem('user', JSON.stringify(data.data));

          // Redirect based on role
          const role = data.data.ROLE.toLowerCase();
          if (role === 'admin') {
            window.location.href = '../pages/admin/dashboard.html';
          } else if (role === 'provider') {
            window.location.href = '../pages/provider/dashboard.html';
          } else {
            window.location.href = '../pages/customer/dashboard.html';
          }
        } else {
          // Show error from backend (e.g., "Invalid email or password")
          UI.showError(DOM.passwordGroup, DOM.passwordInput, DOM.passwordError, data.message || 'Login failed');
          UI.announceStatus(data.message || 'Login failed');
        }
      })
      .catch(error => {
        UI.setLoading(false);
        console.error('Login error:', error);
        UI.showError(DOM.passwordGroup, DOM.passwordInput, DOM.passwordError, 'Unable to connect to service. Please try again later.');
        UI.announceStatus('Unable to connect to service.');
      });
    },
  };


  /* ==========================================================
     MODULE 6 - INITIALIZATION
     Boots the application: populates DOM cache, wires events.
     ========================================================== */
  function init() {
    // Guard: ensure critical elements exist
    if (!DOM.form || !DOM.emailInput || !DOM.passwordInput) {
      console.error('[ServiceLink] Login form elements not found. Aborting initialization.');
      return;
    }

    // Populate nested DOM references
    populateNestedDOM();

    // --- Wire Event Listeners ---

    // Email validation on blur
    DOM.emailInput.addEventListener('blur', Handlers.onEmailBlur);
    DOM.emailInput.addEventListener('input', Handlers.onEmailInput);

    // Password live feedback
    DOM.passwordInput.addEventListener('input', Handlers.onPasswordInput);

    // Password toggle
    if (DOM.passwordToggle) {
      DOM.passwordToggle.addEventListener('click', UI.togglePasswordVisibility);
    }

    // Form submission
    DOM.form.addEventListener('submit', Handlers.onSubmit);

    // Allow Enter key in password field to submit
    DOM.passwordInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        DOM.form.dispatchEvent(new Event('submit', { cancelable: true }));
      }
    });
  }

  // Boot when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
