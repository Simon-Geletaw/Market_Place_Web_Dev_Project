# Phase 3 Requirements
## Backend Structure (Routing and Controllers)

---

## Functional Requirements

1. Define route groups for auth, requests, offers, reviews, and admin actions.
2. Implement controllers that handle request parsing and response output.
3. Implement service layer for business rules and state transitions.
4. Implement repository layer for all SQL access.
5. Add middleware for authentication, role checks, and validation.

## Technical Requirements

1. Keep SQL queries outside controllers.
2. Keep state transition checks in services.
3. Keep reusable validation rules in validator modules.
4. Use consistent response and error handling across controllers.
5. Ensure route-to-controller mapping is explicit and maintainable.

## Acceptance Criteria

1. Every defined route maps to one controller action.
2. Controllers call services and do not execute raw SQL.
3. Services call repositories for persistence operations.
4. Role-protected routes reject unauthorized roles.
5. Invalid input is rejected before service execution.

## Constraints from Project Docs

1. Architecture must support role separation for customer, provider, and admin.
2. Architecture must support status transitions Requested to Reviewed.
3. Architecture must support audit logging and status history recording.
4. Architecture must support category-based request browsing and provider workflows.
