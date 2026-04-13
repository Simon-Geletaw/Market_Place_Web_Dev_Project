# Database Design Document
## Secure Local Service Marketplace (Handy Model)

---

## 1. Overview

This document describes the complete database architecture for the Secure Local Service Marketplace platform. The design supports the core workflow: request posting, offer negotiation, job assignment, completion verification, and review submission.

### 1.1 Database Technology
- **DBMS:** MySQL 8.0+ / MariaDB 10.5+/Postgress
- **Storage Engine:** InnoDB (for ACID compliance and foreign key support)
- **Character Set:** UTF-8 (utf8mb4_unicode_ci) for full Unicode support including Amharic text

### 1.2 Design Principles
1. **State Machine Integrity:** The `service_requests.status` field drives the entire workflow
2. **Audit Trail:** Every critical action is logged in `audit_logs` and `status_history`
3. **Data Integrity:** Foreign keys with appropriate cascade rules prevent orphaned records
4. **Performance:** Strategic indexes on frequently queried fields
5. **Security:** Prepared statement-ready schema (no SQL injection vulnerabilities)

---

## 2. Entity-Relationship Summary

### 2.1 Core Entities
- **users** - All system users (customers, providers, admins)
- **service_categories** - Types of services offered (electrician, plumber, etc.)
- **service_requests** - Customer job postings
- **offers** - Provider price quotes for requests
- **reviews** - Post-completion ratings and feedback

### 2.2 Supporting Entities
- **audit_logs** - System-wide activity tracking
- **status_history** - Request state transition analytics

### 2.3 Key Relationships
```
users (1) ──────┬──────→ (N) service_requests
                │
                ├──────→ (N) offers
                │
                └──────→ (N) reviews

service_categories (1) → (N) service_requests

service_requests (1) ──┬→ (N) offers
                       ├→ (1) review
                       └→ (N) status_history
```

---

## 3. Table Specifications

### 3.1 Table: users
**Purpose:** Stores all platform users with role-based differentiation

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique user identifier |
| email | VARCHAR(255) | NOT NULL, UNIQUE | User email (login credential) |
| password_hash | VARCHAR(255) | NOT NULL | Bcrypt hashed password |
| role | ENUM | 'customer', 'provider', 'admin' | User type |
| name | VARCHAR(255) | NOT NULL | Full name |
| phone | VARCHAR(20) | NULL | Contact number |
| location | VARCHAR(255) | NULL | Sub-city in Addis Ababa |
| rating_average | DECIMAL(3,2) | DEFAULT 0.00 | For providers: calculated rating |
| total_reviews | INT | DEFAULT 0 | For providers: review count |
| is_verified | BOOLEAN | DEFAULT FALSE | Admin verification status for providers |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Registration date |
| updated_at | TIMESTAMP | AUTO UPDATE | Last modification |

**Indexes:**
- `idx_role` - Fast role-based queries (e.g., "find all providers")
- `idx_location` - Location-based provider search
- `idx_rating` - Sort providers by rating

**Security Notes:**
- Passwords MUST be hashed using `password_hash($password, PASSWORD_DEFAULT)` in PHP
- Email uniqueness prevents duplicate accounts
- `is_verified` allows admin approval workflow for providers

---

### 3.2 Table: service_categories
**Purpose:** Categorizes service types (electrician, plumber, etc.)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Category ID |
| name | VARCHAR(100) | NOT NULL, UNIQUE | Category name |
| description | TEXT | NULL | Detailed description |
| icon | VARCHAR(50) | NULL | Icon identifier for UI |
| is_active | BOOLEAN | DEFAULT TRUE | Soft delete flag |

**Business Rules:**
- Categories are pre-populated by admins
- Customers select from active categories only
- Deleting a category with active requests is blocked by `ON DELETE RESTRICT`

---

### 3.3 Table: service_requests
**Purpose:** Customer job postings that drive the state machine

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Request ID |
| customer_id | INT | FOREIGN KEY → users(id) | Who posted the request |
| category_id | INT | FOREIGN KEY → service_categories(id) | Service type |
| description | TEXT | NOT NULL | Job details |
| preferred_date | DATE | NULL | Customer's desired date |
| status | ENUM | See below | Current workflow state |
| location | VARCHAR(255) | NOT NULL | Job location (sub-city) |
| accepted_offer_id | INT | NULL | Links to the winning offer |
| completion_photo | VARCHAR(500) | NULL | Provider uploads proof of work |
| created_at | TIMESTAMP | DEFAULT NOW | Post date |
| updated_at | TIMESTAMP | AUTO UPDATE | Last state change |

**Status Enum Values (The State Machine):**
```
Requested    → Initial state after customer posts
Negotiating  → Offers received, customer reviewing
Assigned     → Customer accepted an offer
Completed    → Provider marked job as done
Reviewed     → Customer left a review
```

**State Transition Rules:**
1. `Requested` → `Negotiating`: When first offer arrives
2. `Negotiating` → `Assigned`: When customer accepts an offer
3. `Assigned` → `Completed`: When provider marks as finished
4. `Completed` → `Reviewed`: When customer submits review

**Indexes:**
- `idx_customer` - Customer's job history
- `idx_status` - Filter by state (e.g., "show me all Assigned jobs")
- `idx_category` - Category-based analytics
- `idx_preferred_date` - Date-based scheduling

---

### 3.4 Table: offers
**Purpose:** Provider price quotes with counter-offer support

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Offer ID |
| request_id | INT | FOREIGN KEY → service_requests(id) | Which job |
| provider_id | INT | FOREIGN KEY → users(id) | Who's offering |
| price | DECIMAL(10,2) | NOT NULL | Proposed price (ETB) |
| message | TEXT | NULL | Optional pitch |
| status | ENUM | 'pending', 'accepted', 'rejected', 'countered' | Offer state |
| counter_price | DECIMAL(10,2) | NULL | Customer's counter-offer |
| counter_message | TEXT | NULL | Counter-offer justification |
| created_at | TIMESTAMP | DEFAULT NOW | Offer submission time |
| updated_at | TIMESTAMP | AUTO UPDATE | Last modification |

**Business Rules:**
- One provider can only submit one offer per request (enforced by `UNIQUE KEY`)
- Customer can counter-offer by setting `counter_price` and status = 'countered'
- Provider can then revise by creating a new offer or withdrawing

**Indexes:**
- `idx_request` - All offers for a request
- `idx_provider` - Provider's offer history
- `idx_status` - Filter by acceptance status

---

### 3.5 Table: reviews
**Purpose:** Post-completion ratings (transaction-linked)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Review ID |
| request_id | INT | FOREIGN KEY → service_requests(id), UNIQUE | One review per job |
| customer_id | INT | FOREIGN KEY → users(id) | Reviewer |
| provider_id | INT | FOREIGN KEY → users(id) | Reviewed provider |
| rating | INT | CHECK (1-5) | Star rating |
| comment | TEXT | NULL | Written feedback |
| created_at | TIMESTAMP | DEFAULT NOW | Review date |

**Business Rules:**
- `request_id` is UNIQUE - prevents duplicate reviews for same job
- Only allowed when `service_requests.status = 'Completed'` (enforced in app logic)
- After review submission, request moves to `Reviewed` state

**Indexes:**
- `idx_provider` - Provider's reviews (for rating calculation)
- `idx_rating` - Quality filtering

---

### 3.6 Table: audit_logs
**Purpose:** Forensic trail of all critical actions

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Log ID |
| user_id | INT | FOREIGN KEY → users(id), NULL | Actor (NULL if system) |
| action | VARCHAR(100) | NOT NULL | e.g., 'offer_accepted', 'login' |
| entity_type | VARCHAR(50) | NULL | e.g., 'service_request', 'offer' |
| entity_id | INT | NULL | Affected record ID |
| details | TEXT | NULL | JSON or descriptive text |
| ip_address | VARCHAR(45) | NULL | User's IP (IPv6 compatible) |
| created_at | TIMESTAMP | DEFAULT NOW | Event timestamp |

**Usage Examples:**
```sql
-- Log offer acceptance
INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
VALUES (123, 'offer_accepted', 'offer', 456, 'Price: 500 ETB');

-- Log failed login
INSERT INTO audit_logs (action, details, ip_address)
VALUES ('login_failed', 'Invalid password for email@example.com', '192.168.1.1');
```

**Indexes:**
- `idx_user` - User activity report
- `idx_action` - Filter by event type
- `idx_created` - Time-based queries

---

### 3.7 Table: status_history
**Purpose:** Analytics on how long requests stay in each state

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Record ID |
| request_id | INT | FOREIGN KEY → service_requests(id) | Which request |
| old_status | VARCHAR(50) | NULL | Previous state (NULL if first) |
| new_status | VARCHAR(50) | NOT NULL | New state |
| duration_seconds | INT | NULL | Time spent in old_status |
| changed_by | INT | FOREIGN KEY → users(id), NULL | Who triggered |
| notes | TEXT | NULL | Contextual info |
| changed_at | TIMESTAMP | DEFAULT NOW | Transition time |

**Analytics Use Cases:**
```sql
-- Average time in "Negotiating" state
SELECT AVG(duration_seconds) / 3600 AS avg_hours
FROM status_history
WHERE old_status = 'Negotiating';

-- Requests that took >3 days to assign
SELECT request_id, duration_seconds / 86400 AS days
FROM status_history
WHERE old_status = 'Requested' AND new_status = 'Assigned'
  AND duration_seconds > 259200;
```

---

## 4. Stored Procedures

### 4.1 log_status_change
**Purpose:** Automatically log state transitions

```sql
CALL log_status_change(
    request_id INT,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    changed_by INT
);
```

**What it does:**
1. Calculates time spent in `old_status`
2. Inserts record into `status_history`

**Usage Example:**
```php
// In PHP when updating request status
$stmt->execute(['new_status' => 'Assigned', 'id' => $request_id]);
$pdo->query("CALL log_status_change($request_id, 'Negotiating', 'Assigned', $user_id)");
```

---

### 4.2 accept_offer
**Purpose:** Atomic offer acceptance with state change

```sql
CALL accept_offer(offer_id INT, customer_id INT);
```

**What it does:**
1. Marks offer as `accepted`
2. Updates request status to `Assigned`
3. Logs state change to `status_history`
4. Records action in `audit_logs`

**Benefits:**
- Prevents race conditions (multiple simultaneous acceptances)
- Ensures data consistency
- Single point of truth for this critical business logic

---

## 5. Views

### 5.1 v_active_requests
**Purpose:** Customer-facing list of open jobs

```sql
SELECT * FROM v_active_requests WHERE location = 'Bole';
```

**Returns:** All requests in `Requested` or `Negotiating` state with full context (category, customer info)

---

### 5.2 v_provider_ratings
**Purpose:** Provider leaderboard and search ranking

```sql
SELECT * FROM v_provider_ratings 
WHERE average_rating >= 4.5 
ORDER BY total_reviews DESC;
```

**Returns:** Each provider's aggregate rating stats

---

## 6. Security Implementation

### 6.1 SQL Injection Prevention
**ALWAYS use PDO prepared statements:**

```php
// GOOD ✓
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$_POST['email']]);

// BAD ✗ - DO NOT DO THIS
$result = mysqli_query($conn, "SELECT * FROM users WHERE email = '{$_POST['email']}'");
```

### 6.2 XSS Prevention
**Always sanitize output:**

```php
// When displaying user input
echo htmlspecialchars($request['description'], ENT_QUOTES, 'UTF-8');
```

### 6.3 Password Security
```php
// Registration
$hash = password_hash($password, PASSWORD_DEFAULT);

// Login
if (password_verify($input_password, $stored_hash)) {
    // Valid login
}
```

---

## 7. Indexing Strategy

### 7.1 Critical Indexes
1. **Foreign Keys** - Auto-indexed for join performance
2. **Status Fields** - Frequently filtered (WHERE status = ...)
3. **Location** - Provider search optimization
4. **Timestamps** - Date range queries (recent activity, analytics)

### 7.2 Missing Indexes Warning
If queries are slow, add composite indexes:

```sql
-- If you frequently query: "requests by customer in specific status"
CREATE INDEX idx_customer_status ON service_requests(customer_id, status);

-- If you frequently query: "offers for a request by status"
CREATE INDEX idx_request_status ON offers(request_id, status);
```

---

## 8. Data Migration Notes

### 8.1 Initial Setup
```bash
mysql -u root -p marketplace_db < database_schema.sql
```

### 8.2 Sample Data
The schema includes test users and categories. **Before production:**
1. Delete test users
2. Update password hashes with real admin credentials
3. Add your actual service categories

### 8.3 Production Checklist
- [ ] Change default passwords in sample data
- [ ] Enable MySQL slow query log
- [ ] Set up automated backups
- [ ] Configure binary logging for point-in-time recovery
- [ ] Run `ANALYZE TABLE` on all tables after bulk inserts

---

## 9. Performance Optimization

### 9.1 Expected Table Sizes (1 year)
- users: ~10,000 rows
- service_requests: ~50,000 rows
- offers: ~200,000 rows
- reviews: ~40,000 rows
- audit_logs: ~500,000 rows

### 9.2 Partitioning Recommendation
If `audit_logs` grows very large (>1M rows):

```sql
ALTER TABLE audit_logs 
PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION pMax VALUES LESS THAN MAXVALUE
);
```

---

## 10. State Machine Implementation

### 10.1 PHP State Validator
```php
function canTransition($current, $new) {
    $allowed = [
        'Requested' => ['Negotiating'],
        'Negotiating' => ['Assigned'],
        'Assigned' => ['Completed'],
        'Completed' => ['Reviewed']
    ];
    return in_array($new, $allowed[$current] ?? []);
}

// Usage
if (!canTransition($current_status, $new_status)) {
    die("Invalid state transition");
}
```

### 10.2 Trigger Alternative (Database-Level)
If you want the database to enforce transitions:

```sql
DELIMITER $$
CREATE TRIGGER validate_status_change
BEFORE UPDATE ON service_requests
FOR EACH ROW
BEGIN
    IF (OLD.status = 'Reviewed' AND NEW.status != 'Reviewed') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot change status after review';
    END IF;
END$$
DELIMITER ;
```

---

## 11. Next Steps

### 11.1 Before Coding
1. ✓ Review this ERD with your team
2. ✓ Import `database_schema.sql` into your MySQL server
3. ✓ Test sample queries against test data
4. ✓ Document any custom business rules

### 11.2 During Coding
1. Create database connection file (`db_config.php`)
2. Build authentication system first (login/register)
3. Implement state machine in PHP
4. Add offer negotiation logic
5. Build review submission with state check

### 11.3 Testing Checklist
- [ ] Test LOGIN_01 (role-based access denial)
- [ ] Test OFFER_01 (state machine blocks invalid transitions)
- [ ] Test SQL injection attempts on all forms
- [ ] Test XSS payloads in description fields
- [ ] Load test with 1000+ concurrent offers

---

## Appendix A: Database Connection Template

```php
<?php
// db_config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'marketplace_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
```

---

**Document Version:** 1.0  
**Last Updated:** 2026-04-13  
**Author:** Claude (Anthropic)  
**Status:** Ready for Implementation
