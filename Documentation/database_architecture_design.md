# Database Architecture Design
## Secure Local Service Marketplace

Document Version: 2.0  
Last Updated: April 24, 2026  
Project: Market_Place_Web_Dev_Project

---

## 1. Purpose

This document explains the database architecture that supports the marketplace workflow.
It focuses on structure, integrity, indexing, state transitions, and security.

Primary workflow:

Requested -> Negotiating -> Assigned -> Completed -> Reviewed

---

## 2. Architecture Principles

1. Request status controls the main business flow.
2. Every critical action must be traceable.
3. Foreign keys protect data integrity.
4. Indexes support frequent search and dashboard queries.
5. Queries must be written with prepared statements.

---

## 3. Core Entities

1. users
2. service_categories
3. service_requests
4. offers
5. reviews
6. audit_logs
7. status_history

---

## 4. Table Overview

### 4.1 users

Stores all platform accounts: customer, provider, and admin.

Key fields:

1. email
2. password_hash
3. role
4. name
5. phone
6. location
7. rating_average
8. total_reviews
9. is_verified

### 4.2 service_categories

Stores the service types offered in the marketplace.

Examples:

1. Electrician
2. Plumber
3. Carpenter
4. Painter
5. Cleaner

### 4.3 service_requests

Stores customer job posts and drives the state machine.

Key fields:

1. customer_id
2. category_id
3. description
4. preferred_date
5. status
6. location
7. accepted_offer_id
8. completion_photo

### 4.4 offers

Stores provider quotations for each request.

Key fields:

1. request_id
2. provider_id
3. price
4. message
5. status
6. counter_price
7. counter_message

### 4.5 reviews

Stores the post-completion rating and feedback for each request.

### 4.6 audit_logs

Stores critical system actions for monitoring and security review.

### 4.7 status_history

Stores timeline data for each request state transition.

---

## 5. Workflow State Machine

State transitions:

1. Requested -> Negotiating when offers arrive.
2. Negotiating -> Assigned when a customer accepts an offer.
3. Assigned -> Completed when the provider marks the job complete.
4. Completed -> Reviewed when the customer submits a review.

---

## 6. Stored Procedures

### 6.1 log_status_change

Logs a request status transition and stores the time spent in the previous state.

### 6.2 accept_offer

Performs offer acceptance as a single transaction.

It should:

1. Mark the chosen offer as accepted.
2. Update the request status to Assigned.
3. Store the accepted offer on the request.
4. Write an audit log entry.
5. Write a status history entry.

---

## 7. Views

### 7.1 v_active_requests

Returns open requests for customer and provider browsing.

### 7.2 v_provider_ratings

Returns provider rating statistics for dashboards and ranking.

---

## 8. Indexing Strategy

Recommended indexes:

1. users.role
2. users.location
3. users.rating_average
4. service_requests.customer_id
5. service_requests.status
6. service_requests.category_id
7. offers.request_id
8. offers.provider_id
9. offers.status
10. reviews.provider_id
11. reviews.rating
12. audit_logs.user_id
13. audit_logs.action
14. status_history.request_id

---

## 9. Security Rules

1. Use PDO prepared statements.
2. Hash passwords with password_hash.
3. Sanitize all output with htmlspecialchars or equivalent.
4. Do not expose internal database details in the UI.
5. Validate file uploads before storage.

---

## 10. Implementation Checklist

1. Review the ERD and schema together.
2. Import the schema into MySQL.
3. Test the workflow with sample data.
4. Verify status transitions in code.
5. Confirm all dashboard queries use indexes.
