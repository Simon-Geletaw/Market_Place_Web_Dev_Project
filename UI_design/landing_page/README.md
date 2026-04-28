# ServiceLink Landing Page — Builder's Textbook

> **A step-by-step guide to building a production-quality landing page with HTML, CSS, and JavaScript.**

---

## 0. Preface

### What We're Building
A landing page for **ServiceLink**, a local service marketplace (similar to Thumbtack/Handy). The page includes: Navigation Bar, Hero Section, Service Categories, How It Works, Statistics, Testimonials Carousel, CTA Banner, and Footer.

### Skills You'll Gain
- Semantic HTML5 structure and accessibility
- CSS Custom Properties (design tokens)
- Flexbox and CSS Grid layouts
- Responsive design with media queries
- JavaScript DOM manipulation and event handling
- Intersection Observer API for scroll animations
- Carousel logic from scratch

### Prerequisites
- Basic understanding of HTML tags
- Familiarity with CSS selectors
- JavaScript variables, functions, and loops

### Project Files
```
frontend/landing_page/
├── index.html    ← Structure
├── styles.css    ← Presentation
└── script.js     ← Behavior
```

---

## 1. Foundations

### 1.1 How Web Pages Work

When a browser loads a page:

1. **Parse HTML** -> builds the **DOM** (Document Object Model) tree
2. **Parse CSS** -> builds the **CSSOM** (CSS Object Model)
3. **Combine** -> creates the **Render Tree**
4. **Layout** -> calculates positions and sizes
5. **Paint** -> draws pixels to screen

This is why we separate concerns:
- **HTML** = What content exists (structure)
- **CSS** = How it looks (presentation)
- **JS** = How it behaves (interaction)

### 1.2 Design System Tokens

Our design system uses CSS Custom Properties as tokens:

```css
:root {
  --color-primary: #0066CC;      /* Action Blue */
  --color-primary-hover: #0052A3;
  --color-text-primary: #1A1A1A;
  --color-text-secondary: #666666;
  --color-bg-white: #FFFFFF;
  --color-border: #E0E0E0;
  --font-family: 'Inter', sans-serif;
  --space-sm: 8px;               /* 8px base scale */
  --space-md: 16px;
  --space-lg: 24px;
  --radius-md: 8px;
  --shadow-card: 0 2px 8px rgba(0,0,0,0.08);
}
```

**Why tokens?** Change `--color-primary` once -> every button, link, and accent updates automatically.

---

## 2. Chapter-Based Build

---

### Chapter 1: Navigation Bar

#### A. Feature Overview
The navbar is the user's primary wayfinding tool. It stays fixed at the top (sticky), provides links to page sections, and shows auth buttons.

**UX Purpose:** Navigation, brand identity, conversion (Sign Up button).

#### B. Required Knowledge

**HTML Tags:**
- `<header>` — landmark for the page header
- `<nav>` — landmark for navigation
- `<ul>/<li>` — unordered list for nav items
- `<a>` — anchor links
- `<button>` — hamburger toggle (interactive, not a link)

**CSS Concepts:**
- `position: fixed` — element stays in viewport during scroll
- `backdrop-filter: blur()` — glassmorphism effect
- Flexbox — horizontal alignment of logo, links, buttons
- `z-index` — stacking order above other content

#### C. Concept Deep Dive

**Fixed positioning:** `position: fixed` removes the element from document flow and positions it relative to the viewport. We must add `padding-top` to the next section so content isn't hidden behind it.

**BEM Naming:** We use Block-Element-Modifier: `.navbar__link--active`. Block = `navbar`, Element = `link`, Modifier = `active`.

#### D. Step-by-Step Implementation

**Step 1: Minimal HTML skeleton**
```html
<header class="navbar" id="navbar">
  <div class="container navbar__inner">
    <a href="#" class="navbar__logo">ServiceLink</a>
    <nav><ul class="navbar__list">
      <li><a href="#hero" class="navbar__link">Home</a></li>
    </ul></nav>
  </div>
</header>
```
*What changed:* Created the header landmark with a logo and one link.
*Why:* Establishes the semantic skeleton before styling.

**Step 2: Add all links and auth buttons**
```html
<li><a href="#categories" class="navbar__link">Services</a></li>
<li><a href="#how-it-works" class="navbar__link">How It Works</a></li>
<li><a href="#testimonials" class="navbar__link">Testimonials</a></li>
<!-- Auth buttons -->
<div class="navbar__actions">
  <a href="#" class="btn btn--ghost">Log In</a>
  <a href="#" class="btn btn--primary">Sign Up Free</a>
</div>
```

**Step 3: Add hamburger toggle for mobile**
```html
<button class="navbar__toggle" id="navToggle" aria-expanded="false">
  <span class="navbar__toggle-bar"></span>
  <span class="navbar__toggle-bar"></span>
  <span class="navbar__toggle-bar"></span>
</button>
```
*Why:* The three `<span>` bars become the hamburger icon. CSS transforms them into an "X" when active.

**Step 4: CSS — Fixed position and glass effect**
```css
.navbar {
  position: fixed; top: 0; left: 0; width: 100%; z-index: 1000;
  background: rgba(255,255,255,0.85);
  backdrop-filter: blur(12px);
  height: 72px;
}
```

**Step 5: CSS — Flexbox layout**
```css
.navbar__inner {
  display: flex; align-items: center; justify-content: space-between;
  height: 100%;
}
.navbar__list { display: flex; gap: 32px; }
```

**Step 6: CSS — Animated underline on hover**
```css
.navbar__link::after {
  content: ''; position: absolute; bottom: -2px; left: 0;
  width: 0; height: 2px; background: var(--color-primary);
  transition: width 0.3s;
}
.navbar__link:hover::after { width: 100%; }
```

**Step 7: JS — Scroll-aware styling**
```javascript
window.addEventListener('scroll', () => {
  navbar.classList.toggle('navbar--scrolled', window.scrollY > 20);
}, { passive: true });
```
*Why `{ passive: true }`?* Tells the browser we won't call `preventDefault()`, enabling scroll optimizations.

#### E. Full Code Snapshot
See `index.html` lines 27-58, `styles.css` lines 97-140, `script.js` lines 22-30.

#### F. Why This Works
- `position: fixed` + `z-index: 1000` keeps the navbar above all content
- `backdrop-filter: blur(12px)` creates a frosted glass effect
- Flexbox with `justify-content: space-between` pushes logo left, actions right
- The `::after` pseudo-element creates the underline without extra HTML

#### G. Common Mistakes
1. **Forgetting scroll padding** — Anchor links jump behind the fixed navbar. Fix: `html { scroll-padding-top: 72px; }`
2. **Using `<div>` instead of `<nav>`** — Screen readers won't identify it as navigation
3. **Not setting `aria-expanded`** — Mobile menu state is invisible to assistive tech

#### H. Test Cases
| Test | Expected | Why |
|------|----------|-----|
| Scroll down 50px | Navbar gains border shadow | Validates scroll listener |
| Click hamburger (mobile) | Menu slides open, bars become X | Validates toggle logic |
| Click nav link (mobile) | Menu closes, scrolls to section | Validates close-on-click |
| Tab through links | Each link shows focus outline | Validates keyboard accessibility |

---

### Chapter 2: Hero Section

#### A. Feature Overview
The hero is the first thing users see. It must communicate the value proposition within 3 seconds and drive users to the primary CTA.

**UX Purpose:** First impression, conversion (Post a Request CTA), trust building (ratings badge).

#### B. Required Knowledge
- CSS Grid — two-column layout (text | visual)
- CSS `linear-gradient()` — background color transitions
- `background-clip: text` — gradient text effect
- CSS `@keyframes` — floating card animations

#### C. Concept Deep Dive

**CSS Grid vs Flexbox:** Grid is two-dimensional (rows AND columns). We use `grid-template-columns: 1fr 1fr` to create two equal columns. On mobile, we switch to `grid-template-columns: 1fr` (single column).

**The `1fr` unit:** `fr` = fractional unit. `1fr 1fr` means "divide available space equally between two columns."

#### D. Step-by-Step Implementation

**Step 1: HTML — Section with heading**
```html
<section class="hero" id="hero" aria-labelledby="hero-heading">
  <div class="container hero__inner">
    <div class="hero__content">
      <h1 id="hero-heading">Find Local Services, Get It Done Fast.</h1>
      <p class="hero__subtitle">Post your service request and receive offers...</p>
      <a href="#" class="btn btn--primary btn--lg">Post a Request</a>
    </div>
  </div>
</section>
```

**Step 2: Add trust indicators and floating cards**
```html
<div class="hero__trust">
  <div class="hero__trust-avatars">
    <span class="hero__avatar" style="background:#0066CC;">A</span>
    <!-- more avatars -->
  </div>
   <p>4.8 average rating from 500+ reviews</p>
</div>
```

**Step 3: CSS Grid layout**
```css
.hero__inner {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 64px;
  align-items: center;
}
```

**Step 4: Floating card animation**
```css
@keyframes floatCard {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-12px); }
}
.hero__card { animation: floatCard 6s ease-in-out infinite; }
.hero__card--2 { animation-delay: 2s; }
```

#### E-F. See full source in `index.html` lines 66-121, `styles.css` lines 142-210.

#### G. Common Mistakes
1. **Gradient text without fallback** — If `background-clip: text` fails, text disappears. Always set a solid `color` as fallback.
2. **Hero too tall on mobile** — Hide the floating cards visual on screens < 992px.

#### H. Test Cases
| Test | Expected | Why |
|------|----------|-----|
| Resize to mobile | Single column, visual hidden | Validates responsive grid |
| Check gradient text | "Get It Done Fast" shows blue gradient | Validates text-clip |
| Cards animate | Float up/down continuously | Validates keyframes |

---

### Chapter 3: Service Categories

#### A. Feature Overview
Six cards showing service types. Each card is clickable and leads to the marketplace filtered by category.

**UX Purpose:** Discovery — helps users find relevant services quickly.

#### B. Required Knowledge
- CSS Grid with `repeat(3, 1fr)` — 3-column grid
- `:hover` pseudo-class — interactive feedback
- `transform: translateY()` — lift effect
- `<article>` tag — self-contained card content

#### D. Key Implementation Steps

**Grid layout:**
```css
.categories__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr); /* 3 equal columns */
  gap: 24px;
}
```

**Hover lift effect:**
```css
.category-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  border-color: var(--color-primary);
}
```

**Icon background swap on hover:**
```css
.category-card__icon-wrap { background: var(--color-primary-light); }
.category-card:hover .category-card__icon-wrap { background: var(--color-primary); }
```

#### G. Common Mistakes
1. **Not setting `gap`** — Cards touch each other. Always define gap on the grid container.
2. **Using `margin` instead of `gap`** — Causes uneven spacing on last row.

#### H. Test Cases
| Test | Expected | Why |
|------|----------|-----|
| Hover over card | Lifts up, shadow increases, border turns blue | Validates hover state |
| Resize to tablet | 2 columns | Validates `repeat(2, 1fr)` breakpoint |
| Resize to mobile | 1 column, full width | Validates single column |
| Add 7th card | Layout still works (wraps to next row) | Validates grid auto-flow |

---

### Chapter 4: How It Works

#### A. Feature Overview
A 4-step horizontal flow: Post -> Receive Offers -> Choose Provider -> Done.

**UX Purpose:** Reduces anxiety by showing the process is simple and predictable.

#### D. Key Implementation
- Flexbox for horizontal alignment of steps
- SVG arrow connectors between steps (hidden on mobile)
- Numbered circles using `border-radius: 50%`

```css
.hiw__steps { display: flex; align-items: flex-start; justify-content: center; }
.hiw__step-number {
  width: 40px; height: 40px; border-radius: 50%;
  background: var(--color-primary); color: #fff;
  display: flex; align-items: center; justify-content: center;
}
```

**Responsive:** On mobile, `flex-direction: column` and connectors hidden.

---

### Chapter 5: Statistics Bar

#### A. Feature Overview
A full-width gradient bar showing key metrics (requests posted, providers, jobs completed, rating).

**UX Purpose:** Social proof — large numbers build confidence.

#### D. Key Implementation

**Animated counter (JS):**
```javascript
function animateCounters() {
  statNumbers.forEach(el => {
    const target = parseFloat(el.dataset.target);
    const duration = 2000;
    const startTime = performance.now();

    function update(now) {
      const progress = Math.min((now - startTime) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
      el.textContent = Math.floor(eased * target).toLocaleString();
      if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
  });
}
```

**Intersection Observer trigger:**
```javascript
const observer = new IntersectionObserver(
  entries => { if (entries[0].isIntersecting) animateCounters(); },
  { threshold: 0.4 }
);
observer.observe(statsSection);
```

**Why IntersectionObserver?** It's more efficient than checking scroll position every frame. The browser handles the detection natively.

#### H. Test Cases
| Test | Expected | Why |
|------|----------|-----|
| Scroll to stats section | Numbers count up from 0 to target | Validates observer + animation |
| Scroll past and return | Numbers stay at final value (no re-animate) | Validates `statsAnimated` flag |

---

### Chapter 6: Testimonials Carousel

#### A. Feature Overview
A horizontally scrolling carousel showing customer/provider reviews with auto-rotation.

**UX Purpose:** Trust building through social proof.

#### C. Concept Deep Dive

**Carousel mechanics:** The "track" is a flex container wider than its parent. We use `transform: translateX()` to slide it left/right. The parent has `overflow: hidden` to clip overflow.

```
┌── Visible viewport ──┐
│ Card 1 │ Card 2 │ Card 3 │ Card 4 (hidden) │
└─────────────────────┘
        ← translateX(-cardWidth) slides left
```

#### D. Key Implementation

**Track setup:**
```css
.testimonials__track {
  display: flex; gap: 24px;
  transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.testimonial-card { min-width: calc(33.333% - 16px); flex-shrink: 0; }
```

**Slide function (JS):**
```javascript
function goToSlide(index) {
  currentIndex = ((index % total) + total) % total; // wraps around
  const cardWidth = cards[0].offsetWidth + gap;
  track.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
  updateDots();
}
```

**Auto-rotation:**
```javascript
setInterval(() => goToSlide(currentIndex + 1), 5000);
// Pause on hover
track.addEventListener('mouseenter', () => clearInterval(timer));
track.addEventListener('mouseleave', () => startAutoPlay());
```

#### G. Common Mistakes
1. **Not using `flex-shrink: 0`** — Cards compress instead of overflowing
2. **Forgetting to rebuild dots on resize** — Card count per view changes

#### H. Test Cases
| Test | Expected | Why |
|------|----------|-----|
| Click next arrow | Slides to next card | Validates goToSlide |
| Wait 5 seconds | Auto-advances | Validates setInterval |
| Hover over carousel | Auto-play pauses | Validates mouseenter listener |
| Click dot 3 | Jumps to slide 3 | Validates dot navigation |

---

### Chapter 7: CTA Banner

Simple gradient section with two buttons. Uses `text-align: center` and flexbox for button layout. The dark gradient contrasts with the rest of the page to draw attention.

---

### Chapter 8: Footer

#### A. Feature Overview
4-column grid with brand info, platform links, support links, and legal links.

#### D. Key Implementation
```css
.footer__inner {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 48px;
}
```
The `2fr` gives the brand column double the width. On mobile: `grid-template-columns: 1fr`.

---

## 3. Integration — Full Page Assembly

### File Interaction
```
index.html
  ├── <link href="styles.css">    ← loaded in <head> (render-blocking, intentional)
  └── <script src="script.js">    ← loaded at end of <body> (non-blocking)
```

**Why CSS in `<head>`?** The browser needs styles before painting. Loading CSS later causes a flash of unstyled content (FOUC).

**Why JS at end of `<body>`?** The DOM must be fully parsed before JavaScript queries elements. Alternatively, use `DOMContentLoaded` (which we do).

### Section Flow
```
Navbar (fixed, z-index: 1000)
  ↓
Hero (padding-top includes navbar height)
  ↓ wave SVG divider
Categories (white background)
  ↓
How It Works (light gray background)
  ↓
Stats (blue gradient — visual break)
  ↓
Testimonials (white background)
  ↓
CTA (dark gradient — urgency)
  ↓
Footer (near-black background)
```

The alternating backgrounds create **visual rhythm** — the eye naturally flows downward through contrasting sections.

---

## 4. Performance Basics

1. **Font loading:** `<link rel="preconnect">` for Google Fonts reduces DNS lookup time
2. **CSS organization:** Properties grouped by section, not scattered — easier to maintain, browser parses in one pass
3. **Passive scroll listeners:** `{ passive: true }` prevents scroll jank
4. **IntersectionObserver > scroll events:** Native browser API, no per-frame calculations
5. **`requestAnimationFrame`:** Syncs counter animation with display refresh rate (60fps)
6. **No unused CSS/JS:** Every rule and function serves a purpose

---

## 5. Accessibility Basics

### Semantic HTML
| Element | Purpose |
|---------|---------|
| `<header>` | Page banner landmark |
| `<nav>` | Navigation landmark |
| `<main>` | Primary content |
| `<section>` | Thematic grouping |
| `<article>` | Self-contained content (cards) |
| `<footer>` | Page footer landmark |

### ARIA Attributes Used
- `aria-label` — labels for icon-only buttons (hamburger, social links)
- `aria-expanded` — communicates menu open/closed state
- `aria-labelledby` — associates sections with their headings
- `aria-hidden="true"` — hides decorative SVGs from screen readers
- `role="tablist"` — carousel dots as tab controls

### Keyboard Navigation
- All interactive elements are focusable via Tab
- `:focus-visible` provides a 2px blue outline
- Hamburger toggle works with Enter/Space keys

### Color Contrast
- Text primary `#1A1A1A` on white = **15.3:1** ratio (exceeds 4.5:1 AA requirement)
- White text on `#0066CC` = **4.9:1** ratio (meets AA)

---

## 6. Final Review

### Complete Page Walkthrough

1. **Navbar** — Fixed glassmorphism bar with logo, 4 section links, and auth buttons. Hamburger on mobile.
2. **Hero** — Gradient background, bold headline with gradient text accent, trust badge with avatar stack, floating animated cards showing real platform states.
3. **Categories** -- 6-card grid with category icons, hover lift effects, provider counts. 3 to 2 to 1 column responsive.
4. **How It Works** — 4-step horizontal flow with numbered circles, SVG icons, and dashed arrow connectors.
5. **Stats** — Blue gradient bar with animated counters triggered on scroll.
6. **Testimonials** — 3-card carousel with auto-rotation, prev/next buttons, dot indicators, pause on hover.
7. **CTA** — Dark gradient banner with two action buttons.
8. **Footer** — 4-column grid with brand, links, and social icons. Dark background for visual closure.

### UX Reasoning
- **Visual hierarchy:** Hero title (2.75rem, 800 weight) -> Section titles (2rem) -> Card titles (1.25rem) -> Body text (0.9rem)
- **Color strategy:** Blue accent for all CTAs creates consistent "action" association
- **Spacing consistency:** All spacing uses the 8px scale, creating mathematical harmony
- **Trust signals:** Rating badge, avatars, stats bar, and testimonials all reinforce credibility

---

## 7. Advanced Extensions

### Animations
- Add `scroll-timeline` for parallax hero background
- Use CSS `@property` for animated gradient text
- Add page load animation sequence with `animation-delay`

### API Integration
- Replace static category counts with `fetch('/api/providers/count?category=...')`
- Load testimonials dynamically from `/api/reviews?featured=true`
- Wire "Post a Request" button to open a modal with form -> `POST /api/requests`

### Forms
- Add search bar to hero section with live autocomplete
- Create newsletter signup in footer with email validation
- Wire Login/Sign Up buttons to auth modal or separate pages

---

## Appendix: File Reference

| File | Lines | Purpose |
|------|-------|---------|
| `index.html` | ~300 | Semantic structure with 8 sections |
| `styles.css` | ~400 | Design tokens, component styles, responsive |
| `script.js` | ~180 | 8 interactive features |

**Design System Source:** Stitch MCP — "Secure Local Service Marketplace Design System"
**Primary Color:** `#0066CC` (Action Blue) | **Font:** Inter | **Spacing:** 8px base scale
