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
