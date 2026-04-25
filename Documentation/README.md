# Service Marketplace Platform - UI/UX Design Specification

## Project Overview
**Project Name:** Secure Local Service Marketplace (Handy Model)  
**Repository:** Market_Place_Web_Dev_Project  
**Product Type:** Web application for local service matching  
**Primary Context:** Addis Ababa local services (location-aware requests and providers)  
**Design Approach:** Responsive, mobile-first, conversion-focused UX

### Product Purpose
This platform connects customers who need local services (electrician, plumber, carpenter, cleaner, painter, etc.) with service providers who submit offers, negotiate price, complete work, and receive reviews.

### Workflow Backbone (State Machine)
The UX must reflect and enforce this request lifecycle:

1. **Requested**
2. **Negotiating**
3. **Assigned**
4. **Completed**
5. **Reviewed**

This status progression is core to the project architecture and should be visible in dashboards, request cards, and detail pages.

---

## Technology Constraints (Project-Specific)
Based on this repository's planning documents:

- **Frontend:** HTML5, CSS3, Bootstrap 5, Vanilla JavaScript
- **Backend:** PHP 8.1+ (no Laravel/CodeIgniter)
- **Database:** MySQL 8.0+ / MariaDB
- **Server:** Apache (XAMPP/WAMP for local development)

### UI Engineering Notes
- Use Bootstrap grid/utilities as the baseline layout system.
- Add custom CSS only where Bootstrap defaults are insufficient.
- Use Vanilla JavaScript for interactions (modals, validation, dynamic updates).
- Keep components accessible and readable on low-end mobile devices.

---

## User Roles

### 1. Customer
- Registers and logs in
- Creates service requests
- Receives and compares provider offers
- Accepts/counters offers
- Submits reviews after completion

### 2. Service Provider
- Registers and logs in
- Browses active requests
- Submits offers with message and price
- Completes assigned jobs and uploads completion proof
- Receives ratings and feedback

### 3. Admin (Operational UX)
- Verifies providers
- Reviews audit information and system activity

---

## Core UX Goals

1. Help customers post requests quickly with minimal friction.
2. Help providers discover relevant jobs fast (location + category).
3. Make negotiation transparent (offer status and counters).
4. Surface request state clearly at all times.
5. Build trust through verification cues, ratings, and reviews.
6. Keep all key actions possible within 2-3 user interactions.

---

## Information Architecture

### Primary Navigation
- Home
- Browse Services / Requests
- Dashboard
- My Requests (Customer)
- My Offers / Assigned Jobs (Provider)
- Profile
- Login / Register / Logout

### Dashboard Navigation (Role-Based)
- **Customer:** Dashboard, My Requests, Offers, Reviews, Profile
- **Provider:** Dashboard, Browse Requests, My Offers, Assigned Jobs, Profile
- **Admin:** Dashboard, Provider Verification, Logs/Monitoring

---

## Screen Specifications

## 1. Landing Page
### Sections
- Navbar (logo, navigation, auth actions)
- Hero section with strong CTA
- Service categories/features highlight
- "How It Works" (post request -> get offers -> assign -> complete -> review)
- Testimonials / trust indicators
- Footer (contact, policy, support links)

### UX Notes
- CTA visible above the fold.
- Keep copy short and action-oriented.
- Prioritize trust language (verified providers, transparent offers).

---

## 2. Authentication

### Login
- Email
- Password
- Login button
- Link to register

### Register
- Full name
- Email
- Password
- Role selection (Customer / Provider)
- Phone
- Location (sub-city)
- Register button

### UX Notes
- Inline validation and clear error states.
- Show password requirements before submit.
- Keep form width constrained for readability.

---

## 3. Customer Dashboard

### Layout
- Sidebar + top bar + content area

### Sections
- Summary cards: Active requests, Negotiating, Assigned, Completed
- Recent request activity timeline
- Quick actions (Post New Request)

### Components
- Status badges aligned to state machine
- Request cards/table with filters
- Offer count indicator per request

### UX Notes
- Status should be visible without opening request details.
- Prioritize pending customer actions (e.g., offers awaiting decision).

---

## 4. Provider Dashboard

### Layout
- Sidebar + top bar + content area

### Sections
- Summary cards: Open offers, Accepted offers, Assigned jobs, Completed jobs
- Nearby/open request feed (category + location)
- Requests management and offer tracking

### Components
- Request table/cards with "Submit Offer" CTA
- Offer status chips (pending/accepted/rejected/countered)
- Completion action for assigned jobs

### UX Notes
- Highlight requests relevant to provider location and category.
- Minimize steps for offer submission.

---

## 5. Marketplace / Listings (Active Requests)

### Components
- Search input (keyword)
- Filter panel (category, location, price range, rating cues)
- Responsive request/service grid

### Card Structure
- Category icon/name
- Brief request description
- Location
- Preferred date
- Current status
- CTA: View Details / Submit Offer

### Responsive Behavior
- Mobile: 1-column list
- Tablet: 2-column
- Desktop: 3-4 columns depending on viewport

### UX Notes
- Optimize scanning with consistent card layout.
- Avoid dense text blocks; use metadata rows.

---

## 6. Service Request Detail Page

### Sections
- Request title and category
- Full description
- Location and preferred date
- Offer panel (for providers)
- Offer management panel (for customer)
- Provider/customer identity summary (contextual)

### Action Areas
- Submit offer (provider)
- Accept/counter/reject offer (customer)
- Status timeline

### UX Notes
- Keep content and action zones visually separated.
- Use sticky action panel on desktop for key CTAs.

---

## 7. Offer Negotiation Modal

### Fields
- Offer price
- Optional message
- Counter price (when customer counters)
- Counter message

### States
- pending
- countered
- accepted
- rejected

### UX Notes
- Show current and proposed prices clearly.
- Confirm destructive decisions (reject/cancel).

---

## 8. Completion and Review Flow

### Provider Completion UI
- "Mark as Finished" action
- Completion photo upload
- Confirmation prompt

### Customer Review UI
- Star rating (1-5)
- Optional text review
- Submit button

### UX Notes
- Only allow review when request is **Completed**.
- Show final state confirmation when moved to **Reviewed**.

---

## Layout System

- Bootstrap 12-column grid on desktop
- Collapsed single-column flow on mobile where needed
- 8px spacing scale: 8, 16, 24, 32, 40, 48
- Standardized section padding and vertical rhythm
- Max-width containers for readability on large screens

---

## Design System

## Color Direction
- **Primary:** Blue family for key actions and focus states
- **Secondary:** Light neutral backgrounds for section separation
- **Semantic:**
  - Success (Completed/Reviewed)
  - Warning (Negotiating)
  - Info (Requested)
  - Accent (Assigned)
- **Text/Borders:** Neutral grayscale with accessible contrast

## Typography
- Strong heading hierarchy (H1-H4)
- Readable body text for form-heavy flows
- Consistent line-height and spacing for dense dashboard data

## Core Components
- Buttons: primary, secondary, danger, disabled
- Inputs: default, focus, invalid, disabled
- Cards: rounded corners, low-elevation shadows
- Tables: compact but readable row height
- Badges: state machine labels
- Modals: centered with dismiss/confirm actions

## Interaction States
- Hover feedback on all clickable elements
- Focus-visible outlines for keyboard users
- Disabled state with reduced opacity + blocked pointer
- Loading states for async operations (spinner/skeleton)

---

## Interaction and Behavior Standards

1. Forms validate both on input and on submit.
2. Show user-friendly validation and server error messages.
3. Use asynchronous updates where practical (offer actions, status refresh).
4. Preserve context after actions (do not disorient users with unnecessary full reloads).
5. Keep state transitions explicit with badges/timeline updates.

---

## Accessibility Requirements

- Meet WCAG-friendly contrast targets.
- Ensure keyboard navigation across forms, modals, and dashboard controls.
- Provide descriptive labels and error messaging.
- Use semantic HTML for structure and screen-reader compatibility.

---

## Security-Aware UX Requirements

- Never expose sensitive backend/system details in UI errors.
- Use confirmation prompts for critical actions (accept offer, mark complete).
- Include upload constraints and feedback for completion photos.
- Reflect authentication/session state clearly in navigation.

---

## Visual Direction
Modern, clean marketplace UI inspired by high-trust SaaS products:

- High whitespace usage
- Clear hierarchy and card-based scanning
- Rounded components with subtle elevation
- Minimal decorative noise
- Strong emphasis on clarity over ornament

---

## Delivery Checklist for UI Implementation

- Landing, auth, customer dashboard, provider dashboard, listings, detail, negotiation, and review flows designed
- Components follow a shared style system
- Status machine represented consistently in every relevant screen
- Mobile-first behavior validated
- Bootstrap-compatible and production-ready structure

---

## Repository Reference Files Used

- Documentation/database_architecture_design.md
- Documentation/database_schema.sql
- Documentation/implementation_roadmap.md
- Documentation/database_relationships.md
- handy_marketplace_erd.html

This README is intentionally aligned with the current database model, project roadmap, and state-machine workflow defined in the repository.
