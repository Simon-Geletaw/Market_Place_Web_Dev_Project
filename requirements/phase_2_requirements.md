# Phase 2 Requirements
## Database Design

---

## Functional Requirements

1. Import and apply schema from Documentation/database_schema.sql.
2. Create all required tables: users, service_categories, service_requests, offers, reviews, audit_logs, status_history.
3. Create required stored procedures: log_status_change and accept_offer.
4. Create required views: v_active_requests and v_provider_ratings.
5. Ensure sample categories and users can be inserted successfully.

## Technical Requirements

1. Use MySQL 8.0+ or MariaDB with InnoDB engine.
2. Use utf8mb4 charset and collation where defined.
3. Enforce all primary, foreign, unique, and check constraints from schema.
4. Verify all indexes from schema exist.
5. Use PDO connection settings with exception mode in backend setup.

## Acceptance Criteria

1. Database contains all required tables and columns exactly as schema.
2. users.role supports only customer, provider, admin.
3. service_requests.status supports Requested, Negotiating, Assigned, Completed, Reviewed.
4. offers unique_provider_request constraint blocks duplicate provider offer per request.
5. reviews.request_id unique constraint blocks duplicate review per request.
6. Stored procedures execute without SQL errors.
7. Views return expected records.

## Constraints from Project Docs

1. service_requests.status is the core workflow state machine.
2. reviews are transaction-linked and one per request.
3. audit_logs and status_history are mandatory traceability tables.
4. category deletion must remain restricted when linked requests exist.
5. user deletion behavior must follow cascade/set-null rules in schema.
