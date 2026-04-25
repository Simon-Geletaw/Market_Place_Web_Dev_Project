# Backend Implementation Guide
## Service Marketplace Platform (PHP and MySQL)

**Status:** Legacy (superseded by `Documentation/backend.md`). This file is kept for historical phased implementation notes.

Document Version: 1.0  
Last Updated: April 24, 2026

---

## Source of Truth Used

This guide is derived from the existing project documents and schema:

1. Documentation/database_schema.sql
2. Documentation/database_architecture_design.md
3. Documentation/database_relationships.md
4. Documentation/implementation_roadmap.md
5. Documentation/ui_ux_design_specification.md
6. handy_marketplace_erd.html

Core system workflow:

Requested -> Negotiating -> Assigned -> Completed -> Reviewed

Core roles:

1. customer
2. provider
3. admin

---

## Phase 1: PHP Fundamentals + HTTP Basics

### 1. Phase Objective

Build foundational PHP and HTTP understanding so you can implement backend features safely and consistently.

### 2. Project Context Mapping

1. This phase prepares you for role-based actions across users, service requests, offers, and reviews.
2. Every later operation will be an HTTP request that reads or writes MySQL data.
3. You must understand request and response flow before implementing authentication and state transitions.

### 3. What You Will Build

1. Local backend runtime setup (Apache + PHP + MySQL).
2. Basic endpoint skeletons for health checks and request parsing.
3. Standard response format for success and error payloads.
4. Basic project bootstrap files (entry point, configuration, shared helpers).

### 4. Concepts to Learn (Project-Specific)

1. PHP file execution lifecycle in Apache.
2. Reading GET and POST data in PHP.
3. Returning JSON responses for frontend requests.
4. Structuring reusable includes for config, db access, and response helpers.
5. Basic input validation for request payloads.

### 5. Related Concepts (General Backend Knowledge)

1. HTTP methods (GET, POST, PUT/PATCH, DELETE).
2. Stateless communication model.
3. Request headers, status codes, and payload contracts.
4. API consistency and error envelope patterns.

### 6. Step-by-Step Guidance

1. Install and verify local stack (Apache, PHP, MySQL).
2. Create a backend root structure with config, public, controllers, services, and repositories.
3. Add a single front entry file that routes requests by path and method.
4. Add a simple health endpoint that confirms app and db connectivity.
5. Create a response helper to standardize success and error JSON shape.
6. Add a request parser helper for query parameters and JSON body.
7. Add a validation helper to check required fields and value formats.

### 7. Why This Approach Is Used

1. A stable foundation reduces rework in later phases.
2. Consistent response shape makes frontend integration easier.
3. Early validation and helper functions avoid repeated logic in every endpoint.

### 8. Common Mistakes

1. Mixing HTML rendering and API response logic in one file.
2. Returning different JSON formats for similar errors.
3. Skipping validation because "frontend already validates".
4. Hardcoding credentials directly into endpoint files.

### 9. Validation Checklist

1. The app responds to a health endpoint.
2. Invalid JSON request bodies return clear 400 errors.
3. Missing required fields return validation errors.
4. All endpoint outputs use the same response structure.

### 10. Testing Tasks

Functional tests:

1. Call health endpoint and confirm 200 response.
2. Send a valid sample POST to a test endpoint and confirm parsed values.

Edge cases:

1. Empty JSON body.
2. Unknown HTTP method.

Failure scenarios:

1. Broken DB connection should return controlled server error.
2. Malformed JSON should not crash PHP process.

### 11. Reflection Questions

1. Why do we standardize JSON response format early?
2. What is the difference between client error and server error?
3. Why should configuration be separated from controllers?

---

## Phase 2: Database Design

### 1. Phase Objective

Understand and implement the project schema exactly as defined, including relationships and constraints.

### 2. Project Context Mapping

1. Tables: users, service_categories, service_requests, offers, reviews, audit_logs, status_history.
2. Workflow is driven by service_requests.status.
3. Offer uniqueness and review uniqueness are required constraints.
4. Role behaviors depend on users.role and users.is_verified.

### 3. What You Will Build

1. Database initialization process using database_schema.sql.
2. DB connection layer using PDO.
3. Schema verification checklist (tables, indexes, procedures, views).
4. Seed verification for categories and test users.

### 4. Concepts to Learn (Project-Specific)

1. ENUM usage for role and status.
2. Foreign keys with cascade and restrict behavior.
3. Unique key unique_provider_request on offers.
4. One-review-per-request rule via reviews.request_id unique.
5. Stored procedures: log_status_change and accept_offer.

### 5. Related Concepts (General Backend Knowledge)

1. Normalization basics.
2. Referential integrity.
3. Transaction boundaries.
4. Index design and query performance.

### 6. Step-by-Step Guidance

1. Import schema from Documentation/database_schema.sql.
2. Verify all 7 tables exist with required columns.
3. Verify foreign keys and cascading rules.
4. Verify indexes from schema are present.
5. Verify stored procedures and views are created.
6. Build PDO connection module with strict error mode.
7. Add a db smoke-test script for startup validation.

### 7. Why This Approach Is Used

1. Schema-first implementation keeps backend logic aligned with domain rules.
2. Database constraints prevent invalid data before app logic is applied.
3. Early verification avoids hidden issues in later phases.

### 8. Common Mistakes

1. Skipping foreign key checks in local environment.
2. Changing status text values and breaking state logic.
3. Ignoring unique constraints when designing offer/review endpoints.
4. Not using utf8mb4 consistently.

### 9. Validation Checklist

1. All required tables and columns match schema.
2. offer uniqueness constraint is active.
3. review uniqueness constraint is active.
4. Stored procedures execute successfully with test data.

### 10. Testing Tasks

Functional tests:

1. Insert valid customer, request, offer, and review records in order.
2. Execute accept_offer procedure with valid inputs.

Edge cases:

1. Insert second offer from same provider for same request.
2. Insert second review for same request.

Failure scenarios:

1. Invalid foreign key values should fail.
2. Status transition inconsistencies should be blocked by logic.

### 11. Reflection Questions

1. Why is one-review-per-request modeled in the database, not only in code?
2. What problem does unique_provider_request solve?
3. Why is service_requests.status central to the system?

---

## Phase 3: Backend Structure (Routing, Controllers)

### 1. Phase Objective

Create a maintainable backend architecture that separates routing, business logic, and data access.

### 2. Project Context Mapping

1. Roles require separate authorization paths.
2. Workflow operations need clear service boundaries.
3. Tables map naturally to repositories or data access modules.

### 3. What You Will Build

1. Route registry by resource (auth, requests, offers, reviews, admin).
2. Controllers to handle request and response orchestration.
3. Service layer for business rules and state transitions.
4. Repository layer for database interaction.
5. Middleware for auth, role checks, and validation.

### 4. Concepts to Learn (Project-Specific)

1. Mapping domain entities to service classes.
2. Keeping state transition logic in service layer.
3. Reusing validation across endpoints.
4. Audit logging hooks at action boundaries.

### 5. Related Concepts (General Backend Knowledge)

1. Separation of concerns.
2. Layered architecture.
3. Dependency inversion at a practical level.
4. Thin controllers, rich domain services.

### 6. Step-by-Step Guidance

1. Define route groups: auth, customer, provider, admin.
2. Create one controller per major domain action set.
3. Move all SQL statements to repository classes.
4. Keep controllers responsible for input extraction and response writing only.
5. Place business rules in services, including transition checks.
6. Add middleware chain: parse -> validate -> auth -> role authorize -> controller.
7. Introduce central error handling for predictable error payloads.

### 7. Why This Approach Is Used

1. Clean structure supports growth from beginner code to production quality.
2. Isolating database logic makes testing and refactoring easier.
3. Middleware keeps cross-cutting concerns out of business code.

### 8. Common Mistakes

1. Embedding SQL directly in controller methods.
2. Duplicating role checks in many files.
3. Putting state transition logic in multiple places.
4. Catching exceptions but not returning actionable errors.

### 9. Validation Checklist

1. Every route maps to one controller action.
2. Controllers call services, not raw SQL.
3. Role-protected routes are enforced by middleware.
4. Error responses are centralized and consistent.

### 10. Testing Tasks

Functional tests:

1. Authenticated route works with valid role.
2. Controller-service-repository path works for a sample read endpoint.

Edge cases:

1. Unknown route path.
2. Unsupported method for valid path.

Failure scenarios:

1. Unauthorized role attempts protected action.
2. Service throws exception and error handler returns safe response.

### 11. Reflection Questions

1. Why should controllers stay thin?
2. Where should state transition checks live?
3. Why is centralized error handling important?

---

## Phase 4: Authentication System

### 1. Phase Objective

Implement secure authentication and authorization for customer, provider, and admin roles.

### 2. Project Context Mapping

1. Uses users table with fields: email, password_hash, role, is_verified.
2. Role values must remain customer, provider, admin.
3. Provider trust status is represented by is_verified.

### 3. What You Will Build

1. Registration endpoint with role selection and validation.
2. Login endpoint using password verification.
3. Session-based or token-based auth flow.
4. Logout endpoint and session invalidation.
5. Role-based route protection.

### 4. Concepts to Learn (Project-Specific)

1. password_hash and password_verify usage.
2. Preventing duplicate email registration.
3. Role-aware redirects and authorization checks.
4. Capturing auth actions in audit_logs.

### 5. Related Concepts (General Backend Knowledge)

1. Authentication vs authorization.
2. Session management and secure cookies.
3. Brute-force mitigation basics.
4. Security-first error messaging.

### 6. Step-by-Step Guidance

1. Add registration validator for required fields and role enum values.
2. Hash password before insert into users.
3. Enforce unique email handling with clear validation response.
4. Build login flow: find user by email, verify password, start session.
5. Build auth middleware to attach current user context.
6. Build role middleware for customer/provider/admin endpoints.
7. Add audit log entries for login success/failure and logout.

### 7. Why This Approach Is Used

1. Secure identity handling is the foundation for all workflows.
2. Role isolation ensures users only access permitted actions.
3. Central auth middleware keeps security logic uniform.

### 8. Common Mistakes

1. Storing plain-text passwords.
2. Returning detailed login errors that leak account existence.
3. Trusting role from client payload without server validation.
4. Forgetting logout invalidation.

### 9. Validation Checklist

1. Registration creates valid users with proper role.
2. Login works only with correct credentials.
3. Unauthorized routes are blocked.
4. Role-restricted routes reject wrong role.

### 10. Testing Tasks

Functional tests:

1. Register customer and provider successfully.
2. Login as each role and access matching route set.

Edge cases:

1. Duplicate email registration.
2. Invalid role value in registration.

Failure scenarios:

1. Wrong password attempts.
2. Access admin route with customer session.

### 11. Reflection Questions

1. Why should role checks be server-side only?
2. Why use password_hash instead of custom hashing?
3. What are safe login error message patterns?

---

## Phase 5: Request System

### 1. Phase Objective

Implement customer request creation and request browsing while preserving lifecycle rules.

### 2. Project Context Mapping

1. Uses service_requests and service_categories tables.
2. Initial request status must be Requested.
3. Request fields include customer_id, category_id, description, preferred_date, location.
4. Browse behavior aligns with v_active_requests for Requested and Negotiating items.

### 3. What You Will Build

1. Create service request endpoint (customer only).
2. Customer request listing endpoint.
3. Request detail endpoint.
4. Provider browse active requests endpoint with filters.

### 4. Concepts to Learn (Project-Specific)

1. Ownership checks for customer-created requests.
2. Status default behavior at creation.
3. Category and location validation.
4. Mapping UI filters to SQL query parameters.

### 5. Related Concepts (General Backend Knowledge)

1. Resource modeling.
2. Pagination and sorting.
3. Query parameter validation.
4. Data exposure minimization.

### 6. Step-by-Step Guidance

1. Build request creation validator with required fields.
2. Ensure customer_id is taken from authenticated session, not request body.
3. Insert request with status Requested.
4. Add endpoint to list customer-owned requests by status and date.
5. Add provider endpoint to list active requests (Requested, Negotiating).
6. Add secure request detail endpoint with role-aware visibility rules.
7. Add audit log entries for request creation actions.

### 7. Why This Approach Is Used

1. Ownership control protects customer data.
2. Filtered browsing keeps provider experience relevant.
3. Role-aware visibility prevents data leakage.

### 8. Common Mistakes

1. Allowing provider to create customer request.
2. Allowing client to set arbitrary status at creation.
3. Returning private customer data in provider browse views.
4. Ignoring category existence checks.

### 9. Validation Checklist

1. Request creation works only for customer role.
2. New requests always start as Requested.
3. Provider browse returns only Requested and Negotiating.
4. Request detail visibility follows role rules.

### 10. Testing Tasks

Functional tests:

1. Customer creates valid request and sees it in dashboard list.
2. Provider can browse active requests and open details.

Edge cases:

1. Invalid category_id.
2. Empty description or invalid preferred_date.

Failure scenarios:

1. Provider attempts request creation endpoint.
2. Customer tries to read another customer private request where not allowed.

### 11. Reflection Questions

1. Why should status not be client-controlled at request creation?
2. Why do we prefer authenticated user context over body-provided user_id?
3. What data should provider not see on request browse cards?

---

## Phase 6: Offer System

### 1. Phase Objective

Implement provider offer submission and negotiation mechanics with strict uniqueness and status rules.

### 2. Project Context Mapping

1. Uses offers table and service_requests status updates.
2. One provider can submit one offer per request (unique_provider_request).
3. Offer statuses: pending, accepted, rejected, countered.
4. First offer on a Requested request should move request to Negotiating.

### 3. What You Will Build

1. Submit offer endpoint (provider only).
2. Customer endpoint to view offers for owned request.
3. Counter-offer endpoint for customer.
4. Offer list endpoint for provider (my offers).

### 4. Concepts to Learn (Project-Specific)

1. Enforcing provider/request uniqueness.
2. Negotiation fields counter_price and counter_message.
3. Request state synchronization with offer events.
4. Ownership checks on both provider and customer sides.

### 5. Related Concepts (General Backend Knowledge)

1. Concurrency and race condition basics.
2. Idempotency considerations.
3. Transaction use in multi-step updates.
4. Domain-level validation beyond schema checks.

### 6. Step-by-Step Guidance

1. Validate provider role and request eligibility before insert.
2. Prevent duplicate provider offer by request through pre-check and DB constraint handling.
3. Insert offer with pending status.
4. If request status is Requested, update it to Negotiating and log status change.
5. Add customer-owned endpoint to list all offers for a request.
6. Add counter-offer logic that updates offer status to countered and stores counter values.
7. Add audit logs for submit and counter actions.

### 7. Why This Approach Is Used

1. Negotiation must stay consistent for customer decision-making.
2. DB uniqueness ensures rule enforcement even under concurrent submissions.
3. Logged transitions support traceability and debugging.

### 8. Common Mistakes

1. Allowing offers on requests in invalid states.
2. Not handling unique constraint violation gracefully.
3. Letting providers view offers from unrelated requests.
4. Failing to update request status to Negotiating on first offer.

### 9. Validation Checklist

1. Provider can submit only one offer per request.
2. Request status transitions Requested -> Negotiating when first offer arrives.
3. Customer can view offers only for owned requests.
4. Counter fields are recorded correctly when countering.

### 10. Testing Tasks

Functional tests:

1. Provider submits first offer and request becomes Negotiating.
2. Customer reads offers and counters one.

Edge cases:

1. Provider submits offer with invalid price.
2. Counter without counter price.

Failure scenarios:

1. Duplicate offer submission for same provider/request.
2. Offer submission for non-existent request.

### 11. Reflection Questions

1. Why should uniqueness be enforced in DB and service logic?
2. Why does first offer trigger Negotiating?
3. What should happen if two providers submit offers nearly simultaneously?

---

## Phase 7: Assignment and Completion

### 1. Phase Objective

Implement acceptance of one winning offer and provider completion flow with proof upload and state integrity.

### 2. Project Context Mapping

1. Uses accept_offer stored procedure and service_requests.accepted_offer_id.
2. Required transition: Negotiating -> Assigned.
3. Completion transition: Assigned -> Completed.
4. completion_photo is stored on service_requests.
5. status_history and audit_logs must track critical changes.

### 3. What You Will Build

1. Accept offer endpoint (customer only, owner only).
2. Assigned job listing for provider.
3. Mark complete endpoint (provider only, assigned job only).
4. Completion photo validation and storage workflow.

### 4. Concepts to Learn (Project-Specific)

1. Atomic offer acceptance using procedure/transaction.
2. Ownership and assignment checks.
3. File upload validation and secure storage handling.
4. State transition validation enforcement.

### 5. Related Concepts (General Backend Knowledge)

1. Atomic operations and consistency.
2. Secure file handling in web backends.
3. Input validation for binary uploads.
4. Event auditability for critical actions.

### 6. Step-by-Step Guidance

1. Build endpoint to accept one offer by customer who owns request.
2. Use accept_offer procedure or equivalent transaction path.
3. Ensure accepted_offer_id is set and status becomes Assigned.
4. Build provider endpoint to list only assigned jobs.
5. Build completion endpoint with file checks (type and size).
6. Store file path in completion_photo and update status to Completed.
7. Call status logging and write audit entries for both assignment and completion.

### 7. Why This Approach Is Used

1. Assignment must not allow multiple accepted offers.
2. Completion requires evidence for trust and dispute handling.
3. Procedure-driven updates reduce inconsistency risk.

### 8. Common Mistakes

1. Accepting offers without checking request ownership.
2. Allowing completion when status is not Assigned.
3. Trusting uploaded filename or MIME type without validation.
4. Forgetting to log transitions.

### 9. Validation Checklist

1. One accepted offer per request is enforced.
2. Request transitions to Assigned only from valid negotiation state.
3. Completion allowed only for assigned provider.
4. completion_photo is stored for completed requests.

### 10. Testing Tasks

Functional tests:

1. Customer accepts one offer and sees request as Assigned.
2. Assigned provider marks complete with valid photo and status changes to Completed.

Edge cases:

1. Upload max size boundary.
2. Unsupported file extension.

Failure scenarios:

1. Different provider attempts completion.
2. Customer attempts to accept offer for someone else request.

### 11. Reflection Questions

1. Why is accept_offer better as one atomic backend operation?
2. Why must completion validate assignment ownership?
3. What is the risk of weak file upload validation?

---

## Phase 8: Review System

### 1. Phase Objective

Implement final review submission and provider reputation updates while enforcing completion dependency.

### 2. Project Context Mapping

1. Uses reviews table with request_id unique and rating 1-5 check.
2. Review is allowed only after request reaches Completed.
3. Final transition is Completed -> Reviewed.
4. Provider rating fields in users must be updated.

### 3. What You Will Build

1. Submit review endpoint (customer only, request owner only).
2. Review retrieval endpoints for customer and provider views.
3. Provider rating aggregation update logic.
4. Request status finalization to Reviewed.

### 4. Concepts to Learn (Project-Specific)

1. Enforcing one review per request.
2. Rating validation boundaries.
3. Final state transition handling.
4. Maintaining derived fields (rating_average and total_reviews).

### 5. Related Concepts (General Backend Knowledge)

1. Aggregate consistency.
2. Eventual consistency vs immediate recomputation.
3. Immutable transaction outcomes.
4. Data quality in reputation systems.

### 6. Step-by-Step Guidance

1. Validate request ownership and current status Completed.
2. Validate rating range 1 to 5.
3. Create review record if no existing review for request.
4. Update provider aggregate metrics from reviews data.
5. Update request status to Reviewed.
6. Log status transition and audit event.
7. Provide endpoint for provider reviews list and averages.

### 7. Why This Approach Is Used

1. Review finalizes service lifecycle and trust loop.
2. Unique review constraint prevents duplicate feedback manipulation.
3. Immediate aggregate update keeps dashboards accurate.

### 8. Common Mistakes

1. Allowing review before completion.
2. Not checking duplicate review by request_id.
3. Updating wrong provider aggregate.
4. Forgetting final status transition to Reviewed.

### 9. Validation Checklist

1. Review accepted only when request is Completed.
2. One review per request is enforced.
3. Request moves to Reviewed after successful review.
4. Provider rating and review count update correctly.

### 10. Testing Tasks

Functional tests:

1. Customer submits valid review and request reaches Reviewed.
2. Provider dashboard reflects updated averages.

Edge cases:

1. Rating equals 1 and rating equals 5.
2. Empty optional comment.

Failure scenarios:

1. Duplicate review attempt.
2. Review attempt for non-owned request.

### 11. Reflection Questions

1. Why is review tied to request_id uniqueness?
2. Why should Reviewed be treated as terminal in lifecycle?
3. What trade-off exists between real-time aggregate update and batch recompute?

---

## Phase 9: Production Hardening

### 1. Phase Objective

Prepare the backend for reliable, secure, and maintainable production deployment.

### 2. Project Context Mapping

1. Security practices are required by database and architecture docs.
2. audit_logs and status_history support observability and investigations.
3. Index usage is required for dashboard and marketplace performance.
4. Role boundaries and state machine integrity must remain enforced.

### 3. What You Will Build

1. Hardened validation and error handling across all endpoints.
2. Security controls: input sanitization, CSRF/session protections, upload restrictions.
3. Logging and monitoring strategy around critical actions.
4. Performance tuning and query review against indexes.
5. Backup and migration readiness checklist.

### 4. Concepts to Learn (Project-Specific)

1. Mapping critical actions to audit_logs entries.
2. Detecting workflow bottlenecks with status_history.
3. Query plan checks for active dashboard endpoints.
4. Defining operational runbooks for failure recovery.

### 5. Related Concepts (General Backend Knowledge)

1. Defense in depth.
2. Least privilege database access.
3. Observability and incident response basics.
4. Non-functional requirements and SLIs.

### 6. Step-by-Step Guidance

1. Add centralized input sanitization and output encoding policy.
2. Review every endpoint for authorization and ownership checks.
3. Add consistent structured logs for critical operations.
4. Review and optimize slow queries using existing indexes.
5. Add operational scripts for backup and restore drills.
6. Add final state machine validation tests in CI or local test suite.
7. Prepare deployment checklist for configuration, secrets, and environment parity.

### 7. Why This Approach Is Used

1. Production quality is mostly about reliability and safety, not only features.
2. Observability shortens incident resolution time.
3. Security and performance controls prevent common backend failures.

### 8. Common Mistakes

1. Deploying with debug settings enabled.
2. Logging sensitive data such as password-related values.
3. Ignoring failed authorization attempts in monitoring.
4. Skipping backup restore testing.

### 9. Validation Checklist

1. All critical endpoints enforce auth and role checks.
2. Status transitions are valid end-to-end under test.
3. Security controls cover SQL injection and XSS vectors.
4. Query performance is acceptable on core listing endpoints.
5. Backup and restore process is documented and verified.

### 10. Testing Tasks

Functional tests:

1. Full lifecycle test from request creation to review completion.
2. Admin verification and audit-log review flow.

Edge cases:

1. Large request/offer datasets for list endpoints.
2. High-volume offer submissions across many requests.

Failure scenarios:

1. Database connection interruption during transaction.
2. Invalid state transition attempts from multiple roles.
3. Malicious input payloads in description and comment fields.

### 11. Reflection Questions

1. What does production-ready mean beyond "features work"?
2. Which logs are most useful during incident response in this system?
3. Why should state validation exist at both business logic and DB constraint levels?

---

## Instructor Workflow Recommendation

For each phase in your implementation:

1. Read the phase objective and context mapping first.
2. Implement only the listed features for that phase.
3. Run validation checklist and testing tasks.
4. Answer reflection questions before moving forward.
5. Share your code for review and correction.

I will then evaluate your implementation phase by phase, identify issues, and guide your next iteration without skipping fundamentals.
