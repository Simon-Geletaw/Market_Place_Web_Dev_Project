# Phase 6 Requirements
## Offer System

---

## Functional Requirements

1. Provider can submit offer on eligible request.
2. Customer can view offers for own request.
3. Customer can submit counter offer values.
4. Provider can view own offer history.
5. System updates request negotiation state when appropriate.

## Technical Requirements

1. Enforce one offer per provider per request using unique constraint and service checks.
2. Offer status values must be pending, accepted, rejected, countered.
3. First valid offer on Requested request should move request to Negotiating.
4. Counter updates must store counter_price and counter_message.
5. Offer operations must log auditable actions.

## Acceptance Criteria

1. Provider offer insert succeeds with valid request and payload.
2. Duplicate provider offer on same request is rejected.
3. Customer cannot read offers for requests not owned.
4. Counter action updates offer status and counter fields.
5. Request status updates to Negotiating when first offer is created.

## Constraints from Project Docs

1. offers table and unique_provider_request rule are mandatory.
2. status flow must remain aligned with service_requests lifecycle.
3. Provider role only can submit offers.
4. Negotiation must preserve traceability through logs/history.
