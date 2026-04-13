# Secure Local Service Marketplace - Database Relationships

## Project Overview

**Market_Place_Web_Dev_Project** (Handy Model) is a **Secure Local Service Marketplace** platform that connects customers needing local services with verified providers who offer those services. The platform follows a state machine workflow: customers post service requests, providers submit offers, customers accept offers, providers complete work, and customers leave reviews. It's built for the Addis Ababa local service community.

---

## Core Entities

| Entity | Purpose | Key Fields |
|--------|---------|-----------|
| **users** | All system users with role-based access (customers, providers, admins) | id, email, role, name, phone, location, rating_average, is_verified |
| **service_categories** | Types of services (electrician, plumber, carpenter, painter, cleaner) | id, name, description, icon, is_active |
| **service_requests** | Customer job postings that drive the workflow state machine | id, customer_id, category_id, description, status, location, accepted_offer_id |
| **offers** | Provider price quotes with counter-offer negotiation support | id, request_id, provider_id, price, message, status, counter_price |
| **reviews** | Post-completion ratings and feedback from customers to providers | id, request_id, customer_id, provider_id, rating, comment |
| **audit_logs** | Forensic trail of all critical actions for security & compliance | id, user_id, action, entity_type, entity_id, details, ip_address |
| **status_history** | Request state transition analytics for workflow tracking | id, request_id, old_status, new_status, duration_seconds, changed_by |

---

## Entity Relationship Diagram

```
┌─────────────┐
│   users     │
│  (1) ◄──────┼────────────────────────┐
└────┬────────┘                        │
     │                                  │
     ├──(N)──► service_requests        │ (N) – Role: provider
     │         (customer_id)           │
     │                                  │
     ├──(N)──► offers                   │
     │         (provider_id) ◄──────────┘
     │
     ├──(N)──► reviews
     │         (customer_id as reviewer)
     │
     ├──(N)──► reviews
     │         (provider_id as reviewed)
     │
     └──(N)──► audit_logs
              (user_id)

service_categories (1)
     │
     └──(N)──► service_requests
              (category_id)

service_requests (1)
     ├──(N)──► offers
     │         (request_id)
     │
     ├──(1)──► reviews
     │         (request_id, UNIQUE constraint)
     │
     ├──(1)──► offers [selected]
     │         (accepted_offer_id)
     │
     └──(N)──► status_history
              (request_id)

status_history (N)
     └──(1)──► users
              (changed_by)
```

---

## Detailed Relationships

### 1. **Users ↔ Service Requests** (One-to-Many)
- **Foreign Key**: `service_requests.customer_id` → `users.id`
- **Cascade**: ON DELETE CASCADE
- **Description**: One customer can post multiple service requests
- **Example**: Customer "Ahmed" (user_id=1) posts electrician and plumbing requests

### 2. **Service Categories ↔ Service Requests** (One-to-Many)
- **Foreign Key**: `service_requests.category_id` → `service_categories.id`
- **Cascade**: ON DELETE RESTRICT (prevents deleting categories with active requests)
- **Description**: Each service request belongs to one category; one category has many requests
- **Example**: The "Electrician" category has 50 active requests

### 3. **Service Requests ↔ Offers** (One-to-Many)
- **Foreign Key**: `offers.request_id` → `service_requests.id`
- **Cascade**: ON DELETE CASCADE
- **Constraint**: `UNIQUE KEY unique_provider_request (request_id, provider_id)` — prevents duplicate offers
- **Description**: One service request receives multiple offers from different providers
- **Example**: A single electrician request receives offers from 3 different providers

### 4. **Users (Providers) ↔ Offers** (One-to-Many)
- **Foreign Key**: `offers.provider_id` → `users.id` (where role='provider')
- **Cascade**: ON DELETE CASCADE
- **Description**: One provider can submit multiple offers for different requests
- **Example**: Provider "Abebe" (user_id=5, role='provider') has submitted 12 offers

### 5. **Service Requests ↔ Selected Offer** (Many-to-One Indirect)
- **Foreign Key**: `service_requests.accepted_offer_id` → `offers.id`
- **Null Allowed**: YES (before customer accepts an offer)
- **Description**: When a customer accepts an offer, this field links to the chosen offer
- **Cascade**: ON DELETE SET NULL (if offer deleted, request can be reopened)
- **Example**: Request #23 has `accepted_offer_id = 107` (the chosen provider's offer)

### 6. **Service Requests ↔ Reviews** (One-to-One)
- **Foreign Key**: `reviews.request_id` → `service_requests.id` (UNIQUE)
- **Cascade**: ON DELETE CASCADE
- **Description**: Each completed service request gets exactly one review
- **Business Rule**: Review can only be created when `service_requests.status = 'Completed'`
- **Example**: After work completes, customer submits 1 review per job

### 7. **Users (Customers) ↔ Reviews** (One-to-Many)
- **Foreign Key**: `reviews.customer_id` → `users.id`
- **Cascade**: ON DELETE CASCADE
- **Description**: One customer can write multiple reviews for different jobs
- **Example**: Customer "Almaz" wrote reviews for 8 completed requests

### 8. **Users (Providers) ↔ Reviews** (One-to-Many Received)
- **Foreign Key**: `reviews.provider_id` → `users.id`
- **Cascade**: ON DELETE CASCADE
- **Description**: One provider can receive multiple reviews from different customers
- **Used For**: Calculate `users.rating_average` and `users.total_reviews`
- **Example**: Provider "Dawit" has 25 reviews with average rating 4.8 stars

### 9. **Users ↔ Audit Logs** (One-to-Many)
- **Foreign Key**: `audit_logs.user_id` → `users.id`
- **Cascade**: ON DELETE SET NULL
- **Description**: Tracks all critical actions (login, offer_accepted, status_changed, etc.)
- **Example**: Audit log records when Provider "Yonas" accepted an offer at 2:45 PM

### 10. **Service Requests ↔ Status History** (One-to-Many)
- **Foreign Key**: `status_history.request_id` → `service_requests.id`
- **Cascade**: ON DELETE CASCADE
- **Description**: Tracks every status change of a request (Requested → Negotiating → Assigned → Completed → Reviewed)
- **Data Tracked**: old_status, new_status, duration_seconds (time spent in previous status)
- **Example**: Request #15 has 4 history records showing its journey through states

### 11. **Status History ↔ Users** (Many-to-One)
- **Foreign Key**: `status_history.changed_by` → `users.id`
- **Cascade**: ON DELETE SET NULL
- **Description**: Records which user triggered each status change
- **Example**: Admin "Tigist" moved Request #42 from Negotiating to Assigned

---

## State Machine Workflow

```
REQUESTED
    │ (First offer received)
    ▼
NEGOTIATING
    │ (Customer accepts an offer)
    ▼
ASSIGNED
    │ (Provider completes work)
    ▼
COMPLETED
    │ (Customer leaves review)
    ▼
REVIEWED ✓ [Final State]
```

**State Transitions via Status History:**
- Each transition is logged in `status_history` table
- Tracks how long request spent in each state
- Records which user triggered the change
- Enables analytics: "Average negotiation time = 2.5 days"

---

## Key Constraints & Cardinality

| Relationship | Cardinality | Unique | Cascade | Notes |
|---|---|---|---|---|
| users → service_requests | 1:N | — | DELETE CASCADE | Customer can have many requests |
| service_categories → service_requests | 1:N | — | DELETE RESTRICT | Prevents orphaned requests |
| service_requests → offers | 1:N | ✓ per provider | DELETE CASCADE | One offer per provider per request |
| users → offers | 1:N | — | DELETE CASCADE | Provider can have many offers |
| service_requests → reviews | 1:1 | ✓ | DELETE CASCADE | One review per completed job |
| users (customer) → reviews | 1:N | — | DELETE CASCADE | Customer can review many jobs |
| users (provider) → reviews | 1:N | — | DELETE CASCADE | Provider receives many reviews |
| users → audit_logs | N:1 | — | DELETE SET NULL | Logs survive user deletion |
| service_requests → status_history | 1:N | — | DELETE CASCADE | History erased with request |
| users → status_history | 1:N | — | DELETE SET NULL | User info tracked but survives deletion |

---

## Data Integrity Rules

 **Foreign Key Constraints**
- All relationships enforced at database level (InnoDB)
- Prevents orphaned records or invalid transitions

 **Business Logic Validation**
- Review can only created when status = 'Completed' (app-level enforcement)
- Offer counter-offer triggers workflow (app-level)

 **Audit Trail**
- Every critical action logged to `audit_logs`
- Every status transition logged to `status_history`
- Enables forensic analysis and compliance reporting

 **Performance Indexes**
- `idx_customer` on service_requests for fast customer queries
- `idx_provider` on offers & reviews for provider metrics
- `idx_status` on service_requests for workflow filtering
- `idx_rating` on users for provider ranking

---

## Example Scenarios

### Scenario 1: Complete Job Workflow
```
1. Customer "Ahmed" posts electrician request
   → service_requests INSERT (status='Requested')
   → status_history INSERT (old_status=NULL, new_status='Requested')

2. Provider "Abebe" submits offer
   → offers INSERT (status='pending')
   → service_requests status changed to 'Negotiating'
   → status_history INSERT (old_status='Requested', new_status='Negotiating')

3. Customer "Ahmed" accepts offer #5
   → offers UPDATE (id=5, status='accepted')
   → service_requests UPDATE (status='Assigned', accepted_offer_id=5)
   → status_history INSERT (old_status='Negotiating', new_status='Assigned')
   → audit_logs INSERT (action='offer_accepted', user_id=1)

4. Provider "Abebe" completes work
   → service_requests UPDATE (status='Completed')
   → status_history INSERT (old_status='Assigned', new_status='Completed')

5. Customer "Ahmed" leaves 5-star review
   → reviews INSERT (request_id=X, customer_id=1, provider_id=5, rating=5)
   → service_requests UPDATE (status='Reviewed')
   → status_history INSERT (old_status='Completed', new_status='Reviewed')
   → users UPDATE (provider rating_average and total_reviews incremented)
```

### Scenario 2: Multiple Offers for One Request
```
- Request #42 (electrician job)
  ├─ Offer #101 from Provider A (pending)
  ├─ Offer #102 from Provider B (pending)
  └─ Offer #103 from Provider C (pending)
  
Customer accepts Offer #102 →
  ├─ offers #102 status='accepted'
  ├─ offers #101 & #103 remain 'pending' (or app deletes them)
  └─ service_requests.accepted_offer_id=102
```

---

## Views for Common Queries

### v_active_requests
Lists requests still open for negotiation:
```sql
SELECT sr.id, sr.description, sc.name AS category, sr.location, Count(o.id) AS offer_count
FROM service_requests sr
JOIN service_categories sc ON sr.category_id = sc.id
LEFT JOIN offers o ON sr.id = o.request_id
WHERE sr.status IN ('Requested', 'Negotiating')
GROUP BY sr.id;
```

### v_provider_ratings
Provider performance metrics:
```sql
SELECT u.id, u.name, COUNT(r.id) AS total_reviews, AVG(r.rating) AS average_rating
FROM users u
LEFT JOIN reviews r ON u.id = r.provider_id
WHERE u.role = 'provider'
GROUP BY u.id;
```

---

*Last Updated: April 14, 2026*
*Project: Market_Place_Web_Dev_Project (Handy Model)*
