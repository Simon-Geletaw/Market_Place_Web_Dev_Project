# Database Relationships
## Secure Local Service Marketplace

Document Version: 2.0  
Last Updated: April 24, 2026  
Project: Market_Place_Web_Dev_Project

---

## 1. Purpose

This document explains how the database tables relate to each other and how those relationships support the marketplace workflow.

---

## 2. Core Entities

1. users
2. service_categories
3. service_requests
4. offers
5. reviews
6. audit_logs
7. status_history

All primary and foreign keys are UUID strings (CHAR(36)).

---

## 3. Relationship Summary

1. A user can create many service requests.
2. A service category can contain many service requests.
3. A service request can receive many offers.
4. A provider can submit many offers.
5. A service request can have one accepted offer.
6. A service request can have one review.
7. A customer can write many reviews.
8. A provider can receive many reviews.
9. A user can create many audit log entries.
10. A service request can have many status history records.

---

## 4. Key Foreign Key Links

1. service_requests.customer_id -> users.user_id
2. service_requests.category_id -> service_categories.category_id
3. offers.request_id -> service_requests.request_id
4. offers.provider_id -> users.user_id
5. reviews.request_id -> service_requests.request_id
6. reviews.customer_id -> users.user_id
7. reviews.provider_id -> users.user_id
8. audit_logs.user_id -> users.user_id
9. status_history.request_id -> service_requests.request_id
10. status_history.changed_by -> users.user_id

---

## 5. Business Rules

1. Only customers create service requests.
2. Only providers submit offers.
3. Only verified providers should be highlighted in the UI.
4. One provider can submit only one offer per request.
5. One request can have only one review.
6. A review is allowed only after completion.

---

## 6. Workflow Relationship

The request lifecycle follows this path:

Requested -> Negotiating -> Assigned -> Completed -> Reviewed

Relationship impact:

1. Offers move a request into Negotiating.
2. Acceptance links a request to one winning offer.
3. Completion stores proof and changes the request state.
4. Review closes the workflow and updates provider reputation.

---

## 7. Integrity and Cascade Rules

1. Customer deletion removes their requests.
2. Request deletion removes related offers, reviews, and status history.
3. Category deletion should be restricted when requests exist.
4. User deletion should preserve audit history where possible.

---

## 8. Relationship Diagrams Used in UI

1. Customer dashboard uses request-to-offer relationships.
2. Provider dashboard uses category-to-request relationships.
3. Admin dashboard uses user-to-audit-log relationships.
4. Request detail pages use request-to-status-history relationships.
