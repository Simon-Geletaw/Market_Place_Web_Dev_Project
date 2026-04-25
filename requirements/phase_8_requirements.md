# Phase 8 Requirements
## Review System

---

## Functional Requirements

1. Customer can submit review for completed request.
2. Review includes required rating and optional comment.
3. Review links request, customer, and provider.
4. Request status moves to Reviewed after valid review.
5. Provider rating summary is updated for dashboard usage.

## Technical Requirements

1. Enforce one review per request via reviews.request_id unique.
2. Enforce rating range 1 to 5.
3. Validate review is allowed only when request is Completed.
4. Recalculate or update provider rating_average and total_reviews.
5. Log review and final status transition in audit/history tables.

## Acceptance Criteria

1. Valid review submission succeeds once per request.
2. Duplicate review for same request is rejected.
3. Review before completion is rejected.
4. Request status moves Completed to Reviewed on successful review.
5. Provider rating metrics reflect new review.

## Constraints from Project Docs

1. reviews table structure and constraints must remain unchanged.
2. Final lifecycle state is Reviewed.
3. review flow must be tied to transaction lifecycle.
4. Role rules require customer to submit review and provider to receive review.
