# Phase 1 Requirements
## PHP Fundamentals + HTTP Basics

---

## Functional Requirements

1. The backend must run on local Apache with PHP configured.
2. The system must expose a health endpoint for runtime verification.
3. The backend must parse request inputs from query and JSON body.
4. The backend must return standardized JSON responses for success and error.
5. The backend must validate required input fields before processing.

## Technical Requirements

1. Use PHP 8.1+ and MySQL-compatible runtime.
2. Organize project with separate config, routing, and controller entry files.
3. Build reusable request parsing and response helper utilities.
4. Use consistent HTTP status codes for 2xx, 4xx, and 5xx outcomes.
5. Keep business logic out of bootstrap and routing files.

## Acceptance Criteria

1. Health endpoint returns success when app is running.
2. Invalid JSON input returns controlled 400 error response.
3. Missing required fields return validation message response.
4. Unknown routes return standardized not found error.
5. All endpoints use one response shape.

## Constraints from Project Docs

1. Backend stack must remain PHP and MySQL based.
2. Future phase support must preserve roles customer, provider, admin.
3. Future phase support must preserve request status flow Requested to Reviewed.
4. Foundation design must support route protection for role-based actions.
