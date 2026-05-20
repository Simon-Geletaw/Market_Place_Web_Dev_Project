# Requirements Document

## Introduction

This document defines the requirements for the Job Lifecycle Management feature of the Handy Marketplace platform. The feature covers the complete end-to-end lifecycle of a service request: from a provider browsing and applying for open jobs, through customer offer management and negotiation, to job completion and post-job review. The system must enforce a strict status state machine across all transitions and provide appropriate visibility to each user role at every stage.

---

## Glossary

- **Customer**: A registered user with role `Customer` who creates service requests and manages incoming offers.
- **Provider**: A registered user with role `Provider` who browses open requests and submits offers.
- **Service_Request**: A record in `SERVICE_REQUESTS` representing a job posted by a Customer. Identified by `REQUEST_ID`.
- **Offer**: A record in `OFFERS` representing a Provider's bid on a Service_Request. Identified by `OFFER_ID`.
- **Review**: A record in `REVIEWS` representing a Customer's post-completion rating of a Provider. Identified by `REVIEW_ID`.
- **Status_History**: A record in `STATUS_HISTORY` capturing every status transition of a Service_Request.
- **Marketplace**: The public-facing browse surface where Providers discover open Service_Requests.
- **Notification**: An in-app message delivered to a user when a relevant lifecycle event occurs.
- **State_Machine**: The enforced set of valid status transitions for a Service_Request: `Requested → Negotiating → Assigned → Completed → Reviewed`.
- **Accepted_Offer**: The single Offer linked to a Service_Request via `ACCEPTED_OFFER_ID` once a Customer accepts a bid.

---

## Requirements

### Requirement 1: Provider Browses Open Requests

**User Story:** As a Provider, I want to browse all open service requests on the marketplace, so that I can identify jobs that match my skills and location.

#### Acceptance Criteria

1. THE Marketplace SHALL display all Service_Requests with status `Requested` or `Negotiating` to authenticated Providers.
2. WHEN a Provider applies a category filter, THE Marketplace SHALL return only Service_Requests matching the specified `CATEGORY_ID`.
3. WHEN a Provider applies a location filter, THE Marketplace SHALL return only Service_Requests matching the specified `LOCATION` value.
4. THE Marketplace SHALL include the following fields for each listed Service_Request: title or description excerpt, category name, location, budget (if set), preferred date (if set), current offer count, and creation timestamp.
5. THE Marketplace SHALL order results by creation timestamp in descending order by default.
6. IF a Provider is not authenticated, THEN THE Marketplace SHALL return an HTTP 401 response.
7. WHEN a Provider has already submitted an Offer for a Service_Request, THE Marketplace SHALL still display that Service_Request in browse results.

---

### Requirement 2: Provider Submits an Offer

**User Story:** As a Provider, I want to submit an offer on an open service request, so that I can be considered for the job.

#### Acceptance Criteria

1. WHEN a Provider submits an Offer with a valid `price` (greater than zero) and `message` on a Service_Request with status `Requested` or `Negotiating`, THE Offer_System SHALL create the Offer with status `Pending`.
2. WHEN the first Offer is submitted on a Service_Request with status `Requested`, THE Offer_System SHALL transition the Service_Request status from `Requested` to `Negotiating` and record the transition in Status_History.
3. IF a Provider submits an Offer on a Service_Request that already has an Offer from that same Provider, THEN THE Offer_System SHALL return an HTTP 409 response with the message "You have already submitted an offer for this request."
4. IF a Provider submits an Offer on a Service_Request with status other than `Requested` or `Negotiating`, THEN THE Offer_System SHALL return an HTTP 422 response with the message "This request is no longer open for offers."
5. IF a Provider submits an Offer with a `price` of zero or less, THEN THE Offer_System SHALL return an HTTP 422 response.
6. THE Offer_System SHALL enforce a unique constraint so that at most one Offer per Provider per Service_Request exists.
7. WHEN an Offer is successfully created, THE Offer_System SHALL record an audit log entry with action `offer_submitted`.

---

### Requirement 3: Customer Views Offers on a Request

**User Story:** As a Customer, I want to view all offers submitted on my service request, so that I can compare providers and make an informed decision.

#### Acceptance Criteria

1. WHEN a Customer requests the offer list for a Service_Request they own, THE Offer_System SHALL return all Offers for that Service_Request including provider name, provider rating, verification status, price, message, counter price (if any), counter message (if any), offer status, and submission timestamp.
2. IF a Customer requests the offer list for a Service_Request they do not own, THEN THE Offer_System SHALL return an HTTP 403 response.
3. THE Offer_System SHALL return offers ordered by submission timestamp in ascending order.

---

### Requirement 4: Customer Accepts an Offer

**User Story:** As a Customer, I want to accept a provider's offer, so that the job is assigned and scheduled.

#### Acceptance Criteria

1. WHEN a Customer accepts an Offer with status `Pending` or `Countered` on a Service_Request with status `Negotiating`, THE Offer_System SHALL set the Offer status to `Accepted`, set `ACCEPTED_OFFER_ID` on the Service_Request, update `LOCATION` and `PREFERRED_DATE` from the acceptance payload, and transition the Service_Request status to `Assigned`.
2. WHEN a Customer accepts an Offer, THE Offer_System SHALL set all other `Pending` or `Countered` Offers on the same Service_Request to status `Rejected`.
3. WHEN a Customer accepts an Offer, THE Offer_System SHALL record the `Negotiating → Assigned` transition in Status_History with the Customer as the actor.
4. WHEN a Customer accepts an Offer, THE Offer_System SHALL send a Notification to the assigned Provider with the scheduled date and location.
5. IF a Customer attempts to accept an Offer on a Service_Request with status other than `Negotiating`, THEN THE Offer_System SHALL return an HTTP 422 response.
6. IF a Customer attempts to accept an Offer with status other than `Pending` or `Countered`, THEN THE Offer_System SHALL return an HTTP 422 response with the message "This offer is no longer available to accept."
7. IF a Customer attempts to accept an Offer on a Service_Request they do not own, THEN THE Offer_System SHALL return an HTTP 403 response.
8. WHEN a Customer accepts an Offer, THE Offer_System SHALL record an audit log entry with action `offer_accepted`.

---

### Requirement 5: Customer Rejects an Offer

**User Story:** As a Customer, I want to reject a provider's offer, so that I can decline unsuitable bids without closing the request.

#### Acceptance Criteria

1. WHEN a Customer rejects an Offer with status `Pending` or `Countered`, THE Offer_System SHALL set the Offer status to `Rejected`.
2. WHEN a Customer rejects an Offer, THE Offer_System SHALL send a Notification to the Provider informing them their offer was declined.
3. IF a Customer attempts to reject an Offer with status other than `Pending` or `Countered`, THEN THE Offer_System SHALL return an HTTP 422 response with the message "This offer cannot be rejected."
4. IF a Customer attempts to reject an Offer on a Service_Request they do not own, THEN THE Offer_System SHALL return an HTTP 403 response.
5. WHEN a Customer rejects an Offer, THE Offer_System SHALL record an audit log entry with action `offer_rejected`.
6. WHEN a Customer rejects an Offer, THE Offer_System SHALL leave the Service_Request status unchanged.

---

### Requirement 6: Customer Counters an Offer

**User Story:** As a Customer, I want to send a counter-offer to a provider, so that I can negotiate the price before committing.

#### Acceptance Criteria

1. WHEN a Customer counters an Offer with status `Pending` by providing a `counter_price` greater than zero and an optional `counter_message`, THE Offer_System SHALL set the Offer status to `Countered` and store the counter price and message.
2. WHEN a Customer counters an Offer, THE Offer_System SHALL send a Notification to the Provider with the counter price amount.
3. IF a Customer attempts to counter an Offer with status other than `Pending`, THEN THE Offer_System SHALL return an HTTP 422 response with the message "Only Pending offers can be countered."
4. IF a Customer provides a `counter_price` of zero or less, THEN THE Offer_System SHALL return an HTTP 422 response.
5. IF a Customer attempts to counter an Offer on a Service_Request they do not own, THEN THE Offer_System SHALL return an HTTP 403 response.
6. WHEN a Customer counters an Offer, THE Offer_System SHALL record an audit log entry with action `offer_countered`.

---

### Requirement 7: Provider Marks a Job as Completed

**User Story:** As a Provider, I want to mark an assigned job as completed, so that the customer can review my work.

#### Acceptance Criteria

1. WHEN the assigned Provider marks a Service_Request with status `Assigned` as completed, THE Request_System SHALL transition the Service_Request status to `Completed` and optionally store a `completion_photo` path.
2. WHEN a Provider marks a job as completed, THE Request_System SHALL record the `Assigned → Completed` transition in Status_History with the Provider as the actor.
3. IF a Provider attempts to mark a Service_Request as completed that does not have status `Assigned`, THEN THE Request_System SHALL return an HTTP 422 response with the message "Only Assigned requests can be marked complete."
4. IF a Provider attempts to mark a Service_Request as completed for which they are not the assigned Provider (i.e., their Offer is not the Accepted_Offer), THEN THE Request_System SHALL return an HTTP 403 response with the message "You are not the assigned provider for this request."
5. WHEN a Provider marks a job as completed, THE Request_System SHALL record an audit log entry with action `request_completed`.

---

### Requirement 8: Customer Leaves a Review

**User Story:** As a Customer, I want to leave a rating and comment after a job is completed, so that I can share my experience and help other customers choose providers.

#### Acceptance Criteria

1. WHEN a Customer submits a Review with a `rating` between 1 and 5 (inclusive) and an optional `comment` for a Service_Request with status `Completed`, THE Review_System SHALL create the Review record and transition the Service_Request status to `Reviewed`.
2. WHEN a Review is submitted, THE Review_System SHALL record the `Completed → Reviewed` transition in Status_History with the Customer as the actor.
3. WHEN a Review is submitted, THE Review_System SHALL recalculate and update the Provider's `RATING_AVERAGE` and `TOTAL_REVIEWS` on the `USERS` table.
4. IF a Customer attempts to submit a Review for a Service_Request with status other than `Completed`, THEN THE Review_System SHALL return an HTTP 422 response with the message "You can only review a completed job."
5. IF a Customer attempts to submit a second Review for the same Service_Request, THEN THE Review_System SHALL return an HTTP 409 response with the message "You have already reviewed this job."
6. IF a Customer submits a Review with a `rating` outside the range 1–5, THEN THE Review_System SHALL return an HTTP 422 response with the message "Rating must be between 1 and 5."
7. IF a Customer attempts to submit a Review for a Service_Request they do not own, THEN THE Review_System SHALL return an HTTP 403 response with the message "Access denied."
8. WHEN a Review is submitted, THE Review_System SHALL record an audit log entry with action `review_submitted`.
9. THE Review_System SHALL enforce that at most one Review exists per Service_Request (unique constraint on `REQUEST_ID` in `REVIEWS`).

---

### Requirement 9: Status State Machine Integrity

**User Story:** As a platform operator, I want all job status transitions to follow the defined state machine, so that the system remains consistent and auditable.

#### Acceptance Criteria

1. THE State_Machine SHALL only permit the following transitions: `Requested → Negotiating`, `Negotiating → Assigned`, `Assigned → Completed`, `Completed → Reviewed`.
2. WHEN any status transition occurs, THE State_Machine SHALL record an entry in Status_History with the old status, new status, actor user ID, and a descriptive note.
3. IF a status transition is attempted that is not permitted by the State_Machine, THEN THE State_Machine SHALL reject the operation and return an appropriate HTTP error response.
4. THE State_Machine SHALL ensure that a Service_Request in status `Assigned`, `Completed`, or `Reviewed` cannot receive new Offers.
5. THE State_Machine SHALL ensure that a Service_Request can have at most one Accepted_Offer at any time.

---

### Requirement 10: Provider Views Assigned and Completed Jobs

**User Story:** As a Provider, I want to view my assigned and completed jobs, so that I can manage my workload and track my history.

#### Acceptance Criteria

1. WHEN a Provider requests their assigned jobs, THE Request_System SHALL return all Service_Requests with status `Assigned` where the Provider's Offer is the Accepted_Offer, including customer name, customer phone, category name, location, preferred date, and accepted price.
2. WHEN a Provider requests their completed jobs, THE Request_System SHALL return all Service_Requests with status `Completed` where the Provider's Offer is the Accepted_Offer.
3. THE Request_System SHALL order assigned and completed job results by last updated timestamp in descending order.
4. IF a Provider requests jobs for which they are not the assigned Provider, THE Request_System SHALL not include those Service_Requests in the response.

---

### Requirement 11: Customer Views Their Requests

**User Story:** As a Customer, I want to view all my service requests with their current status, so that I can track the progress of my jobs.

#### Acceptance Criteria

1. WHEN a Customer requests their service request list, THE Request_System SHALL return all Service_Requests owned by that Customer including category name, status, offer count, budget, location, preferred date, and creation timestamp.
2. WHEN a Customer applies a status filter, THE Request_System SHALL return only Service_Requests matching the specified status value.
3. WHEN a Customer applies a sort parameter, THE Request_System SHALL order results by creation timestamp in the specified direction (`asc` or `desc`), defaulting to `desc`.
4. IF a Customer requests the detail of a Service_Request they do not own, THEN THE Request_System SHALL return an HTTP 403 response.

---

### Requirement 12: Notification Delivery for Lifecycle Events

**User Story:** As a user, I want to receive in-app notifications when important lifecycle events occur on my jobs or offers, so that I can respond promptly.

#### Acceptance Criteria

1. WHEN a Customer accepts an Offer, THE Notification_System SHALL deliver a Notification to the assigned Provider containing the scheduled date and location.
2. WHEN a Customer rejects an Offer, THE Notification_System SHALL deliver a Notification to the affected Provider informing them the offer was declined.
3. WHEN a Customer counters an Offer, THE Notification_System SHALL deliver a Notification to the affected Provider containing the counter price.
4. THE Notification_System SHALL store each Notification with a `USER_ID`, `TYPE`, `MESSAGE`, `LINK`, `ENTITY_TYPE`, `ENTITY_ID`, and `IS_READ` flag defaulting to `false`.
5. IF the Notification_System encounters an error delivering a Notification, THEN THE Notification_System SHALL log the error and SHALL NOT prevent the primary lifecycle operation from completing.
