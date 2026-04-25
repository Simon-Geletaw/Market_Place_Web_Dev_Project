# Backend Architecture & Project Structure
## Service Marketplace Platform (PHP 8.1+ + MySQL)

Document Version: 1.0  
Last Updated: April 24, 2026  
Project: `Market_Place_Web_Dev_Project`

---

## 1. Purpose

This document is the single **backend** reference for:

1. Backend architecture (layers, responsibilities, data flow).
2. Backend-oriented project structure (folders, files, ownership).
3. The domain workflow (roles + request status state machine).
4. The database contract the backend must implement.
5. The planned route and endpoint surface area.

It is written for a plain-PHP codebase (no Laravel/CodeIgniter) and is aligned to the repository’s current folder layout.

---

## 2. Source of Truth

Backend rules and data constraints come from:

1. `Documentation/database_schema.sql` (authoritative schema + constraints + procedures + views)
2. `Documentation/database_architecture_design.md`
3. `Documentation/database_relationships.md`
4. `Documentation/implementation_roadmap.md`

---

## 3. Tech Stack (Backend)

- Language: PHP 8.1+ (`declare(strict_types=1);`)
- Server: Apache (XAMPP/WAMP) or PHP built-in server for local dev
- Database: MySQL 8 / MariaDB
- Patterns: MVC-style pages + layered backend (Controller → Service → Repository)
- Data access: PDO + prepared statements (no raw string interpolation)

---

## 4. Domain Backbone (What The Backend Must Enforce)

### 4.1 Roles

The backend must enforce permissions based on `users.role`:

1. `customer`
2. `provider`
3. `admin`

### 4.2 Request Lifecycle (State Machine)

The request workflow is the backbone of both UI and backend:

`Requested → Negotiating → Assigned → Completed → Reviewed`

The backend must prevent invalid transitions (for example, you cannot review before completion).

---

## 5. Backend Architecture (Layers)

This codebase uses a simple layered structure:

1. **Routing (HTTP → Controller)**  
   Matches the request path + method to a controller action.
2. **Controller (I/O + orchestration)**  
   Reads input, calls services, returns a response (HTML view or JSON envelope).
3. **Service (business rules)**  
   Owns domain decisions: permissions, state transitions, transactions, workflow rules.
4. **Repository (database access)**  
   Owns SQL + mapping to/from arrays. No business rules here.
5. **Middleware (cross-cutting)**  
   Auth guard, role guard, validation guard.
6. **Helpers (shared utilities)**  
   Request parsing, JSON response shaping, view path resolution.

Rule of thumb:

- Controllers stay thin.
- Services are the “brain”.
- Repositories are the “hands” that talk to MySQL.

---

## 6. Project Structure (Backend-Oriented)

Top-level folders and what they mean:

```
app/
  controllers/        # HTTP handlers (thin)
  services/           # business rules + workflow/state machine checks
  repositories/       # SQL + persistence (PDO)
  middleware/         # auth/role/validation guards
  validators/         # input validation functions per feature
  helpers/            # request/response/view helper functions

config/               # backend configuration (currently reserved)
database/             # future migrations/seeds (schema is in Documentation/)
routes/               # route registry (currently reserved)

public/               # web server document root (static assets + entry scripts)
  assets/
    css/
    js/
    images/

views/                # server-rendered PHP views (role-based screens)
storage/
  uploads/            # user uploads (e.g., completion photos)
logs/                 # file logs (app-level) - separate from DB audit logs
tests/
  backend/
  frontend/

requirements/         # phased requirements checklists
Documentation/        # project documentation (schema, roadmap, specs)
```

Notes:

1. The **backend** code lives under `app/` (controllers/services/repositories/middleware/validators/helpers).
2. `views/` + `public/` are the server-rendered UI layer that the backend serves.
3. Database artifacts for execution live in `Documentation/database_schema.sql`.
4. `config/` and `routes/` exist to support a front-controller router, even if they are not fully implemented yet.

---

## 7. Current Backend Modules (What Exists Today)

### 7.1 Controllers (`app/controllers/`)

Existing controller classes:

- `AdminController.php`
- `AuthController.php`
- `OfferController.php`
- `RequestController.php`
- `ReviewController.php`

Controller responsibilities:

1. Parse inputs (query/body/session)
2. Call the correct service method
3. Return a consistent response shape (JSON envelope or view)

### 7.2 Services (`app/services/`)

Existing service classes:

- `AdminService.php`
- `AuthService.php`
- `OfferService.php`
- `RequestService.php`
- `ReviewService.php`

Service responsibilities:

1. Enforce role rules (customer/provider/admin)
2. Enforce the request lifecycle transitions
3. Coordinate multi-step operations using transactions
4. Trigger audit/status history writes where required

### 7.3 Repositories (`app/repositories/`)

Existing repository classes:

- `UserRepository.php`
- `RequestRepository.php`
- `OfferRepository.php`
- `ReviewRepository.php`
- `AuditLogRepository.php`
- `StatusHistoryRepository.php`

Repository responsibilities:

1. Own SQL (prepared statements)
2. Return data in simple arrays suitable for services/controllers

### 7.4 Middleware (`app/middleware/`)

Existing middleware functions:

- `require_authentication()`
- `require_role(string $role)`
- `validate_payload(array $data, array $rules): array`

### 7.5 Validators (`app/validators/`)

Existing validators (currently placeholders):

- `auth_validator.php`
- `request_validator.php`
- `offer_validator.php`
- `review_validator.php`

Validator responsibilities:

1. Validate required fields
2. Validate data types and formats (email, numbers, allowed enums)
3. Return structured errors for consistent responses

### 7.6 Helpers (`app/helpers/`)

Existing helpers:

- `request.php` (method + path helpers)
- `response.php` (JSON envelope helper)
- `view.php` (view path resolution)

---

## 8. Database Contract (Backend Must Match)

### 8.1 Tables (7 core)

1. `users`
2. `service_categories`
3. `service_requests`
4. `offers`
5. `reviews`
6. `audit_logs`
7. `status_history`

### 8.2 Important Constraints (Must Not Be Bypassed)

1. **Unique email** in `users.email`
2. **One offer per provider per request**: `offers (request_id, provider_id)` unique key
3. **One review per request**: `reviews.request_id` is unique
4. Foreign keys + cascades must remain enabled (InnoDB)

### 8.3 Stored Procedures (State Machine Helpers)

Defined in `Documentation/database_schema.sql`:

1. `log_status_change(p_request_id, p_old_status, p_new_status, p_changed_by)`
2. `accept_offer(p_offer_id, p_customer_id)`

### 8.4 Views (Read Models)

1. `v_active_requests` (open requests for browsing)
2. `v_provider_ratings` (rating aggregation for dashboards)

---

## 9. Backend Endpoints (Specification)

This section defines the **intended** endpoint surface area so routing and controllers remain consistent.

Two interaction styles are expected:

1. **Server-rendered pages** (routes return HTML via `views/`)
2. **JSON API endpoints** (routes return JSON using a standard envelope)

### 9.1 JSON Envelope (Standard Response)

Use the helper contract from `app/helpers/response.php`:

- `success` (bool)
- `message` (string)
- `data` (object/array)
- `status_code` (int)

### 9.2 Authentication

| Method | Path | Role | Purpose |
|---|---|---|---|
| POST | `/api/auth/register` | guest | Create user (customer/provider) |
| POST | `/api/auth/login` | guest | Start session |
| POST | `/api/auth/logout` | any | End session |

### 9.3 Service Requests

| Method | Path | Role | Purpose |
|---|---|---|---|
| POST | `/api/requests` | customer | Create a service request (status starts as `Requested`) |
| GET | `/api/requests` | customer | List my requests (filter by status) |
| GET | `/api/requests/{id}` | customer/provider/admin | Request detail (role-aware visibility) |
| GET | `/api/marketplace/requests` | provider | Browse active requests (Requested/Negotiating) |

### 9.4 Offers (Negotiation)

| Method | Path | Role | Purpose |
|---|---|---|---|
| POST | `/api/requests/{id}/offers` | provider | Submit offer (unique per provider/request) |
| GET | `/api/requests/{id}/offers` | customer | List offers for my request |
| POST | `/api/offers/{id}/counter` | customer | Counter an offer (updates counter fields + status) |
| POST | `/api/offers/{id}/accept` | customer | Accept offer (moves request to `Assigned`) |
| GET | `/api/offers` | provider | List my offers |

### 9.5 Completion + Reviews

| Method | Path | Role | Purpose |
|---|---|---|---|
| POST | `/api/requests/{id}/complete` | provider | Mark assigned job complete (+ optional upload path) |
| POST | `/api/requests/{id}/review` | customer | Review completed job (moves to `Reviewed`) |

### 9.6 Admin

| Method | Path | Role | Purpose |
|---|---|---|---|
| GET | `/api/admin/audit-logs` | admin | View audit events |
| POST | `/api/admin/providers/{id}/verify` | admin | Verify provider (`users.is_verified = true`) |

Implementation rules for endpoints:

1. IDs like `{id}` are integers and must be validated.
2. Role checks must be server-side (never trust the frontend).
3. Status transitions must be validated in the service layer.
4. Repository methods must use prepared statements.

---

## 10. Security Checklist (Backend)

Minimum backend security requirements:

1. Passwords: `password_hash()` and `password_verify()` only.
2. SQL: PDO prepared statements for all queries (no string concatenation).
3. Output encoding: escape all user content in views to prevent XSS.
4. Sessions: regenerate session IDs on login; protect admin routes.
5. Authorization: enforce ownership (customer can only see their requests/offers).
6. File uploads: restrict mime/type/size; store outside public if possible; randomize filenames.
7. Audit logging: write `audit_logs` for security-sensitive and workflow actions.

---

## 11. Local Setup (Backend)

### 11.1 Database Setup

1. Create a local MySQL database (example name: `marketplace`).
2. Import `Documentation/database_schema.sql`.
3. Confirm tables, procedures, and views exist.

### 11.2 Web Server Setup

Recommended:

1. Point Apache DocumentRoot to `public/` (so assets resolve under `/assets/...`).
2. Ensure PHP 8.1+ is enabled.

Alternative (quick dev):

1. Run PHP built-in server from the repository root with document root `public/`.

---

## 12. Next Implementation Steps (Practical)

1. Implement a front controller under `public/` and load `app/helpers/*`.
2. Implement a route registry under `routes/` (HTML + API).
3. Implement a PDO DB connector in `app/repositories/` (or `config/` + a shared DB module).
4. Fill repository methods to match schema queries.
5. Implement services with explicit state transition rules and transactions.
6. Add validation rules in `app/validators/` and enforce via middleware.

