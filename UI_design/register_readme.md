# ServiceLink — Registration Page: A Full-Stack Textbook

> **Audience:** Junior → Senior Developer  
> **Scope:** This document deconstructs the Registration page through the same progressive lens — HTML structure, CSS styling, and JavaScript logic — turning each layer into a deep-dive educational chapter.

---

## Part I — The HTML Textbook: Architecture & The DOM

### Chapter Overview

If the Login page is the *gate*, the Registration page is the *onboarding ramp*. Its purpose extends beyond mere data collection:

1. **Capture** the minimum viable user profile (name, email, credentials, role, contact, location).
2. **Categorize** users into roles (Customer vs. Provider) that determine their entire application experience.
3. **Validate** data quality at the client level to reduce server load and improve UX.
4. **Establish trust** through terms acceptance and clear communication of data usage.

The Registration form is significantly more complex than Login. It introduces new HTML patterns: `<fieldset>` and `<legend>` for radio groups, `<select>` for dropdowns, `<input type="checkbox">` for boolean consent, and a password strength indicator. Each of these patterns creates a different kind of node in the DOM tree, and understanding their differences is key to writing production-quality HTML.

---

### The DOM Tree Analysis

```
Document
└── html [lang="en"]
    ├── head
    │   ├── meta [charset, viewport, description, keywords]
    │   ├── title
    │   └── link (×4: preconnect ×2, fonts, stylesheet)
    └── body
        ├── aside.auth-brand [aria-hidden="true"]
        │   └── (same branding structure as Login page)
        ├── main.auth-main [id="main-content"]
        │   └── div.auth-form-container
        │       ├── a.auth-mobile-logo
        │       ├── header.auth-form__header
        │       │   ├── h1 "Create Your Account"
        │       │   └── p  "Join our local service marketplace today."
        │       ├── div#registerFormStatus [role="status"]
        │       ├── form#registerForm [novalidate]
        │       │   │
        │       │   ├── div.form-group#nameGroup
        │       │   │   ├── label [for="registerName"]
        │       │   │   ├── input#registerName [type="text", minlength="2"]
        │       │   │   └── span.form-error#nameError
        │       │   │
        │       │   ├── div.form-group#emailGroup
        │       │   │   ├── label [for="registerEmail"]
        │       │   │   ├── input#registerEmail [type="email"]
        │       │   │   ├── span.form-hint#emailHint
        │       │   │   └── span.form-error#emailError
        │       │   │
        │       │   ├── div.form-group#passwordGroup
        │       │   │   ├── label [for="registerPassword"]
        │       │   │   ├── input#registerPassword [type="password"]
        │       │   │   ├── button#passwordToggle [type="button"]
        │       │   │   ├── span.form-error#passwordError
        │       │   │   ├── div.password-strength
        │       │   │   │   ├── div.password-strength__bar > div.__fill
        │       │   │   │   └── span.password-strength__label
        │       │   │   └── ul.password-requirements
        │       │   │       ├── li#reqLength
        │       │   │       ├── li#reqUppercase
        │       │   │       └── li#reqNumber
        │       │   │
        │       │   ├── div.form-group#confirmPasswordGroup
        │       │   │   ├── label [for="registerConfirmPassword"]
        │       │   │   ├── input#registerConfirmPassword [type="password"]
        │       │   │   └── span.form-error#confirmPasswordError
        │       │   │
        │       │   ├── fieldset.form-group#roleGroup ★ NEW PATTERN
        │       │   │   ├── legend "I am a..."
        │       │   │   └── div.radio-group [role="radiogroup"]
        │       │   │       ├── label.radio-card > input[type="radio"] "Customer"
        │       │   │       └── label.radio-card > input[type="radio"] "Provider"
        │       │   │
        │       │   ├── div.form-group#phoneGroup
        │       │   │   ├── label [for="registerPhone"]
        │       │   │   ├── input#registerPhone [type="tel"]
        │       │   │   └── span.form-error#phoneError
        │       │   │
        │       │   ├── div.form-group#locationGroup
        │       │   │   ├── label [for="registerLocation"]
        │       │   │   ├── select#registerLocation
        │       │   │   │   ├── option [disabled, selected] "Select your sub-city"
        │       │   │   │   ├── option "Addis Ketema"
        │       │   │   │   ├── ... (11 sub-cities)
        │       │   │   │   └── option "Lemi Kura"
        │       │   │   └── span.form-error#locationError
        │       │   │
        │       │   ├── div.form-group#termsGroup
        │       │   │   ├── label.checkbox-label
        │       │   │   │   ├── input#registerTerms [type="checkbox"]
        │       │   │   │   ├── span.checkbox-custom (visual checkmark)
        │       │   │   │   └── span.checkbox-text "I agree to..."
        │       │   │   └── span.form-error#termsError
        │       │   │
        │       │   └── button#registerSubmitBtn [type="submit"]
        │       └── p.auth-form__footer
        └── script [src="script.js"]
```

**Architecture Comparison: Login vs. Register**

| Aspect | Login | Register |
|--------|-------|----------|
| Form fields | 2 (email, password) | 7 (name, email, password, confirm, role, phone, location) + 1 checkbox |
| New HTML patterns | — | `<fieldset>`, `<legend>`, `<select>`, `<input type="radio">`, `<input type="checkbox">`, `<input type="tel">` |
| Validation complexity | 2 rules | 10+ rules (including cross-field: password match) |
| DOM node count | ~60 | ~150+ |

---

### Step-by-Step Construction Guide

The registration page shares the same document shell as the login page. This section focuses on the **new patterns** introduced by the registration form.

#### Step 1: The `<fieldset>` and `<legend>` Pattern

```html
<fieldset class="form-group form-group--fieldset" id="roleGroup">
  <legend class="form-label">I am a...</legend>
  <div class="radio-group" role="radiogroup" aria-required="true">
    <label class="radio-card" for="roleCustomer">
      <input type="radio" id="roleCustomer" name="role" value="customer" checked>
      <span class="radio-card__body">
        <span class="radio-card__label">Customer</span>
        <span class="radio-card__desc">I need services</span>
      </span>
    </label>
    <label class="radio-card" for="roleProvider">
      <input type="radio" id="roleProvider" name="role" value="provider">
      <span class="radio-card__body">
        <span class="radio-card__label">Provider</span>
        <span class="radio-card__desc">I offer services</span>
      </span>
    </label>
  </div>
</fieldset>
```

**Why `<fieldset>` instead of `<div>`?** In forms, `<fieldset>` creates a semantic group. Screen readers announce the `<legend>` text before each input within the group: "I am a..., Customer, radio button, 1 of 2, checked." Using `<div>` instead would cause screen readers to announce each radio button in isolation, without context.

**Why `name="role"` on both radios?** Radio buttons with the same `name` attribute form a mutually exclusive group. The browser enforces that only one can be selected at a time. This is a *native browser behavior* — no JavaScript required. The `name` also becomes the key in the `FormData` object: `{ role: "customer" }`.

**Why `checked` on the first radio?** We default to "Customer" because the Stitch UX spec indicates most users are customers. This also ensures the `FormData` always has a `role` value, even if the user doesn't interact with the radio group.

#### Step 2: The `<select>` Dropdown Pattern

```html
<select class="form-input form-select" id="registerLocation" name="location" required>
  <option value="" disabled selected>Select your sub-city</option>
  <option value="bole">Bole</option>
  <option value="kirkos">Kirkos</option>
  <!-- ... -->
</select>
```

**Why `value=""` on the placeholder option?** When the form is submitted, the browser checks the `required` attribute. An option with `value=""` is treated as "no selection," so the browser (or our JS) can detect that the user hasn't chosen a location.

**Why `disabled selected` on the placeholder?** `selected` makes it the default display text. `disabled` prevents the user from re-selecting it after choosing a real option. This is a UX pattern that prevents accidental "un-selection."

**The `name` attribute maps to the database.** When this form reaches the backend, the PHP controller receives `$_POST['location']` with a value like `"bole"`. This value directly maps to the `location` ENUM column in the `users` table. The HTML `<option value="">` is designing the API contract.

#### Step 3: The Password Strength Indicator

```html
<div class="password-strength" id="passwordStrength" aria-hidden="true">
  <div class="password-strength__bar">
    <div class="password-strength__fill" id="passwordStrengthFill"></div>
  </div>
  <span class="password-strength__label" id="passwordStrengthLabel"></span>
</div>

<ul class="password-requirements" id="passwordRequirements" aria-label="Password requirements">
  <li class="password-req" id="reqLength" data-met="false">At least 8 characters</li>
  <li class="password-req" id="reqUppercase" data-met="false">One uppercase letter</li>
  <li class="password-req" id="reqNumber" data-met="false">One number</li>
</ul>
```

**Why `aria-hidden="true"` on the strength bar?** The visual bar (Weak → Strong with colors) is redundant with the text label and the requirements checklist. Screen readers should read the checklist items, not try to interpret a progress bar. The checklist items use `data-met="false"` — a custom data attribute that our CSS and JS toggle to `"true"` to provide both visual and semantic feedback.

**Why `data-met` instead of a CSS class?** Custom data attributes (`data-*`) are designed for storing state in HTML. Using `data-met="true"` is more self-documenting than a class like `is-met`. It also allows CSS attribute selectors (`[data-met="true"]`) and easy JS access (`element.dataset.met`).

#### Step 4: The Checkbox with Custom Styling

```html
<label class="checkbox-label" for="registerTerms">
  <input type="checkbox" id="registerTerms" name="terms" required class="checkbox-input">
  <span class="checkbox-custom" aria-hidden="true">
    <svg><!-- checkmark --></svg>
  </span>
  <span class="checkbox-text">
    I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
  </span>
</label>
```

**Why a custom checkbox?** The native `<input type="checkbox">` is rendered differently by every browser and OS, and its styling is extremely limited. Our approach:
1. Hide the native checkbox visually (but keep it in the DOM for accessibility).
2. Show a `<span class="checkbox-custom">` as the visual replacement.
3. Use the `:checked` pseudo-class on the hidden input to style the custom span.

This gives us pixel-perfect control while maintaining full keyboard accessibility and screen reader support.

---

### Textbook Glossary — Tags & Attributes

| Tag / Attribute | Definition | Implementation | Under the Hood |
|---|---|---|---|
| `<fieldset>` | Groups related form controls. | Wraps the "I am a..." role selection. | Creates a semantic group in the accessibility tree. Screen readers announce the `<legend>` as context for every control inside. |
| `<legend>` | Caption for a `<fieldset>`. | "I am a..." — labels the role radio group. | Always the first child of `<fieldset>`. Rendered as part of the fieldset's border by default (overridable with CSS). |
| `<input type="radio">` | Exclusive-choice input. | Customer/Provider role selection. | Radio inputs with the same `name` are mutually exclusive. The browser manages the group state natively. `FormData` sends only the checked value. |
| `checked` | Pre-selects a radio/checkbox. | Default-selects "Customer." | Sets `input.checked = true` in the DOM. The `change` event does *not* fire for the initial checked state — only for user interactions. |
| `<select>` | Dropdown selection list. | Location (sub-city) picker. | Creates a `<select>` DOM element with `selectedIndex` property. On mobile, this triggers the native OS picker (wheel on iOS, bottom sheet on Android). |
| `<option>` | Individual choice within `<select>`. | Each sub-city (Bole, Kirkos, etc.). | The `value` attribute is what gets submitted; the text content is what the user sees. This separation is fundamental to form design. |
| `disabled` (on `<option>`) | Prevents selection. | The placeholder "Select your sub-city." | The option is visible but unselectable. Combined with `selected`, it acts as placeholder text. |
| `<input type="tel">` | Telephone number input. | Phone number field. | On mobile, this triggers the telephone keypad (numbers + symbols like `+`). Does *not* validate format — that must be done in JS. |
| `<input type="checkbox">` | Boolean (yes/no) input. | Terms of Service agreement. | `input.checked` returns `true`/`false`. In `FormData`, a checked checkbox sends its `value` attribute (default `"on"`); unchecked sends nothing. |
| `minlength="2"` | Minimum character count. | On the Full Name input. | Populates `validity.tooShort = true` when the input's value length is less than 2. The browser can use this for native validation (which we've disabled in favor of JS). |
| `<input type="password">` | Masked text input. | Password and Confirm Password. | Characters are masked. `autocomplete="new-password"` (vs `current-password` in login) tells the browser this is a *new* password, triggering the password generator on Chrome/Safari. |
| `autocomplete="new-password"` | Hints that this is a new password. | On the registration password field. | Triggers browser password generation suggestions. Chrome shows "Suggest strong password." This is different from `current-password` used on Login. |
| `role="radiogroup"` | ARIA role for a group of radios. | On the `<div>` wrapping the radio cards. | Provides the same semantics as `<fieldset>` for custom-styled radio groups. Used as a backup when `<fieldset>` styles are overridden. |
| `data-met` | Custom data attribute. | On password requirement `<li>` items. | Stores boolean state ("true"/"false"). Accessed via JS: `element.dataset.met`. Targetable in CSS: `[data-met="true"]`. |

---

## Part II — The CSS Textbook: Styling & The CSSOM

### Chapter Overview

The Registration page shares the same layout architecture as the Login page (mobile-first, split-panel on desktop), but introduces **four new component patterns** that require dedicated CSS:

1. **Radio Card Group** — Transforming native radio buttons into tappable cards.
2. **Select Dropdown** — Replacing the browser's default dropdown with a custom-styled element.
3. **Custom Checkbox** — Pixel-perfect checkbox with animated checkmark.
4. **Password Strength Indicator** — A dynamic progress bar with color-coded strength levels.

These patterns demonstrate a core CSS principle: **styling form controls requires hiding the native element and building a visual replacement**, while keeping the native element in the DOM for accessibility and form data.

---

### The Render Tree — Registration-Specific Considerations

The Registration form has ~150+ DOM nodes vs Login's ~60. This impacts rendering performance:

- **More paint operations** — Each form group, input, label, and error span triggers a paint.
- **More layout recalculations** — When password requirements update (showing/hiding checkmarks), the browser recalculates the layout of everything below.
- **More composite layers** — Animated elements (strength bar fill, feature cards) create compositor layers.

**Our mitigation strategy:**
- Use `transform` and `opacity` for animations (GPU-accelerated, no reflow).
- Use `gap` in flexbox/grid instead of margins (fewer layout properties to resolve).
- Use `will-change` sparingly and only on known-animated elements.

---

### Step-by-Step Construction Guide: New Components

#### Step 1: Radio Card Group

```css
.radio-group {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-sm);
}

.radio-card__input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.radio-card__body {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: var(--space-md);
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-lg);
  transition: all var(--transition);
}

.radio-card__input:checked + .radio-card__body {
  border-color: var(--color-primary);
  background: var(--color-primary-subtle);
  box-shadow: 0 0 0 1px var(--color-primary);
}
```

**Why hide the native radio with `opacity: 0` instead of `display: none`?** An element with `display: none` is removed from the accessibility tree — screen readers can't find it. `opacity: 0` + `position: absolute` keeps the radio in the tab order and accessible to screen readers while being visually hidden. The `+` adjacent sibling combinator then styles the visual `.radio-card__body` based on the hidden input's `:checked` state.

**Mobile adaptation:** On screens smaller than 380px, the grid switches to `grid-template-columns: 1fr` and the card body becomes `flex-direction: row` for a compact horizontal layout.

#### Step 2: Custom Select Dropdown

```css
.form-select {
  cursor: pointer;
  padding-right: 40px;
  appearance: none;
  -webkit-appearance: none;
}

.select-chevron {
  position: absolute;
  right: 14px;
  pointer-events: none;
  transition: transform var(--transition-fast);
}

.form-select:focus ~ .select-chevron {
  transform: rotate(180deg);
  color: var(--color-primary);
}
```

**Why `appearance: none`?** Native `<select>` elements have OS-specific styling (Chrome adds a dropdown arrow, Safari rounds the corners). `appearance: none` strips all of this, giving us a blank canvas. We then add our own SVG chevron icon, positioned absolutely inside the input wrapper.

**The `~` general sibling combinator** — Unlike `+` (adjacent sibling), `~` targets *any* sibling that comes after the select. This lets us style the chevron based on the select's focus state, even if there are other elements between them.

#### Step 3: Password Strength Bar

```css
.password-strength__fill {
  height: 100%;
  width: 0%;
  border-radius: var(--radius-full);
  transition: width var(--transition), background var(--transition);
}

.password-strength__fill--weak   { width: 33%;  background: var(--color-danger); }
.password-strength__fill--fair   { width: 66%;  background: var(--color-warning); }
.password-strength__fill--strong { width: 100%; background: var(--color-success); }
```

**The modifier class pattern (BEM):** The base class `.password-strength__fill` defines the shape and transition. The modifier classes (`--weak`, `--fair`, `--strong`) only change `width` and `background`. JavaScript swaps these modifier classes dynamically as the user types, and the CSS `transition` property handles the smooth animation.

**Performance consideration:** Animating `width` triggers a *reflow* (the browser recalculates the layout of the bar and its container). In this case it's acceptable because the element is small and isolated. For larger elements, prefer `transform: scaleX()` which only triggers *compositing*.

#### Step 4: Custom Checkbox

```css
.checkbox-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.checkbox-custom {
  width: 20px;
  height: 20px;
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-sm);
  color: transparent;
  transition: all var(--transition-fast);
}

.checkbox-input:checked + .checkbox-custom {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: #fff;
}
```

**The same hide-and-replace pattern as radio cards.** The native checkbox is visually hidden but remains functional. The custom `<span>` uses `color: transparent` to hide the SVG checkmark by default, and `color: #fff` when checked to reveal it. This is more performant than toggling `display` or `visibility` on the SVG.

---

### Textbook Glossary — Selectors & Properties (Registration-Specific)

| Selector / Property | Mechanics | Expert Expansion |
|---|---|---|
| `grid-template-columns: 1fr 1fr` | Creates two equal-width columns in CSS Grid. | `1fr` = one fraction of available space. Grid is ideal for 2D layouts (rows AND columns), while Flexbox is for 1D (single axis). |
| `.radio-card__input:checked + .radio-card__body` | Styles the sibling element when the radio is checked. | The `+` combinator requires the elements to be adjacent siblings. This is the foundation of the "hidden input" pattern for custom form controls. |
| `opacity: 0` + `position: absolute` | Visually hides while preserving accessibility. | Unlike `display: none` (removes from accessibility tree) or `visibility: hidden` (takes space), this combo is invisible, takes no space, but remains accessible. |
| `appearance: none` | Strips browser default form control styling. | Required for cross-browser consistency. Safari, Chrome, and Firefox all render `<select>`, `<checkbox>`, and `<radio>` differently. |
| `.form-select:focus ~ .select-chevron` | Styles a sibling based on the select's focus state. | The `~` general sibling combinator works across non-adjacent siblings. Use `+` for adjacent only. |
| `transition: width 0.3s, background 0.3s` | Animates multiple properties simultaneously. | Comma-separated transitions allow different durations per property. `width` causes reflow; `transform`/`opacity` are GPU-accelerated alternatives. |
| `[data-met="true"]` | Attribute selector targeting a custom data attribute. | CSS attribute selectors can match exact values (`=`), prefixes (`^=`), suffixes (`$=`), or contains (`*=`). More semantic than class-based state. |
| `pointer-events: none` | Makes the element non-interactive (clicks pass through). | Used on decorative overlays (icons, chevrons) that shouldn't capture mouse events meant for the input underneath. |
| `will-change: transform` | Hints to the browser that a property will animate soon. | Creates a compositor layer in advance, preventing jank on first frame. Overuse wastes GPU memory. Only apply to elements that actually animate. |

---

## Part III — The JS Textbook: Logic, Security & The Event Loop

### Chapter Overview

The Registration page's JavaScript extends the Login page's architecture with three new patterns:

1. **Password Strength Analyzer** — A real-time scoring engine that evaluates password complexity.
2. **Cross-Field Validation** — The confirm-password field depends on the password field's value (inter-dependent state).
3. **Multi-Field Sequential Validation** — Seven fields must be validated in order on submit, with error focus management.

The same Module Pattern applies, but with an additional `PasswordStrength` module and significantly more complex event wiring.

---

### Password Strength: A State Machine

The password strength indicator is effectively a simple **state machine** with three states:

```
                    ┌──────────────────┐
         0 reqs    │   (empty)        │
         met       │   No indicator   │
                    └────────┬─────────┘
                             │ user types
                    ┌────────▼─────────┐
         1 req     │   WEAK           │  → red bar (33%)
         met       │   Score: 1       │
                    └────────┬─────────┘
                             │ more reqs met
                    ┌────────▼─────────┐
         2 reqs    │   FAIR           │  → amber bar (66%)
         met       │   Score: 2       │
                    └────────┬─────────┘
                             │ all reqs met
                    ┌────────▼─────────┐
         3 reqs    │   STRONG         │  → green bar (100%)
         met       │   Score: 3       │
                    └──────────────────┘
```

**Implementation:**

```javascript
const PasswordStrength = {
  analyze(password) {
    const requirements = {
      length:    password.length >= 8,
      uppercase: /[A-Z]/.test(password),
      number:    /\d/.test(password),
    };
    
    const metCount = Object.values(requirements).filter(Boolean).length;
    const levels = ['weak', 'weak', 'fair', 'strong'];
    
    return {
      score: metCount,
      level: levels[metCount],
      requirements,
    };
  },
};
```

**Why `Object.values().filter(Boolean).length`?** This is a functional programming pattern:
1. `Object.values(requirements)` → `[true, false, true]`
2. `.filter(Boolean)` → `[true, true]` (removes falsy values)
3. `.length` → `2`

This is more maintainable than counting manually with if/else chains. When we add a new requirement (e.g., "one special character"), we just add it to the `requirements` object — the scoring logic adapts automatically.

---

### Cross-Field Validation

```javascript
onPasswordInput() {
  const password = DOM.passwordInput.value;
  UI.updatePasswordStrength(password);
  
  // Also re-validate confirm password if it has a value
  if (DOM.confirmInput && DOM.confirmInput.value) {
    const confirmResult = Validators.confirmPassword(password, DOM.confirmInput.value);
    if (confirmResult.valid) {
      UI.clearError(DOM.confirmGroup, DOM.confirmInput, DOM.confirmError);
      UI.setSuccess(DOM.confirmInput);
    } else if (DOM.confirmInput.classList.contains('form-input--error')) {
      UI.showError(DOM.confirmGroup, DOM.confirmInput, DOM.confirmError, confirmResult.message);
    }
  }
},
```

**Why re-validate confirm password when the password changes?** If the user types password "Hello123", then types "Hello123" in confirm (✅ match), then changes password to "Hello456" — the confirm field is now WRONG but wouldn't update without this cross-field check. This is a common UX bug in poorly implemented forms.

---

### Ethiopian Phone Number Validation

```javascript
phone(value) {
  const phoneRegex = /^(?:\+251|0)(?:9|7)\d{8}$/;
  const digitsOnly = trimmed.replace(/[\s\-()]/g, '');
  if (!phoneRegex.test(digitsOnly)) {
    return { valid: false, message: 'Enter a valid Ethiopian phone number.' };
  }
}
```

**Regex breakdown:**
- `^(?:\+251|0)` — Starts with country code `+251` or local `0`
- `(?:9|7)` — Mobile prefix (Ethio Telecom: 9xx, Safaricom: 7xx)
- `\d{8}$` — Followed by exactly 8 digits

**Why strip formatting characters first?** Users enter phone numbers in many formats: `+251 911 234 567`, `0911-234-567`, `(0911) 234567`. Stripping spaces, hyphens, and parentheses normalizes them all to the same format for regex validation.

---

### Multi-Field Submit Pipeline

```javascript
onSubmit(event) {
  event.preventDefault();
  const errors = [];

  // Validate each field in order
  const nameResult = Validators.name(data.name);
  if (!nameResult.valid) {
    UI.showError(DOM.nameGroup, DOM.nameInput, DOM.nameError, nameResult.message);
    errors.push(nameResult.message);
  }
  // ... repeat for all 7 fields ...

  if (errors.length > 0) {
    UI.announceStatus(`Form has ${errors.length} errors. ${errors[0]}`);
    const firstError = DOM.form.querySelector('.form-input--error');
    if (firstError) firstError.focus();
    return;
  }
}
```

**Why validate ALL fields before stopping?** Unlike some forms that stop at the first error, we validate everything and show ALL errors simultaneously. This respects the user's time — they can fix all issues in one pass instead of playing "whack-a-mole" with sequential errors.

**Why focus the first errored input?** Accessibility requirement. Screen reader users can't see the red error highlights. Focusing the first errored input causes the screen reader to announce: "Full name, edit text, required. Full name is required."

---

### Textbook Glossary — JavaScript APIs (Registration-Specific)

| API / Pattern | Mechanics | Expert Expansion |
|---|---|---|
| `Object.values(obj)` | Returns an array of the object's own enumerable property values. | Used in password strength to extract `[true, false, true]` from the requirements object. ES2017 — polyfill for IE11. |
| `Array.filter(Boolean)` | Removes all falsy values from an array. | `Boolean` is a constructor function that returns `true`/`false`. Passing it to `.filter()` removes `false`, `0`, `''`, `null`, `undefined`, `NaN`. |
| `String.replace(/regex/g, '')` | Replaces all matches of a regex with empty string. | The `g` flag is required for replacing all occurrences. Without it, only the first match is replaced. |
| `document.querySelector('.class')` | Returns the first element matching a CSS selector. | Slower than `getElementById` (selector parsing + tree traversal). Use for one-off queries, not in hot paths. |
| `?.` (Optional Chaining) | Returns `undefined` instead of throwing if the left side is `null`/`undefined`. | `document.querySelector(...)?.value` is safer than assuming the element exists. Prevents "Cannot read property of null" errors. |
| `element.dataset.met` | Accesses the `data-met` custom attribute as a JS property. | All `data-*` attributes are accessible via `element.dataset`. Kebab-case attributes become camelCase: `data-my-val` → `dataset.myVal`. |
| `input.checked` | Boolean property for checkbox/radio state. | Unlike `input.value` (always a string), `checked` is a true boolean. Use for conditional logic without string comparison. |
| `new Event('submit', { cancelable: true })` | Programmatically creates and dispatches a DOM event. | `cancelable: true` allows `preventDefault()` to work on the synthetic event. Without it, `preventDefault()` is silently ignored. |


