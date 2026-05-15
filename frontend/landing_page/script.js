/**
 * ServiceLink Landing Page — Interactive Behaviors
 * =================================================
 * Features:
 *  1. Sticky navbar with scroll-aware styling
 *  2. Mobile hamburger menu toggle
 *  3. Smooth scroll to anchor links
 *  4. Active nav-link highlighting on scroll
 *  5. Animated stat counters (Intersection Observer)
 *  6. Animated stat counters (Intersection Observer)
 *  7. Scroll-reveal animations
 *  8. Back-to-top button
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {
  /* --- 1. STICKY NAVBAR --- */
  const navbar = document.getElementById('navbar');
  const navToggle = document.getElementById('navToggle');
  const navMenu = document.getElementById('navbarMenu');
  const backToTop = document.getElementById('backToTop');

  if (!navbar) return;

  function handleNavbarScroll() {
    if (window.scrollY > 20) {
      navbar.classList.add('navbar--scrolled');
    } else {
      navbar.classList.remove('navbar--scrolled');
    }
  }
  window.addEventListener('scroll', handleNavbarScroll, { passive: true });

  /* --- 2. MOBILE MENU TOGGLE --- */
  if (navToggle && navMenu) {
    navToggle.addEventListener('click', () => {
      const isOpen = navToggle.classList.toggle('navbar__toggle--active');
      navMenu.classList.toggle('navbar__nav--open');
      navToggle.setAttribute('aria-expanded', String(isOpen));

      // Prevent body scroll when menu is open
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    // Close menu when a nav link is clicked
    navMenu.querySelectorAll('.navbar__link').forEach(link => {
      link.addEventListener('click', () => {
        navToggle.classList.remove('navbar__toggle--active');
        navMenu.classList.remove('navbar__nav--open');
        navToggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      });
    });
  }

  /* --- 3. SMOOTH SCROLL --- */
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', (e) => {
      const targetId = anchor.getAttribute('href');
      if (targetId === '#') return;
      const target = document.querySelector(targetId);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  /* --- 4. ACTIVE NAV LINK ON SCROLL --- */
  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.navbar__link');

  function highlightNavLink() {
    const scrollPos = window.scrollY + 120;
    sections.forEach(section => {
      const top = section.offsetTop;
      const height = section.offsetHeight;
      const id = section.getAttribute('id');
      if (scrollPos >= top && scrollPos < top + height) {
        navLinks.forEach(link => {
          link.classList.remove('navbar__link--active');
          if (link.getAttribute('href') === `#${id}`) {
            link.classList.add('navbar__link--active');
          }
        });
      }
    });
  }
  window.addEventListener('scroll', highlightNavLink, { passive: true });


  /* --- 6. ANIMATED STAT COUNTERS --- */
  const statNumbers = document.querySelectorAll('.stats__number[data-target]');
  let statsAnimated = false;

  function animateCounters() {
    if (statsAnimated) return;
    statsAnimated = true;

    statNumbers.forEach(el => {
      const target = parseFloat(el.dataset.target);
      const isDecimal = target % 1 !== 0;
      const duration = 2000;
      const startTime = performance.now();

      function updateCounter(now) {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        // Ease-out cubic
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = eased * target;
        el.textContent = isDecimal ? current.toFixed(1) : Math.floor(current).toLocaleString();
        if (progress < 1) requestAnimationFrame(updateCounter);
      }
      requestAnimationFrame(updateCounter);
    });
  }

  // Trigger when stats section is visible
  const statsSection = document.querySelector('.stats');
  if (statsSection) {
    const statsObserver = new IntersectionObserver(
      entries => { if (entries[0].isIntersecting) animateCounters(); },
      { threshold: 0.4 }
    );
    statsObserver.observe(statsSection);
  }

  /* --- 7. SCROLL-REVEAL ANIMATIONS --- */
  const revealElements = document.querySelectorAll(
    '.category-card, .hiw__step, .section-header'
  );
  revealElements.forEach(el => el.classList.add('reveal'));

  const revealObserver = new IntersectionObserver(
    entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('reveal--visible');
          revealObserver.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15, rootMargin: '0px 0px -40px 0px' }
  );
  revealElements.forEach(el => revealObserver.observe(el));

  /* --- 8. BACK TO TOP --- */
  if (backToTop) {
    window.addEventListener('scroll', () => {
      backToTop.classList.toggle('back-to-top--visible', window.scrollY > 500);
    }, { passive: true });

    backToTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }
});
