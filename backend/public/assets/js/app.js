document.addEventListener('DOMContentLoaded', () => {
  const statusBadges = document.querySelectorAll('[data-status]');
  statusBadges.forEach((badge) => {
    badge.setAttribute('aria-label', `Status: ${badge.dataset.status}`);
  });
});
