# Phase 5 Requirements
## Request System

---

## Functional Requirements

1. Customer can create a service request.
2. Customer can list own requests.
3. Customer can view request detail.
4. Provider can browse active requests.
5. Request filters can use category, location, and status context.

## Technical Requirements

1. New request must be inserted with status Requested.
2. service_requests.customer_id must come from authenticated customer identity.
3. Validate category_id exists in service_categories.
4. Validate required fields description and location.
5. Use indexed fields for listing and filtering performance.

## Acceptance Criteria

1. Customer-only route enforcement for request creation.
2. New requests appear in customer dashboard list.
3. Provider browse returns Requested and Negotiating states only.
4. Invalid category or payload returns validation error.
5. Request detail endpoint returns role-appropriate data.

## Constraints from Project Docs

1. request lifecycle must begin at Requested.
2. category relationship must use service_categories foreign key.
3. role rules must restrict creation to customers.
4. request data model must preserve preferred_date, location, and status fields.
