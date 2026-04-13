# FAST-TRACK PROJECT ROADMAP
## Secure Local Service Marketplace - Complete Implementation Plan

---

## ✅ PHASE 1: COMPLETED DOCUMENTS

### 1.1 Software Requirements Specification (SRS) ✓
- **Status:** Complete
- **Purpose:** Defines all functional and non-functional requirements
- **Key Content:** 
  - User personas
  - State machine workflow (Requested → Negotiating → Assigned → Completed → Reviewed)
  - Security requirements (PDO, XSS prevention)
  - Performance requirements

### 1.2 Entity-Relationship Diagram (ERD) ✓
- **Status:** Complete (Interactive Visual)
- **Purpose:** Visual database schema
- **Deliverable:** Mermaid ERD showing all 7 tables and relationships

### 1.3 Database Schema (SQL) ✓
- **Status:** Complete
- **Deliverable:** `database_schema.sql`
- **Content:**
  - All CREATE TABLE statements
  - Foreign keys and indexes
  - Stored procedures (`accept_offer`, `log_status_change`)
  - Views (`v_active_requests`, `v_provider_ratings`)
  - Sample test data

### 1.4 Database Design Document ✓
- **Status:** Complete
- **Deliverable:** `database_design_document.md`
- **Content:**
  - Detailed table specifications
  - Security implementation guide
  - State machine logic
  - Performance optimization tips

---

## 📋 PHASE 2: REMAINING DOCUMENTATION (NEXT STEPS)

### 2.1 System Architecture & File Structure Document
**Priority:** HIGH (Next Document to Create)
**Estimated Time:** 2 hours

**Contents:**
```
project-root/
├── config/
│   ├── db_config.php          # Database connection
│   └── constants.php          # App-wide constants
├── includes/
│   ├── header.php             # Common header
│   ├── footer.php             # Common footer
│   └── session.php            # Session management
├── models/
│   ├── User.php               # User model (CRUD)
│   ├── ServiceRequest.php     # Request model
│   ├── Offer.php              # Offer model
│   └── Review.php             # Review model
├── controllers/
│   ├── auth.php               # Login/Register/Logout
│   ├── requests.php           # Request CRUD
│   ├── offers.php             # Offer negotiation
│   └── reviews.php            # Review submission
├── views/
│   ├── customer/
│   │   ├── dashboard.php
│   │   ├── post_request.php
│   │   └── view_offers.php
│   ├── provider/
│   │   ├── dashboard.php
│   │   ├── browse_requests.php
│   │   └── submit_offer.php
│   └── admin/
│       └── dashboard.php
├── public/
│   ├── css/
│   │   └── style.css          # Bootstrap + custom
│   ├── js/
│   │   └── app.js             # Vanilla JS
│   └── uploads/               # Completion photos
├── index.php                  # Landing page
└── .htaccess                  # URL rewriting
```

**Why Important:**
- Prevents spaghetti code
- Ensures MVC-like separation even without a framework
- Makes team collaboration easier

---

### 2.2 API Endpoint Specification Document
**Priority:** HIGH
**Estimated Time:** 1.5 hours

**Example Endpoints:**

| Endpoint | Method | Purpose | Input | Output |
|----------|--------|---------|-------|--------|
| `/api/auth/login.php` | POST | User login | email, password | JSON: session token |
| `/api/requests/create.php` | POST | Post new request | category_id, description, date | JSON: request_id |
| `/api/offers/submit.php` | POST | Submit offer | request_id, price, message | JSON: offer_id |
| `/api/offers/accept.php` | POST | Accept offer | offer_id | JSON: success |
| `/api/requests/complete.php` | POST | Mark as finished | request_id, photo | JSON: success |
| `/api/reviews/submit.php` | POST | Leave review | request_id, rating, comment | JSON: success |

**Why Important:**
- Frontend-backend contract
- AJAX interaction planning
- Makes async UI updates possible

---

### 2.3 Security Checklist Document
**Priority:** MEDIUM
**Estimated Time:** 1 hour

**Contents:**
- CSRF token implementation
- File upload validation (completion photos)
- Rate limiting strategy
- Session hijacking prevention
- Password policy enforcement

---

### 2.4 UI/UX Wireframes
**Priority:** MEDIUM (Can Overlap with Coding)
**Estimated Time:** 2 hours

**Tools:**
- Figma (quick mockups)
- draw.io (simple wireframes)
- Even paper sketches work!

**Key Screens:**
1. Landing page
2. Login/Register forms
3. Customer dashboard (My Requests grid)
4. Provider dashboard (Browse Requests)
5. Offer negotiation modal
6. Review submission form

**Why Important:**
- Prevents redesign during coding
- Team alignment on user flow

---

## 🚀 PHASE 3: CODING APPROACH (VANILLA STACK)

### 3.1 Technology Stack Confirmed
```
Frontend:  HTML5 + CSS3 + Bootstrap 5 + Vanilla JavaScript
Backend:   PHP 8.1+ (no Laravel/CodeIgniter)
Database:  MySQL 8.0+ / MariaDB
Server:    Apache (XAMPP/WAMP for local dev)
```

**Why No Framework?**
- Faster learning curve for beginners
- No framework overhead
- Direct control over every line of code
- Easier to debug

---

### 3.2 Coding Phases (7-Day Sprint)

#### **DAY 1: Foundation Setup**
**Tasks:**
- [ ] Install XAMPP/WAMP
- [ ] Create database and import `database_schema.sql`
- [ ] Set up folder structure
- [ ] Create `db_config.php` and test connection
- [ ] Build landing page with Bootstrap

**Deliverable:** Working homepage with "Login" and "Register" buttons

---

#### **DAY 2: Authentication System**
**Tasks:**
- [ ] Create `register.php` (Customer/Provider selection)
- [ ] Create `login.php` with password verification
- [ ] Build session management (`session.php`)
- [ ] Implement role-based redirects
- [ ] Create logout functionality

**Deliverable:** Users can register, login, and see role-specific dashboards

**Test:** Verify LOGIN_01 - access denial between roles

---

#### **DAY 3: Customer Flow (Request Posting)**
**Tasks:**
- [ ] Customer dashboard: "My Requests" table
- [ ] Create `post_request.php` form
- [ ] Implement AJAX request submission
- [ ] Show success confirmation
- [ ] Display posted requests in dashboard

**Deliverable:** Customers can post service requests

---

#### **DAY 4: Provider Flow (Browse & Offer)**
**Tasks:**
- [ ] Provider dashboard: browse active requests by category/location
- [ ] Build "Submit Offer" modal
- [ ] Implement offer submission
- [ ] Show "My Offers" section with status tracking

**Deliverable:** Providers can browse requests and submit offers

---

#### **DAY 5: Negotiation & Assignment (The Core Feature)**
**Tasks:**
- [ ] Customer: View all offers for a request
- [ ] Implement "Accept Offer" button → calls `accept_offer()` stored procedure
- [ ] Implement "Counter Offer" modal
- [ ] State machine validation (prevent invalid transitions)
- [ ] Real-time status updates

**Deliverable:** Full offer-counter-offer workflow with state changes

**Test:** Verify OFFER_01 - cannot mark finished before assignment

---

#### **DAY 6: Completion & Reviews**
**Tasks:**
- [ ] Provider: "Mark as Finished" button (only for Assigned jobs)
- [ ] Image upload for completion photo
- [ ] Customer: Review form (only for Completed jobs)
- [ ] Star rating UI
- [ ] Update provider's `rating_average` after review

**Deliverable:** Complete transaction lifecycle from request to review

---

#### **DAY 7: Admin Panel & Polish**
**Tasks:**
- [ ] Admin dashboard: verify providers (`is_verified` flag)
- [ ] View audit logs
- [ ] Status analytics (avg time per state)
- [ ] UI polish (error messages, loading spinners)
- [ ] Mobile responsiveness check
- [ ] Security audit (XSS/SQL injection tests)

**Deliverable:** Fully functional MVP

---

### 3.3 Development Best Practices

#### **Code Organization Pattern (MVC-ish)**
Even without a framework, use this structure:

```php
// models/ServiceRequest.php
class ServiceRequest {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function create($customer_id, $category_id, $description, $date, $location) {
        $stmt = $this->pdo->prepare("
            INSERT INTO service_requests 
            (customer_id, category_id, description, preferred_date, location)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$customer_id, $category_id, $description, $date, $location]);
        return $this->pdo->lastInsertId();
    }
}

// controllers/requests.php
require_once '../models/ServiceRequest.php';
$requestModel = new ServiceRequest($pdo);
$requestId = $requestModel->create(...);

// views/customer/post_request.php
<form action="controllers/requests.php" method="POST">
    <!-- form fields -->
</form>
```

---

#### **AJAX Pattern (No jQuery)**
```javascript
// public/js/app.js
function submitOffer(requestId) {
    const formData = new FormData();
    formData.append('request_id', requestId);
    formData.append('price', document.getElementById('price').value);
    
    fetch('/api/offers/submit.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('Offer submitted successfully!', 'success');
            refreshOffersList();
        }
    })
    .catch(err => showAlert('Error: ' + err, 'danger'));
}
```

---

#### **Security Implementation Checklist**
Every PHP file must have:

```php
<?php
// 1. Session start
session_start();

// 2. CSRF token generation (in forms)
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// 3. CSRF validation (in POST handlers)
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('Invalid CSRF token');
}

// 4. Input sanitization
$description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');

// 5. Prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
?>
```

---

## 📊 PROGRESS TRACKING

### Documentation Progress
- [x] SRS Document
- [x] ERD Diagram
- [x] Database Schema SQL
- [x] Database Design Document
- [ ] System Architecture Document
- [ ] API Endpoint Specification
- [ ] Security Checklist
- [ ] UI Wireframes (optional but recommended)

### Coding Progress (7-Day Plan)
- [ ] Day 1: Foundation Setup
- [ ] Day 2: Authentication System
- [ ] Day 3: Customer Request Posting
- [ ] Day 4: Provider Browse & Offer
- [ ] Day 5: Negotiation & State Machine
- [ ] Day 6: Completion & Reviews
- [ ] Day 7: Admin Panel & Polish

---

## 🎯 CRITICAL SUCCESS FACTORS

### 1. State Machine is King
**Everything** revolves around the `service_requests.status` field:
- Use the stored procedures (`accept_offer`, `log_status_change`)
- Never update status without logging to `status_history`
- Enforce transitions in both PHP and database

### 2. Security First
- Every form: CSRF token
- Every database query: PDO prepared statement
- Every output: `htmlspecialchars()`
- File uploads: whitelist extensions + size limits

### 3. Mobile-First UI
- Bootstrap 5 grid system
- Test on 320px viewport (iPhone SE)
- Buttons must be ≥44px touch targets
- Avoid horizontal scrolling

### 4. Test Early, Test Often
Run these tests DAILY:
```bash
# SQL Injection Test
curl -X POST http://localhost/api/auth/login.php \
  -d "email=admin'--&password=test"
# Should fail with sanitization error

# XSS Test
Submit: <script>alert('XSS')</script>
# Should display as text, not execute

# State Machine Test
Try to mark job as finished before it's assigned
# Should return error: "Invalid state transition"
```

---

## 🔄 NEXT IMMEDIATE STEPS

### Step 1: Create System Architecture Document (TODAY)
I can generate this for you right now with:
- Complete folder structure
- File naming conventions
- Class/function organization

### Step 2: Set Up Development Environment (TODAY)
- Install XAMPP
- Import database schema
- Create project folder

### Step 3: Start Day 1 Coding (TOMORROW)
- Build landing page
- Create database connection file
- Test connection

---

## 💡 PRODUCTIVITY TIPS

### 1. Parallel Work Strategy
If you have a team:
- **Developer A:** Authentication + Session (Days 1-2)
- **Developer B:** Database setup + Models (Days 1-2)
- **Developer C:** UI/CSS + Bootstrap layouts (Days 1-2)
Then merge on Day 3

### 2. Use Code Snippets
Create a `snippets.txt` file with reusable code:
- PDO connection template
- CSRF token generation
- Error message display
- Success alert

### 3. Commit Often
Even without Git:
- Zip your project folder daily: `project_2026-04-13.zip`
- Keep old versions
- Roll back if something breaks

---

## 🚨 COMMON PITFALLS TO AVOID

### 1. "I'll Add Security Later"
❌ NO! Security must be built-in from Day 1
✅ Use prepared statements from the first query

### 2. "Let Me Perfect This Page First"
❌ Don't spend 3 days on one page's CSS
✅ Build all core features first, polish at the end

### 3. "I'll Document Later"
❌ Code without comments becomes unreadable in 2 weeks
✅ Write inline comments as you code

### 4. "The State Machine Can Be Simpler"
❌ Cutting corners here breaks the entire workflow
✅ Follow the SRS state diagram exactly

---

## 📞 READY TO START CODING?

**OPTION A: Start Immediately (Risky but Fast)**
- Skip architecture document
- Jump to Day 1 coding
- Learn folder structure as you go

**OPTION B: Complete Documentation First (Recommended)**
- Spend 3 more hours on architecture + API docs
- Then code with clear roadmap
- Fewer bugs, less refactoring

**My Recommendation:** Option B  
The 3 hours you invest now will save 10 hours of debugging later.

---

## 🎁 BONUS: DEPLOYMENT CHECKLIST (For Later)

When you're ready to go live:
- [ ] Change `db_config.php` to production credentials
- [ ] Set `error_reporting(0)` in production
- [ ] Enable HTTPS (Let's Encrypt)
- [ ] Set up automated database backups
- [ ] Configure `.htaccess` for security headers
- [ ] Run performance audit (GTmetrix)
- [ ] Test on real mobile devices

---

**Would you like me to create the System Architecture Document next?**  
Or are you ready to jump straight into coding setup?

---

**Document Status:** Ready for Action  
**Last Updated:** 2026-04-13  
**Your Current Position:** ✅ Database Design Complete → Next: Architecture or Code
