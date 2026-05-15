import Api from '../core/api.js';

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('forgotForm');
  const emailInput = document.getElementById('email');
  const emailError = document.getElementById('emailError');
  const emailGroup = document.getElementById('emailGroup');
  const submitBtn = document.getElementById('submitBtn');
  const btnText = submitBtn.querySelector('.btn__text');
  const btnSpinner = submitBtn.querySelector('.btn__spinner');
  const formStatus = document.getElementById('formStatus');

  const showError = (message) => {
    emailError.textContent = message;
    emailGroup.classList.add('has-error');
  };

  const clearError = () => {
    emailError.textContent = '';
    emailGroup.classList.remove('has-error');
  };

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearError();
    formStatus.textContent = '';
    
    const email = emailInput.value.trim();
    if (!email) {
      showError('Email is required');
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showError('Please enter a valid email address');
      return;
    }

    btnText.style.display = 'none';
    btnSpinner.style.display = 'inline-block';
    submitBtn.disabled = true;

    try {
      const res = await Api.post('/auth/forgot-password', { email });
      formStatus.textContent = res.message || 'Password reset link sent! Check your email.';
      form.innerHTML = `<div class="auth-success-message">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="green" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <h3>Email Sent</h3>
        <p>If an account exists for that email, a reset link has been sent.</p>
      </div>`;
    } catch (err) {
      showError(err.message || 'Failed to send reset link');
      btnText.style.display = 'inline-block';
      btnSpinner.style.display = 'none';
      submitBtn.disabled = false;
    }
  });
});
