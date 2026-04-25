# Database Design
## Service Marketplace Platform (PHP + MySQL)

**Status:** Legacy (superseded by `Documentation/backend.md`). This file is kept for deeper table-by-table notes.

Document Version: 1.0  
Last Updated: April 24, 2026

---

## 1. Scope and Source of Truth

This document is based strictly on the project schema and documentation:

1. Documentation/database_schema.sql
2. Documentation/database_architecture_design.md
3. Documentation/database_relationships.md
4. handy_marketplace_erd.html

No additional entities or out-of-scope features are introduced.

Core lifecycle:

Requested -> Negotiating -> Assigned -> Completed -> Reviewed

---

## 2. Entity Inventory

The system has 7 core tables:

1. users
2. service_categories
3. service_requests
4. offers
5. reviews
6. audit_logs
7. status_history

---

## 3. Table Definitions

## 3.1 users

Purpose:

Stores all platform identities and role information.

Fields:

1. id, INT, PK, AUTO_INCREMENT
2. email, VARCHAR(255), NOT NULL, UNIQUE
3. password_hash, VARCHAR(255), NOT NULL
4. role, ENUM(customer, provider, admin), NOT NULL
5. name, VARCHAR(255), NOT NULL
6. phone, VARCHAR(20), nullable
7. location, VARCHAR(255), nullable
8. rating_average, DECIMAL(3,2), default 0.00
9. total_reviews, INT, default 0
10. is_verified, BOOLEAN, default false
11. created_at, TIMESTAMP, default current timestamp
12. updated_at, TIMESTAMP, auto update

Indexes:

1. idx_role
2. idx_location
3. idx_rating

Design decisions:

1. Role enum enforces allowed identity types.
2. Provider reputation fields are denormalized for dashboard speed.
3. is_verified supports trust and admin moderation flow.

## 3.2 service_categories

Purpose:

Stores service taxonomy used by request posting and browsing.

Fields:

1. id, INT, PK, AUTO_INCREMENT
2. name, VARCHAR(100), NOT NULL, UNIQUE
3. description, TEXT, nullable
4. icon, VARCHAR(50), nullable
5. is_active, BOOLEAN, default true
6. created_at, TIMESTAMP, default current timestamp

Design decisions:

1. Categories are normalized into a separate table.
2. Unique name prevents duplicate category labels.
3. is_active enables soft deactivation.

## 3.3 service_requests

Purpose:

Stores customer job requests and drives lifecycle state.

Fields:

1. id, INT, PK, AUTO_INCREMENT
2. customer_id, INT, FK -> users.id, NOT NULL
3. category_id, INT, FK -> service_categories.id, NOT NULL
4. description, TEXT, NOT NULL
5. preferred_date, DATE, nullable
6. status, ENUM(Requested, Negotiating, Assigned, Completed, Reviewed), default Requested
7. location, VARCHAR(255), NOT NULL
8. accepted_offer_id, INT, nullable
9. completion_photo, VARCHAR(500), nullable
10. created_at, TIMESTAMP, default current timestamp
11. updated_at, TIMESTAMP, auto update

Foreign key behavior:

1. customer_id ON DELETE CASCADE
2. category_id ON DELETE RESTRICT

Indexes:

1. idx_customer
2. idx_status
3. idx_category
4. idx_preferred_date

Design decisions:

1. status is the state machine backbone.
2. accepted_offer_id links the winning negotiation result.
3. completion_photo supports proof-of-work requirement.

## 3.4 offers

Purpose:

Stores provider quotations and negotiation data.

Fields:

1. id, INT, PK, AUTO_INCREMENT
2. request_id, INT, FK -> service_requests.id, NOT NULL
3. provider_id, INT, FK -> users.id, NOT NULL
4. price, DECIMAL(10,2), NOT NULL
5. message, TEXT, nullable
6. status, ENUM(pending, accepted, rejected, countered), default pending
7. counter_price, DECIMAL(10,2), nullable
8. counter_message, TEXT, nullable
9. created_at, TIMESTAMP, default current timestamp
10. updated_at, TIMESTAMP, auto update

Constraints:

1. UNIQUE KEY unique_provider_request (request_id, provider_id)

Indexes:

1. idx_request
2. idx_provider
3. idx_status

Design decisions:

1. Unique pair prevents duplicate offers by same provider on one request.
2. Counter fields keep negotiation context in the same record.
3. Offer status supports negotiation and decision lifecycle.

## 3.5 reviews

Purpose:

Stores customer feedback after completion.

Fields:

1. id, INT, PK, AUTO_INCREMENT
2. request_id, INT, FK -> service_requests.id, NOT NULL, UNIQUE
3. customer_id, INT, FK -> users.id, NOT NULL
4. provider_id, INT, FK -> users.id, NOT NULL
5. rating, INT, NOT NULL, CHECK 1 to 5
6. comment, TEXT, nullable
7. created_at, TIMESTAMP, default current timestamp

Indexes:

1. idx_provider
2. idx_rating

Design decisions:

1. One review per request is enforced at DB level.
2. Rating check ensures bounded quality score input.
3. Provider and customer linkage supports accountability.

## 3.6 audit_logs

Purpose:

Tracks security and business-critical actions.

Fields:

1. id, INT, PK, AUTO_INCREMENT
2. user_id, INT, FK -> users.id, nullable
3. action, VARCHAR(100), NOT NULL
4. entity_type, VARCHAR(50), nullable
5. entity_id, INT, nullable
6. details, TEXT, nullable
7. ip_address, VARCHAR(45), nullable
8. created_at, TIMESTAMP, default current timestamp

Foreign key behavior:

1. user_id ON DELETE SET NULL

Indexes:

1. idx_user
2. idx_action
3. idx_created

Design decisions:

1. Optional user_id allows logging system-triggered events.
2. action/entity fields provide filterable operational telemetry.

## 3.7 status_history

Purpose:

Tracks request state transitions and timing analytics.

Fields:

1. id, INT, PK, AUTO_INCREMENT
2. request_id, INT, FK -> service_requests.id, NOT NULL
3. old_status, VARCHAR(50), nullable
4. new_status, VARCHAR(50), NOT NULL
5. duration_seconds, INT, nullable
6. changed_by, INT, FK -> users.id, nullable
7. notes, TEXT, nullable
8. changed_at, TIMESTAMP, default current timestamp

Foreign key behavior:

1. request_id ON DELETE CASCADE
2. changed_by ON DELETE SET NULL

Indexes:

1. idx_request
2. idx_new_status

Design decisions:

1. Separate transition log supports analytics and debugging.
2. duration_seconds supports bottleneck reporting.

---

## 4. Relationship Model

1. users 1:N service_requests via customer_id.
2. users 1:N offers via provider_id.
3. users 1:N reviews as customer and as provider.
4. service_categories 1:N service_requests.
5. service_requests 1:N offers.
6. service_requests 1:1 reviews via unique reviews.request_id.
7. service_requests 1:N status_history.
8. users 1:N audit_logs.

---

## 5. Workflow Constraints

Lifecycle constraints:

1. Request starts at Requested.
2. First offer moves request to Negotiating.
3. Accepted offer moves request to Assigned.
4. Provider completion moves request to Completed.
5. Customer review moves request to Reviewed.

Offer constraints:

1. One offer per provider per request.
2. Offer status can be pending, accepted, rejected, or countered.

Review constraints:

1. One review per request.
2. Rating must be between 1 and 5.
3. Review is valid only after completion state in business logic.

Role constraints:

1. customer creates requests and reviews.
2. provider submits offers and marks completion.
3. admin verifies providers and monitors logs.

---

## 6. Procedures and Views

Stored procedures:

1. log_status_change
   - Logs old status, new status, duration, and actor.
2. accept_offer
   - Marks selected offer accepted.
   - Moves request to Assigned.
   - Sets accepted_offer_id.
   - Logs status and audit entries.

Views:

1. v_active_requests
   - Returns requests in Requested or Negotiating.
2. v_provider_ratings
   - Returns aggregated provider rating metrics.

---

## 7. Integrity and Performance Notes

Integrity:

1. Foreign keys prevent orphaned records.
2. Unique constraints enforce marketplace business rules.
3. ON DELETE rules preserve consistent cleanup behavior.

Performance:

1. Status indexes support dashboard filters.
2. Provider and request indexes support offer queries.
3. Audit and timestamp indexes support operations monitoring.

---

## 8. Security-Related Design Decisions

1. password_hash field enforces hashed password storage model.
2. audit_logs supports security event tracking.
3. role and is_verified fields support access control and trust boundaries.
4. Schema design assumes prepared statements and output sanitization in application layer.

---

## 9. Summary

This database design is production-oriented for the defined marketplace scope:

1. Strong relational integrity.
2. Clear workflow state machine.
3. Role-aware data model.
4. Built-in observability through logs and history.
5. Practical performance support through targeted indexes.
