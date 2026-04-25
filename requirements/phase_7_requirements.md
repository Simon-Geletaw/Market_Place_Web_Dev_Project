# Phase 7 Requirements
## Assignment and Completion

---

## Functional Requirements

1. Customer can accept one offer for own request.
2. Accepted offer links to service_requests.accepted_offer_id.
3. Request state changes to Assigned after valid acceptance.
4. Assigned provider can mark job as complete.
5. Completion flow stores proof photo path in completion_photo.

## Technical Requirements

1. Offer acceptance must be atomic using accept_offer procedure or transaction equivalent.
2. Completion must validate provider assignment ownership.
3. Completion must validate upload constraints (type and size policy).
4. Transition logs must be recorded in status_history.
5. Critical assignment and completion events must be written to audit_logs.

## Acceptance Criteria

1. Only request owner customer can accept an offer.
2. Request state moves Negotiating to Assigned after acceptance.
3. accepted_offer_id is populated with selected offer.
4. Only assigned provider can move request Assigned to Completed.
5. completion_photo is stored after successful completion submission.

## Constraints from Project Docs

1. State machine must follow Requested -> Negotiating -> Assigned -> Completed -> Reviewed.
2. accept_offer procedure behavior must remain consistent with schema.
3. completion_photo field in service_requests must be used for completion evidence.
4. status_history and audit_logs are required for transition traceability.
