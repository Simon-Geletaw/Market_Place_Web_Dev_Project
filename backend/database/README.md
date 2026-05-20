# Market Place Database Documentation

This document provides a comprehensive overview of the `market_place` database architecture, schema, and core logic.

## 1. Overview
The `market_place` database is designed to power a service marketplace connecting Customers with Service Providers. It handles user management, service requests, bidding (offers), reviews, and maintains high traceability through audit logging and status history tracking.

- **System:** MySQL 8.0+ (InnoDB)
- **Charset:** utf8mb4
- **Collation:** utf8mb4_unicode_ci

## 2. Core Schema Structure

### 2.1 Users (`USERS`)
The central identity table for all platform participants.
- **Roles:** `Customer`, `Provider`, `Admin`.
- **Key Fields:** `USER_ID` (UUID), `EMAIL`, `PASSWORD_HASH`, `RATING_AVERAGE`.
- **Traceability:** Tracks `CREATED_AT` and `UPDATED_AT`.

### 2.2 Service Categories (`SERVICE_CATEGORIES`)
Defines the types of services available (e.g., Plumbing, IT, Cleaning).
- **Constraints:** `NAME` must be unique. `IS_ACTIVE` flag for soft-disabling categories.

### 2.3 Service Requests (`SERVICE_REQUESTS`)
The central workflow entity. Represents a job posted by a customer.
- **Statuses:** `Requested`, `Negotiating`, `Assigned`, `Completed`, `Reviewed`.
- **Relationships:** Linked to a `Customer` and a `Category`.
- **Workflow:** Updates to `Assigned` when an offer is accepted.

### 2.4 Offers (`OFFERS`)
Bids made by Providers on specific Service Requests.
- **Statuses:** `Pending`, `Accepted`, `Rejected`, `Countered`.
- **Constraints:** `UNIQUE_PROVIDER_REQUEST` ensures a provider can only make one offer per request.

### 2.5 Reviews (`REVIEWS`)
Feedback given by Customers to Providers after a request is `Completed`.
- **Constraints:** `REQUEST_ID` is unique, ensuring only one review per transaction. `RATING` is enforced between 1 and 5.

### 2.6 Traceability Tables
- **`AUDIT_LOGS`**: Records specific security or sensitive actions (e.g., login, profile updates, offer acceptance).
- **`STATUS_HISTORY`**: Tracks every state transition of a `SERVICE_REQUEST`, including duration in each state.

## 3. Workflow Logic (Planned Stored Procedures)
_Note: Implementations for these are found in `Documentation/functions.sql` and should be ported to MySQL syntax._

- **`log_status_change`**: Automatically called during status updates to populate `STATUS_HISTORY`.
- **`accept_offer`**: Handles the atomic operation of marking an offer as `Accepted`, updating the request to `Assigned`, and logging the event.

## 4. Reporting Views
- **`v_active_requests`**: Returns only requests in `Requested` or `Negotiating` status with joined category and customer names.
- **`v_provider_ratings`**: Aggregates review data to calculate average ratings and volume for each Provider.

## 5. Security & Constraints
1. **Foreign Key Integrity:** 
   - `ON DELETE CASCADE` is used for user-related data (requests, offers, reviews).
   - `ON DELETE RESTRICT` for categories ensure taxonomy integrity.
   - `ON DELETE SET NULL` for `accepted_offer_id` prevents breaking requests if an offer is removed.
2. **Indexing:** Primary keys are UUIDs. Unique constraints on emails and transaction-specific links.
3. **Auditability:** Every change is timestamped, and status changes are versioned in history tables.

