# UI/UX Design Specification
## Secure Local Service Marketplace Platform

Document Version: 2.0  
Last Updated: April 24, 2026  
Project: Market_Place_Web_Dev_Project  
Platform: Responsive Web App  
Technology Stack: HTML5, CSS3, Bootstrap 5, Vanilla JavaScript, PHP 8.1+, MySQL 8+

---

## 1. Purpose

This document defines the interface, user flows, components, and role-based screens for the marketplace.

It is written to be useful for design tools, implementation planning, and frontend development.

Primary workflow:

Requested -> Negotiating -> Assigned -> Completed -> Reviewed

---

## 2. Design Goals

1. Make request posting fast for customers.
2. Help providers find relevant jobs quickly.
3. Make trust and status easy to understand.
4. Keep admin operations clear and efficient.
5. Keep the experience usable on mobile and desktop.

---

## 3. Stitch-Ready Design Inputs

Use these when generating screens in Stitch or a similar design tool.

### 3.1 Project Context

1. Product: local service marketplace
2. Audience: customers, providers, admins
3. Main purpose: request, offer, assign, complete, review
4. Visual direction: clean, modern, trust-focused, card-based layout

### 3.2 Design Tokens

Primary colors:

1. Primary blue: #0066CC
2. Hover blue: #0052A3
3. Info blue: #0099FF
4. Warning amber: #FFA500
5. Success green: #28A745
6. Completion teal: #20C997
7. Review blue: #4C7CFF

Neutral colors:

1. Text primary: #1A1A1A
2. Text secondary: #666666
3. Background: #FFFFFF
4. Muted background: #F8F9FA
5. Border: #E0E0E0

Spacing scale:

1. 4px
2. 8px
3. 16px
4. 24px
5. 32px
6. 48px

### 3.3 Reusable Components

1. Navbar
2. Sidebar
3. Status badge
4. Summary card
5. Request card
6. Offer card
7. Modal shell
8. Form field
9. Timeline
10. Toast message

---

## 4. Role Classification

## 4.1 Customer

Customer goals:

1. Post service requests.
2. Compare provider offers.
3. Accept or counter offers.
4. Track progress.
5. Leave reviews.

Customer screens:

1. Landing page
2. Login and register
3. Customer dashboard
4. My requests
5. Request detail page
6. Offers panel
7. Review modal

Customer UI priorities:

1. Fast request creation.
2. Clear offer comparison.
3. Visible request status.
4. Simple completion and review flow.

## 4.2 Provider

Provider goals:

1. Find active jobs.
2. Submit competitive offers.
3. Track accepted jobs.
4. Mark jobs complete.
5. Build ratings.

Provider screens:

1. Login and register
2. Provider dashboard
3. Browse requests
4. Offer modal
5. Assigned jobs
6. Completion modal
7. Reviews received

Provider UI priorities:

1. Quick filtering by category and location.
2. Strong submit offer action.
3. Trust badge for verification.
4. Clear job progress tracking.

## 4.3 Admin

Admin goals:

1. Verify providers.
2. Monitor system activity.
3. Review audit logs.
4. Watch marketplace health.

Admin screens:

1. Admin dashboard
2. Verification queue
3. Audit logs
4. Metrics view

Admin UI priorities:

1. Dense but readable data layouts.
2. Clear approve and reject actions.
3. Monitoring widgets and filters.

---

## 5. Information Architecture

Global navigation:

1. Home
2. Browse
3. Dashboard
4. Notifications
5. Profile menu

Customer sidebar:

1. Dashboard
2. My Requests
3. Offers Received
4. Assigned Jobs
5. Completed Jobs
6. Reviews Given
7. Profile

Provider sidebar:

1. Dashboard
2. Browse Requests
3. My Offers
4. Assigned Jobs
5. Completed Jobs
6. Reviews Received
7. Profile

Admin sidebar:

1. Dashboard
2. Provider Verification
3. Audit Logs
4. System Metrics
5. Profile

---

## 6. Screen Specifications

## 6.1 Landing Page

Sections:

1. Navbar
2. Hero area
3. Service categories
4. How it works
5. Testimonials
6. Footer

Recommended improvements:

1. Add a trust panel near the hero.
2. Show verified provider count.
3. Use one strong primary CTA and one secondary CTA.
4. Keep copy short and direct.

## 6.2 Authentication

Login fields:

1. Email
2. Password

Register fields:

1. Full name
2. Email
3. Password
4. Role selection
5. Phone number
6. Location

UI requirements:

1. Inline validation.
2. Clear error messages.
3. Password rules shown before submission.

## 6.3 Customer Dashboard

Main sections:

1. Summary cards
2. Action required items
3. Request list
4. Filters and sorting

State-aware actions:

1. Requested: view details.
2. Negotiating: manage offers.
3. Assigned: track progress.
4. Completed: leave review.

## 6.4 Provider Dashboard

Main sections:

1. Summary cards
2. Nearby requests
3. My offers
4. Assigned jobs

Recommended improvements:

1. Show matching jobs by category and location.
2. Make offer submission easy to open and complete.
3. Show verified badge and rating near the top.

## 6.5 Marketplace and Request Detail

Marketplace features:

1. Search
2. Filters
3. Request cards
4. Pagination or load more

Request detail features:

1. Request summary
2. Customer or provider context card
3. Status timeline
4. Offer panel
5. Sticky action area on desktop

## 6.6 Offer and Review Modals

Offer modal:

1. Price field
2. Optional message
3. Validation and submit state

Completion modal:

1. Photo upload
2. Optional notes
3. Confirmation button

Review modal:

1. Star rating
2. Optional feedback
3. Submit confirmation

---

## 7. Component Rules

Buttons:

1. Primary
2. Secondary
3. Success
4. Danger

Cards:

1. Rounded corners
2. Low shadow
3. Clear padding
4. Hover state

Status badges:

1. Requested
2. Negotiating
3. Assigned
4. Completed
5. Reviewed

Forms:

1. Validate on input and submit.
2. Keep errors near the field.
3. Do not hide submitted values on error.

---

## 8. Interaction Flows

### 8.1 Customer posts a request

1. Open create request form.
2. Enter category, description, date, and location.
3. Submit and create a Requested item.
4. Show success message and request detail page.

### 8.2 Provider submits an offer

1. Open request detail.
2. Fill price and optional message.
3. Submit offer.
4. Update the request state when needed.

### 8.3 Customer accepts an offer

1. Review offers.
2. Select one offer.
3. Confirm acceptance.
4. Move the request to Assigned.

### 8.4 Provider marks job complete

1. Open assigned job.
2. Upload completion photo.
3. Submit completion.
4. Move request to Completed.

### 8.5 Customer submits review

1. Open completed request.
2. Select rating and optional comment.
3. Submit review.
4. Move request to Reviewed.

---

## 9. Responsive Behavior

Mobile:

1. Single-column layout.
2. Drawer sidebar.
3. Full-width buttons where needed.
4. Cards instead of dense tables.

Tablet:

1. Two-column layouts where useful.
2. Compact sidebar or drawer.

Desktop:

1. Full sidebar navigation.
2. Multi-column dashboards.
3. Sticky detail and action panels.

---

## 10. Accessibility and Security

1. Use semantic HTML.
2. Keep focus states visible.
3. Ensure high contrast text.
4. Provide labels for all inputs.
5. Show confirmation for critical actions.
6. Sanitize user-generated content before display.
7. Show clear file upload rules and limits.

---

## 11. Stitch Export Checklist

1. Reuse components consistently.
2. Keep role variants separate.
3. Keep all status badges aligned with the database states.
4. Include loading and empty states.
5. Define mobile and desktop behavior for each screen.
