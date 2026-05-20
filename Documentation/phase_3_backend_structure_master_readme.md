# Phase 3 Master README
## Backend Structure: Routing, Controllers, Services, Repositories, Middleware, and Validation

Project: `Market_Place_Web_Dev_Project`  
Backend stack: PHP 8.1+, MySQL/MariaDB, Apache/XAMPP, PDO  
Phase source of truth: `requirements/phase_3_requirements.md`

---

## Phase Introduction

Phase 3 is where this project begins to look like a real backend application instead of a collection of PHP files.

The goal is not to write as many files as possible. The goal is to give every file a clear job, every request a predictable path, and every business rule a proper home.

This phase focuses exclusively on backend structure:

1. Routing
2. Controllers
3. Services
4. Repositories
5. Middleware
6. Validators
7. Consistent responses and errors

The project must support these route groups:

1. `auth`
2. `requests`
3. `offers`
4. `reviews`
5. `admin`

The project must also support these core constraints:

1. Role separation between `customer`, `provider`, and `admin`
2. The request state machine: `Requested -> Negotiating -> Assigned -> Completed -> Reviewed`
3. `audit_logs` integration for critical actions
4. `status_history` integration for request status changes
5. Category browsing and provider workflows

### What Backend Architecture Means

Backend architecture is the way server-side code is organized so that the application can receive requests, enforce rules, talk to the database, and return safe responses.

In beginner PHP, it is common to see one file do everything:

1. Read `$_POST`
2. Check a session
3. Run SQL
4. Decide business rules
5. Echo HTML or JSON
6. Handle errors

That works for tiny demos, but it fails quickly in real applications.

Professional backend architecture separates these responsibilities:

| Layer | Main Job |
| --- | --- |
| Route | Decide which controller action handles the URL |
| Middleware | Block bad requests before the app does real work |
| Controller | Read input and return output |
| Service | Enforce business rules and workflow decisions |
| Repository | Execute SQL and retrieve or persist data |
| Validator | Reuse input validation rules |
| Helper | Share small technical utilities like response formatting |

### Why Structure Matters More Than Code Volume

Large applications are not hard because they contain many lines of code. They are hard because the wrong code lives in the wrong place.

A small but poorly structured backend becomes difficult because:

1. You cannot find where a rule is enforced.
2. You fix a bug in one route but forget another route has duplicate logic.
3. You accidentally allow a provider to do a customer-only action.
4. You update a SQL query in one file but leave another query outdated.
5. You return errors in five different formats.
6. You allow invalid state transitions because the check is missing from one controller.

Phase 3 prevents that by creating a stable mental map.

### Why Spaghetti PHP Fails

"Spaghetti PHP" means the application has no clean separation. HTML, SQL, authorization, validation, and business rules are tangled together.

Example symptoms:

1. A route file contains raw SQL.
2. A controller decides whether `Completed` can become `Reviewed`.
3. A repository checks `$_SESSION`.
4. A view decides whether a user is allowed to accept an offer.
5. Validation rules are copied into many files.
6. Database errors are displayed directly to users.

This is dangerous for this marketplace because the workflow is role-sensitive and state-sensitive. A wrong check could allow:

1. A provider to create a request.
2. A customer to submit an offer.
3. A review before completion.
4. A second accepted offer on the same request.
5. An admin route to be accessed by a normal user.
6. A request to skip from `Requested` directly to `Completed`.

### MVC-Inspired Layered Architecture

This project uses an MVC-inspired layered backend.

It is "MVC-inspired" because plain PHP does not force a framework structure, but the project still follows the professional idea:

1. Request enters through a public entry point.
2. Router maps URL to a controller action.
3. Controller handles HTTP input/output.
4. Service handles business decisions.
5. Repository handles database access.
6. View or JSON response is returned.

For Phase 3, the most important rule is:

```text
Route -> Middleware -> Controller -> Service -> Repository -> Database
```

The reverse path returns data:

```text
Database -> Repository -> Service -> Controller -> Response -> Browser
```

### Difference Between Routes, Controllers, Services, Repositories, Middleware, and Validators

| Concept | Beginner Meaning | Project Meaning |
| --- | --- | --- |
| Route | A URL rule | Maps `/requests/create` to `RequestController::create` |
| Controller | HTTP handler | Reads request data, calls service, returns response |
| Service | Business brain | Enforces roles, offer rules, state transitions, audit/status triggers |
| Repository | Database worker | Runs SQL with PDO prepared statements |
| Middleware | Gatekeeper | Checks authentication, roles, and validation before controller |
| Validator | Input rule module | Defines reusable rules for login, request creation, offers, reviews |

### HTTP Request Lifecycle In This Project

When a user clicks a button or submits a form, the backend should process the request in this order:

1. Browser sends HTTP request.
2. `public/index.php` receives the request.
3. Router reads HTTP method and URI.
4. Matching route is found.
5. Middleware runs.
6. Authentication middleware confirms the user is logged in when required.
7. Role middleware confirms the user has permission.
8. Validation middleware checks input.
9. Controller receives clean request data.
10. Controller calls exactly one service method for the use case.
11. Service loads data through repositories.
12. Service enforces business rules.
13. Service calls repositories to persist changes.
14. Repositories run SQL using PDO prepared statements.
15. Service triggers audit logs and status history where needed.
16. Controller formats success or error response.
17. Browser receives JSON, redirect, or rendered page.

### Project Lifecycle Example

Provider submits an offer:

```text
Browser
  -> POST /offers/submit
  -> auth middleware
  -> role middleware: provider
  -> validation middleware: request_id, price, message
  -> OfferController::submit
  -> OfferService::submitOffer
  -> RequestRepository::findById
  -> OfferRepository::findByProviderAndRequest
  -> OfferRepository::create
  -> RequestRepository::updateStatus(Requested -> Negotiating)
  -> StatusHistoryRepository::record
  -> AuditLogRepository::record
  -> success response
```

The controller does not know SQL. The repository does not know browser input. The service owns the marketplace rule.

---

## Architectural Thinking For Beginners

### Separation Of Concerns

Separation of concerns means each part of the backend should focus on one category of work.

In this project:

1. Routes care about URL mapping.
2. Middleware cares about request access.
3. Controllers care about HTTP input/output.
4. Services care about marketplace rules.
5. Repositories care about SQL.
6. Validators care about input correctness.

This makes the backend easier to read because you know where to look.

If a route is wrong, check `routes/`.  
If a response shape is wrong, check the controller or response helper.  
If an offer rule is wrong, check `OfferService`.  
If a SQL query is wrong, check `OfferRepository`.  
If invalid input passes through, check validators or validation middleware.

### Single Responsibility Principle

Single responsibility means a file or class should have one reason to change.

Examples:

| File | Should Change When |
| --- | --- |
| `AuthController.php` | Login/register HTTP behavior changes |
| `AuthService.php` | Password or authentication business rules change |
| `UserRepository.php` | User SQL queries change |
| `auth_validator.php` | Login/register input rules change |
| `role.php` middleware | Role authorization behavior changes |

If one file changes for every feature, that file is too powerful.

### Maintainability

Maintainable code is code you can safely change later.

Phase 3 makes the project maintainable by requiring:

1. Explicit route-to-controller mapping
2. Controllers that call services only
3. Services that call repositories only
4. SQL centralized in repositories
5. Validation centralized in validator modules
6. Consistent response and error handling

### Scalability

Scalability is not only about traffic. It is also about team and feature growth.

This structure makes future features easier:

1. Add a new request filter by updating request routes, service, and repository.
2. Add a new admin report by creating an admin route, controller action, service method, and repository query.
3. Add upload validation by extending validators and middleware.
4. Add more audit logging without changing controllers.

### Security Boundaries

Security improves when checks happen in predictable places.

In this project:

1. Middleware blocks unauthenticated users.
2. Role middleware blocks wrong roles.
3. Validation middleware blocks malformed input.
4. Services block invalid business actions.
5. Repositories prevent SQL injection with prepared statements.
6. Controllers avoid leaking internal errors.

### Why Controllers Should Stay Thin

Controllers sit close to the internet. They handle messy HTTP details:

1. Query strings
2. Form data
3. JSON bodies
4. Session data
5. Response formatting

Because they already touch external input, controllers should not also contain business rules and SQL. A thin controller is easier to secure and easier to test.

Controller rule:

```text
Parse request -> call service -> return response
```

### Why Business Logic Must Not Live In Routes

Routes should answer one question:

```text
Which controller action handles this method and URI?
```

Routes should not decide:

1. Whether an offer can be accepted
2. Whether a request can be reviewed
3. Whether a provider is verified
4. Whether a status transition is legal
5. Which SQL query should run

If business logic lives in routes, route files become hard to read and dangerous to modify.

### Why SQL In Controllers Is Dangerous

SQL in controllers causes several problems:

1. Duplicate queries across multiple controllers
2. Mixed HTTP logic and persistence logic
3. Harder testing
4. Higher risk of SQL injection
5. Harder transaction management
6. No single place to optimize queries

In this project, SQL belongs only in repository classes.

---

## Project Folder Structure Blueprint

Recommended backend structure:

```text
backend/
  public/
    index.php
    .htaccess
    assets/
  routes/
    web.php
    api.php
  app/
    controllers/
    services/
    repositories/
    middleware/
    validators/
    models/
      entities/
    helpers/
  config/
    app.php
    database.php
  database/
    schema.sql
    seeds/
  views/
  storage/
    uploads/
  logs/
  tests/
```

The current repository already contains most of this shape under `backend/`, including `backend/app/controllers`, `backend/app/services`, `backend/app/repositories`, `backend/app/middleware`, and `backend/app/validators`.

### `public/`

Purpose:

The web server document root. Public files are the only files the browser should directly access.

What goes inside:

1. `index.php` front controller
2. `.htaccess` rewrite rules
3. CSS, JS, images
4. Public upload access only if safely controlled

Why it exists:

It protects application code. Users should not directly open `services/AuthService.php` or `config/database.php`.

Project examples:

1. `backend/public/index.php`
2. `backend/public/assets/css`
3. `backend/public/assets/js`

Common mistakes:

1. Putting controllers directly in public folders
2. Exposing config files
3. Allowing uploaded PHP files to execute
4. Handling every route in separate public PHP pages

### `routes/`

Purpose:

Defines explicit URL-to-controller mappings.

What goes inside:

1. Route groups
2. HTTP method definitions
3. Controller action references
4. Middleware assignments

Why it exists:

Routes are the table of contents for the backend. A developer should be able to open route files and understand the application surface.

Project examples:

1. `/auth/login -> AuthController::login`
2. `/requests/create -> RequestController::create`
3. `/offers/submit -> OfferController::submit`
4. `/admin/categories -> AdminController::categories`

Common mistakes:

1. Running SQL in route files
2. Writing business rules in route definitions
3. Hiding route mappings inside controllers
4. Creating unclear URLs like `/doAction.php`

### `controllers/`

Purpose:

Controllers handle HTTP-specific work.

What goes inside:

1. Request parsing
2. Calling service methods
3. Response formatting
4. Redirect decisions
5. User-friendly error handling

Why it exists:

Controllers keep the web layer separate from the business layer.

Project examples:

1. `AuthController`
2. `RequestController`
3. `OfferController`
4. `ReviewController`
5. `AdminController`

Common mistakes:

1. Writing SQL in controllers
2. Checking complex state transitions in controllers
3. Duplicating validation rules
4. Returning inconsistent errors
5. Calling repositories directly

Phase 3 rule:

```text
Controllers call services only.
```

### `services/`

Purpose:

Services hold business logic, workflow rules, and state transition decisions.

What goes inside:

1. Role-specific workflow rules
2. State machine checks
3. Offer acceptance rules
4. Review eligibility rules
5. Audit log triggers
6. Status history triggers
7. Transaction coordination

Why it exists:

The service layer is the brain of the backend. It decides what actions are allowed.

Project examples:

1. `RequestService::createRequest`
2. `OfferService::submitOffer`
3. `OfferService::acceptOffer`
4. `ReviewService::submitReview`
5. `AdminService::createCategory`

Common mistakes:

1. Treating services as pass-through wrappers
2. Putting SQL directly in services
3. Forgetting status history writes
4. Forgetting audit logs
5. Allowing state changes without checking current state

Phase 3 rule:

```text
State transition checks stay inside services.
```

### `repositories/`

Purpose:

Repositories own all database access.

What goes inside:

1. PDO queries
2. Prepared statements
3. Database inserts, updates, deletes, selects
4. Database result mapping
5. Query-specific methods

Why it exists:

Repositories isolate SQL so controllers and services do not become database scripts.

Project examples:

1. `UserRepository`
2. `RequestRepository`
3. `OfferRepository`
4. `ReviewRepository`
5. `AuditLogRepository`
6. `StatusHistoryRepository`

Common mistakes:

1. Checking `$_SESSION` in repositories
2. Returning raw PDO statements to services
3. Mixing business rules into SQL methods
4. Building SQL with string concatenation
5. Copying the same query into many files

Phase 3 rule:

```text
Services call repositories only for persistence operations.
```

### `middleware/`

Purpose:

Middleware intercepts requests before controllers run.

What goes inside:

1. Authentication checks
2. Role checks
3. Validation execution
4. CSRF checks
5. Request size checks
6. Content type checks

Why it exists:

Middleware creates reusable security gates.

Project examples:

1. `auth.php`
2. `role.php`
3. `validation.php`

Common mistakes:

1. Checking roles inside every controller action
2. Allowing invalid input to reach services
3. Mixing validation rules directly into middleware
4. Returning different error shapes from each middleware

Phase 3 rule:

```text
Invalid input is blocked before service execution.
```

### `validators/`

Purpose:

Validators define reusable input validation rules.

What goes inside:

1. Required fields
2. Type rules
3. Length rules
4. Enum rules
5. Numeric limits
6. File rules
7. Sanitization helpers where appropriate

Why it exists:

Validation should be consistent across HTML forms, JSON APIs, and future features.

Project examples:

1. `auth_validator.php`
2. `request_validator.php`
3. `offer_validator.php`
4. `review_validator.php`

Common mistakes:

1. Duplicating rules in multiple controllers
2. Mixing database checks into basic validators
3. Sanitizing output only sometimes
4. Returning errors in inconsistent formats

Phase 3 rule:

```text
Reusable validation rules stay in validator modules.
```

### `config/`

Purpose:

Configuration files store environment-specific settings.

What goes inside:

1. Database connection settings
2. App URL
3. Session settings
4. Upload limits
5. Error mode settings

Why it exists:

Configuration changes between local, staging, and production. Business code should not be edited just because the database password changes.

Project examples:

1. `backend/config/app.php`
2. Future `backend/config/database.php`

Common mistakes:

1. Hardcoding credentials inside repositories
2. Committing real production secrets
3. Changing application logic to change environment settings

### `models/entities/`

Purpose:

Models or entities represent important domain objects.

What goes inside:

1. `User`
2. `ServiceRequest`
3. `Offer`
4. `Review`
5. `ServiceCategory`
6. `AuditLog`
7. `StatusHistory`

Why it exists:

Entities make the domain easier to reason about. In a beginner plain-PHP project, arrays are acceptable early on, but entities become useful as the application matures.

Project examples:

1. A `ServiceRequest` entity can expose current status.
2. An `Offer` entity can expose price and provider.
3. A `User` entity can expose role.

Common mistakes:

1. Putting SQL inside entities
2. Making entities depend on `$_POST`
3. Creating entities before the project actually needs them

### `helpers/`

Purpose:

Helpers provide small reusable technical utilities.

What goes inside:

1. Request parsing helpers
2. Response envelope helpers
3. View rendering helpers
4. Redirect helpers
5. Safe output helpers

Why it exists:

Small repeated technical tasks should not be copied everywhere.

Project examples:

1. `request.php`
2. `response.php`
3. `view.php`

Common mistakes:

1. Turning helpers into a dumping ground
2. Putting business logic in helpers
3. Putting SQL in helpers
4. Creating large global utility files with unrelated functions

---

## Routing System Deep Dive

### What Routing Is

Routing is the process of matching an HTTP request to the correct controller action.

A route usually considers:

1. HTTP method
2. URI path
3. Middleware
4. Controller action

Example route concept:

```text
POST /offers/submit
  middleware: auth, role:provider, validate:offer_submit
  controller: OfferController::submit
```

### HTTP Methods

#### `GET`

Used to read data or show pages.

Project examples:

1. `GET /auth/login` shows login page.
2. `GET /requests/browse` shows active requests.
3. `GET /admin/categories` lists categories.

Security:

`GET` routes should not create, update, or delete important data.

#### `POST`

Used to create data or submit forms.

Project examples:

1. `POST /auth/login`
2. `POST /requests/create`
3. `POST /offers/submit`
4. `POST /reviews/submit`

Security:

Use validation, authentication where needed, role checks, and CSRF protection for browser forms.

#### `PUT` / `PATCH`

Used to update existing data.

Project examples:

1. `PATCH /offers/{offer_id}/accept`
2. `PATCH /requests/{request_id}/complete`
3. `PATCH /admin/categories/{category_id}`

In simple HTML forms, PHP may receive these as `POST` plus a hidden `_method` value. The router can normalize that later.

#### `DELETE`

Used to remove data.

Project examples:

1. `DELETE /admin/categories/{category_id}`
2. `DELETE /requests/{request_id}` if cancellation/deletion is supported

Security:

Delete routes must be protected and should usually require CSRF checks and authorization.

### Route Grouping

Route grouping keeps related URLs together.

Required Phase 3 route groups:

1. `auth`
2. `requests`
3. `offers`
4. `reviews`
5. `admin`

Group example:

```text
auth group
  GET  /auth/login
  POST /auth/login
  GET  /auth/register
  POST /auth/register
  POST /auth/logout
```

Why grouping matters:

1. Easier navigation
2. Easier middleware assignment
3. Easier route review
4. Easier future API versioning

### URI Design

Good URIs are readable and resource-oriented.

Project examples:

| Use Case | Recommended URI |
| --- | --- |
| Show login | `GET /auth/login` |
| Submit login | `POST /auth/login` |
| Create request | `POST /requests/create` |
| Browse requests | `GET /requests/browse` |
| Submit offer | `POST /offers/submit` |
| Accept offer | `PATCH /offers/{offer_id}/accept` |
| Submit review | `POST /reviews/submit` |
| Manage categories | `GET /admin/categories` |

Avoid unclear URIs:

1. `/process.php`
2. `/do_request.php`
3. `/action=accept`
4. `/submit2.php`

### REST-Like Thinking

This project does not need to be a perfect REST API, but it should follow REST-like discipline:

1. Use nouns for resources: `requests`, `offers`, `reviews`, `categories`.
2. Use HTTP methods to show intent.
3. Use route parameters for IDs.
4. Keep action names only where they clarify workflow actions.

Examples:

```text
GET    /requests/{request_id}
POST   /requests/create
POST   /offers/submit
PATCH  /offers/{offer_id}/accept
POST   /reviews/submit
```

### Route Parameters

Route parameters represent dynamic values in the URL.

Example:

```text
GET /requests/{request_id}
```

If the browser visits:

```text
GET /requests/abc-123
```

The router passes:

```text
request_id = abc-123
```

Security:

Route parameters are still user input. They must be validated before service execution.

### Protected Routes

Protected routes require middleware.

Examples:

| Route | Required Middleware |
| --- | --- |
| `POST /requests/create` | auth, role:customer, validate request |
| `POST /offers/submit` | auth, role:provider, validate offer |
| `PATCH /offers/{offer_id}/accept` | auth, role:customer, validate route params |
| `POST /reviews/submit` | auth, role:customer, validate review |
| `GET /admin/categories` | auth, role:admin |

### Explicit Project Route Mapping

Every route must map to one controller action.

#### Auth Routes

| Method | URI | Middleware | Controller Action | Purpose |
| --- | --- | --- | --- | --- |
| `GET` | `/auth/login` | guest optional | `AuthController::showLogin` | Show login form |
| `POST` | `/auth/login` | validate login | `AuthController::login` | Authenticate user |
| `GET` | `/auth/register` | guest optional | `AuthController::showRegister` | Show registration form |
| `POST` | `/auth/register` | validate register | `AuthController::register` | Create customer/provider account |
| `POST` | `/auth/logout` | auth | `AuthController::logout` | End session |

Flow:

```text
Route -> validation middleware -> AuthController -> AuthService -> UserRepository
```

Security:

1. Passwords must be verified with `password_verify`.
2. Passwords must be stored with `password_hash`.
3. Login errors must not reveal whether email or password was wrong.
4. Admin registration should not be open to public users unless explicitly controlled.

Maintainability:

All authentication decisions belong in `AuthService`; all user SQL belongs in `UserRepository`.

#### Request Routes

| Method | URI | Middleware | Controller Action | Purpose |
| --- | --- | --- | --- | --- |
| `GET` | `/requests/browse` | auth optional or provider auth | `RequestController::browse` | Browse active requests by category/location |
| `GET` | `/requests/{request_id}` | auth | `RequestController::show` | Show request detail |
| `POST` | `/requests/create` | auth, role:customer, validate request | `RequestController::create` | Customer creates service request |
| `PATCH` | `/requests/{request_id}/complete` | auth, role:provider, validate completion | `RequestController::markCompleted` | Provider marks assigned work complete |

Flow:

```text
Route -> auth/role/validation -> RequestController -> RequestService -> RequestRepository
```

Security:

1. Only customers create requests.
2. Only the assigned provider can mark a request completed.
3. Providers browse only appropriate active requests.
4. Category and location filters must be validated.

Maintainability:

The controller should not know the state machine. It should call `RequestService`.

#### Offer Routes

| Method | URI | Middleware | Controller Action | Purpose |
| --- | --- | --- | --- | --- |
| `POST` | `/offers/submit` | auth, role:provider, validate offer | `OfferController::submit` | Provider submits offer |
| `PATCH` | `/offers/{offer_id}/accept` | auth, role:customer, validate params | `OfferController::accept` | Customer accepts offer |
| `PATCH` | `/offers/{offer_id}/reject` | auth, role:customer, validate params | `OfferController::reject` | Customer rejects offer |
| `PATCH` | `/offers/{offer_id}/counter` | auth, role:customer, validate counter | `OfferController::counter` | Customer sends counter price/message |

Flow:

```text
Route -> auth/role/validation -> OfferController -> OfferService -> OfferRepository + RequestRepository
```

Security:

1. Only providers submit offers.
2. Providers cannot submit offers to their own customer request.
3. One provider should not submit duplicate offers for the same request.
4. Only the request owner can accept, reject, or counter offers.
5. Accepting an offer must move `Negotiating -> Assigned`.

Maintainability:

Offer acceptance is a business transaction. It belongs in `OfferService`.

#### Review Routes

| Method | URI | Middleware | Controller Action | Purpose |
| --- | --- | --- | --- | --- |
| `POST` | `/reviews/submit` | auth, role:customer, validate review | `ReviewController::submit` | Customer submits review |
| `GET` | `/reviews/provider/{provider_id}` | auth optional | `ReviewController::providerReviews` | Show provider reviews |

Flow:

```text
Route -> auth/role/validation -> ReviewController -> ReviewService -> ReviewRepository
```

Security:

1. Only customers submit reviews.
2. Review allowed only when request status is `Completed`.
3. Only the customer who owns the request can review it.
4. One request can have only one review.
5. Review submission moves `Completed -> Reviewed`.

Maintainability:

Review eligibility belongs in `ReviewService`, not in a form or controller.

#### Admin Routes

| Method | URI | Middleware | Controller Action | Purpose |
| --- | --- | --- | --- | --- |
| `GET` | `/admin/dashboard` | auth, role:admin | `AdminController::dashboard` | Admin overview |
| `GET` | `/admin/categories` | auth, role:admin | `AdminController::categories` | List categories |
| `POST` | `/admin/categories` | auth, role:admin, validate category | `AdminController::createCategory` | Create category |
| `PATCH` | `/admin/categories/{category_id}` | auth, role:admin, validate category | `AdminController::updateCategory` | Update category |
| `GET` | `/admin/audit-logs` | auth, role:admin | `AdminController::auditLogs` | Review critical actions |
| `PATCH` | `/admin/providers/{provider_id}/verify` | auth, role:admin, validate params | `AdminController::verifyProvider` | Verify provider |

Flow:

```text
Route -> auth/role/validation -> AdminController -> AdminService -> repositories
```

Security:

1. Only admins access admin routes.
2. Admin actions should be audit logged.
3. Category deletion should respect database constraints.
4. Provider verification should be explicit and traceable.

Maintainability:

Admin workflows should not bypass services just because they are internal.

---

## Controllers Deep Dive

### What Controllers Do

Controllers translate HTTP into application commands.

They should:

1. Read sanitized request input from request helpers or middleware.
2. Read authenticated user information from session/auth context.
3. Call one service method for the use case.
4. Convert service result into JSON, redirect, or rendered view.
5. Catch expected application errors and format them consistently.

### What Controllers Do Not Do

Controllers should not:

1. Run SQL queries.
2. Open PDO connections directly.
3. Decide state transitions.
4. Duplicate validation rules.
5. Decide complex permissions.
6. Update audit logs directly unless the action is purely HTTP-level.
7. Echo raw database errors.

### Parsing Request Data

A controller can read:

1. Route parameters like `request_id`
2. Body input like `description`, `price`, `rating`
3. Query filters like `category_id`, `location`
4. Authenticated user context like `user_id`, `role`

But parsing is not the same as trusting.

Validation middleware should already have checked required fields and basic formats before the controller calls the service.

### Calling Services

Controller-to-service calls should be direct and use meaningful method names.

Examples:

```text
AuthController::login -> AuthService::login
RequestController::create -> RequestService::createRequest
OfferController::submit -> OfferService::submitOffer
OfferController::accept -> OfferService::acceptOffer
ReviewController::submit -> ReviewService::submitReview
AdminController::createCategory -> AdminService::createCategory
```

### Returning JSON Or Redirects

For JSON responses, use a consistent envelope.

For browser form workflows, redirects are acceptable:

1. Successful login redirects to role dashboard.
2. Successful request creation redirects to request detail.
3. Successful offer submission redirects back to request detail.
4. Failed validation redirects back with errors.

Even when using redirects, error structure should be consistent in session flash data.

### Error Formatting

Controllers should translate application errors into user-safe responses.

Examples:

| Error Type | HTTP Status | Response Meaning |
| --- | --- | --- |
| Validation error | `422` | Input is malformed or incomplete |
| Authentication error | `401` | User is not logged in |
| Authorization error | `403` | User role is not allowed |
| Not found | `404` | Resource does not exist or is hidden |
| Business rule violation | `409` | Action conflicts with workflow state |
| Server/database failure | `500` | Unexpected backend failure |

### Request/Response Cycle In A Controller

Example mental model for `OfferController::submit`:

```text
1. Get authenticated provider ID.
2. Get validated request ID, price, and message.
3. Call OfferService::submitOffer.
4. Return success response or redirect.
```

Nothing in that cycle requires SQL in the controller.

---

## Service Layer Deep Dive

### Why The Service Layer Is The Brain

The service layer knows the project rules.

For this marketplace, services answer questions like:

1. Can this user create a request?
2. Can this provider submit an offer?
3. Can this offer be accepted?
4. Can this request move to the next state?
5. Should this action write to `audit_logs`?
6. Should this action write to `status_history`?
7. Does this operation need a database transaction?

### Business Rules

Business rules are rules that come from the marketplace domain, not from HTML or SQL.

Examples:

1. Only customers create service requests.
2. Only providers submit offers.
3. Only admins manage categories.
4. A provider can submit only one offer per request.
5. A request can have only one accepted offer.
6. A review is allowed only after completion.
7. Status must follow `Requested -> Negotiating -> Assigned -> Completed -> Reviewed`.

These rules belong in services.

### Workflow And State Machine Enforcement

The request lifecycle is:

```text
Requested -> Negotiating -> Assigned -> Completed -> Reviewed
```

Allowed transitions:

| From | To | Trigger | Service Owner |
| --- | --- | --- | --- |
| `Requested` | `Negotiating` | First valid offer submitted | `OfferService` |
| `Negotiating` | `Assigned` | Customer accepts offer | `OfferService` |
| `Assigned` | `Completed` | Assigned provider marks complete | `RequestService` |
| `Completed` | `Reviewed` | Customer submits review | `ReviewService` |

Blocked transitions:

1. `Requested -> Assigned` without accepted offer
2. `Requested -> Completed`
3. `Negotiating -> Reviewed`
4. `Assigned -> Reviewed`
5. `Completed -> Assigned`
6. `Reviewed -> Completed`

The service should check current state before changing it.

### Offer Acceptance Rules

Accepting an offer is not a simple update.

The service must coordinate:

1. Confirm authenticated user is the customer who owns the request.
2. Confirm offer exists.
3. Confirm offer belongs to the request.
4. Confirm request status is `Negotiating`.
5. Mark selected offer as accepted.
6. Reject or close competing offers if required by project behavior.
7. Update `service_requests.accepted_offer_id`.
8. Update request status to `Assigned`.
9. Record `status_history`.
10. Record `audit_logs`.

This should be treated as one business transaction.

### Role Restrictions

Even if middleware checks roles, services should still protect critical operations.

Why?

1. Services may later be called from CLI scripts or tests.
2. Middleware can be misconfigured on a route.
3. Defense in depth prevents accidental privilege escalation.

Examples:

1. `RequestService::createRequest` should require `customer`.
2. `OfferService::submitOffer` should require `provider`.
3. `AdminService::createCategory` should require `admin`.

### Audit Logging Triggers

Critical actions should write to `audit_logs`.

Examples:

1. User login
2. Request created
3. Offer submitted
4. Offer accepted
5. Request completed
6. Review submitted
7. Provider verified
8. Category created, updated, or deleted

Services should trigger audit logs because services understand the meaning of the action.

### Status History Triggers

Any request status change should write to `status_history`.

Examples:

1. `Requested -> Negotiating`
2. `Negotiating -> Assigned`
3. `Assigned -> Completed`
4. `Completed -> Reviewed`

Services should write status history at the same time they update request status.

### Transaction Thinking

Some actions require multiple database writes that must succeed or fail together.

Examples:

1. Accept offer
2. Mark request completed
3. Submit review
4. Verify provider with audit log

If offer acceptance updates the offer but fails to update the request status, the system becomes inconsistent. The service should coordinate transactions through repository/database support.

---

## Repository Layer Deep Dive

### What Repositories Do

Repositories are the only layer that talks directly to the database.

They should:

1. Receive clean parameters from services.
2. Prepare SQL statements.
3. Bind values safely.
4. Execute queries.
5. Return arrays, IDs, booleans, or simple result objects.

### Database Abstraction

Repositories hide SQL details from the rest of the app.

The service should say:

```text
RequestRepository::findById($requestId)
```

The service should not know:

1. Table names
2. Join syntax
3. PDO binding details
4. Index usage
5. SQL column ordering

### PDO Usage Philosophy

PDO should be used consistently:

1. Use prepared statements.
2. Bind user-provided values.
3. Enable exceptions for database errors.
4. Avoid string interpolation for values.
5. Keep connection creation centralized.

Prepared statement thinking:

```text
SQL template + bound values = safer query
```

Never build SQL like:

```text
"SELECT * FROM users WHERE email = '$email'"
```

Use placeholders and binding instead.

### CRUD Isolation

CRUD means:

1. Create
2. Read
3. Update
4. Delete

Repository methods should express operations clearly.

Examples:

| Repository | Method Ideas |
| --- | --- |
| `UserRepository` | `findByEmail`, `findById`, `create`, `verifyProvider` |
| `RequestRepository` | `create`, `findById`, `findActiveByCategory`, `updateStatus`, `setAcceptedOffer` |
| `OfferRepository` | `create`, `findById`, `findByRequest`, `findByProviderAndRequest`, `accept`, `rejectCompetingOffers` |
| `ReviewRepository` | `create`, `existsForRequest`, `findByProvider` |
| `AuditLogRepository` | `record`, `findRecent`, `findByUser` |
| `StatusHistoryRepository` | `record`, `findByRequest` |

### Query Centralization

If a query is needed in multiple places, it should exist once in a repository method.

Example:

Provider dashboard and marketplace browsing may both need active requests by category. That logic should be centralized in `RequestRepository`.

### Why Repositories Protect Maintainability

Suppose the `service_requests` table changes from `description` to `details`.

If SQL is scattered in controllers, you must hunt through the project.

If SQL is centralized, you update `RequestRepository`.

That is why repositories matter.

---

## Middleware System

### What Middleware Is

Middleware runs before the controller.

It can:

1. Allow the request to continue.
2. Stop the request with an error.
3. Attach validated data or auth context.
4. Redirect the user.

Middleware is ideal for repeated request gates.

### Authentication Middleware

Authentication asks:

```text
Who is the user?
```

It checks whether the session or token identifies a logged-in user.

For this project, session-based auth is likely enough for browser flows.

Authentication middleware should:

1. Start or use a secure session.
2. Check for `user_id`.
3. Load or confirm user context when needed.
4. Reject missing sessions with `401` or redirect to login.

### Session Or Token Checks

Session checks are common in PHP web apps:

```text
$_SESSION['user_id']
$_SESSION['role']
```

Tokens are common for APIs.

This project can begin with sessions, but the architecture should not spread `$_SESSION` everywhere. Keep auth access centralized through middleware and helpers.

### Role Middleware

Role middleware asks:

```text
Is this user allowed to access this route?
```

Required roles:

1. `customer`
2. `provider`
3. `admin`

Examples:

| Route | Allowed Role |
| --- | --- |
| `/requests/create` | `customer` |
| `/offers/submit` | `provider` |
| `/reviews/submit` | `customer` |
| `/admin/categories` | `admin` |

Role middleware must block unauthorized roles.

### Validation Middleware

Validation middleware asks:

```text
Is the request input shaped correctly?
```

It should:

1. Select the correct validator module.
2. Run the validator.
3. Stop the request if errors exist.
4. Pass validated data to the controller.

Important distinction:

Validation middleware handles input validation. Services handle business validation.

Example:

Input validation:

1. `price` is required.
2. `price` is numeric.
3. `message` is not too long.

Business validation:

1. Request exists.
2. Request is open for offers.
3. Provider has not already submitted an offer.

### Request Interception

Middleware intercepts requests before expensive or sensitive work happens.

Correct order:

```text
auth -> role -> validation -> controller -> service
```

Why this order:

1. Do not validate protected data for anonymous users unnecessarily.
2. Do not run controller logic for the wrong role.
3. Do not call services with malformed input.

### Security Gates

Use middleware for:

1. Authentication
2. Role access
3. CSRF verification
4. Validation
5. Content type checks for JSON endpoints
6. Upload size/type checks

Do not rely only on frontend JavaScript validation. The backend must reject bad input.

---

## Validation Architecture

### Required Fields

Required field examples:

| Use Case | Required Fields |
| --- | --- |
| Login | `email`, `password` |
| Register | `name`, `email`, `password`, `role`, `phone`, `location` |
| Create request | `category_id`, `description`, `preferred_date`, `location` |
| Submit offer | `request_id`, `price`, `message` |
| Submit review | `request_id`, `rating` |
| Create category | `name` |

### Sanitization

Validation and sanitization are related but different.

Validation asks:

```text
Is this input acceptable?
```

Sanitization asks:

```text
How do we make this value safe for a specific use?
```

Important:

1. Use prepared statements for SQL safety.
2. Use escaping like `htmlspecialchars` for HTML output safety.
3. Do not rely on one sanitization step to solve every security problem.

### Business Validation Vs Input Validation

Input validation belongs in validators.

Examples:

1. Email format
2. Password minimum length
3. Price is positive
4. Rating is between 1 and 5
5. UUID-like ID is present

Business validation belongs in services.

Examples:

1. Email is not already registered
2. Request belongs to this customer
3. Provider can offer on this request
4. Request is in `Completed` state before review
5. Category exists and is active

### Reusable Validator Modules

Each validator module should serve one feature area.

Recommended modules:

1. `auth_validator.php`
2. `request_validator.php`
3. `offer_validator.php`
4. `review_validator.php`
5. `admin_validator.php`

Validators should return structured errors, not echo output.

Example error concept:

```text
field: price
message: Price must be a positive number.
```

### Error Standardization

Validation errors should look the same across the project.

Recommended shape:

```text
success: false
error:
  type: validation_error
  message: Please correct the highlighted fields.
  fields:
    price: Price must be a positive number.
```

This allows controllers, frontend scripts, and views to handle errors predictably.

---

## Response And Error Handling Standard

### Standard Success Response Shape

For JSON-style responses:

```text
success: true
message: Human-readable success message
data: Response payload
```

Examples:

1. `Request created successfully.`
2. `Offer submitted successfully.`
3. `Offer accepted successfully.`
4. `Review submitted successfully.`
5. `Category created successfully.`

### Standard Error Response Shape

For JSON-style errors:

```text
success: false
error:
  type: error_type
  message: Human-readable safe message
  fields: optional validation field errors
```

### Validation Errors

Use when input is missing or malformed.

HTTP status:

```text
422 Unprocessable Entity
```

Example:

```text
type: validation_error
message: Please correct the highlighted fields.
```

### Authorization Errors

Use when logged-in user has the wrong role or does not own the resource.

HTTP status:

```text
403 Forbidden
```

Example:

```text
type: authorization_error
message: You are not allowed to perform this action.
```

### Authentication Errors

Use when no valid login session exists.

HTTP status:

```text
401 Unauthorized
```

Example:

```text
type: authentication_error
message: Please log in to continue.
```

### Business Rule Violations

Use when the request is well-formed but conflicts with marketplace rules.

HTTP status:

```text
409 Conflict
```

Examples:

1. Trying to review a request before completion
2. Trying to accept an offer on an already assigned request
3. Provider trying to submit a duplicate offer
4. Attempting an invalid state transition

### Database Failures

Use safe generic messages for users.

HTTP status:

```text
500 Internal Server Error
```

User-safe message:

```text
Something went wrong. Please try again.
```

Internal details should go to logs, not the browser.

### Consistent Response Rules

1. Controllers should use response helpers.
2. Middleware should use the same error shape as controllers.
3. Services should throw or return structured application errors.
4. Repositories should not format HTTP responses.
5. Raw PDO exception messages should not be shown to users.

---

## Project Workflow Walkthroughs

### 1. Customer Creates Request

Route:

```text
POST /requests/create
```

Middleware:

1. `auth`
2. `role:customer`
3. `validate:request_create`

Controller:

```text
RequestController::create
```

Controller job:

1. Read validated `category_id`, `description`, `preferred_date`, `location`.
2. Read authenticated `customer_id`.
3. Call `RequestService::createRequest`.
4. Return success response or redirect.

Service:

```text
RequestService::createRequest
```

Service job:

1. Confirm user role is `customer`.
2. Confirm category exists.
3. Create request with status `Requested`.
4. Write audit log: request created.
5. Optionally write initial status history entry.

Repository:

1. `CategoryRepository::findById` if category repository exists
2. `RequestRepository::create`
3. `AuditLogRepository::record`
4. `StatusHistoryRepository::record`

DB:

1. Insert into `service_requests`.
2. Insert into `audit_logs`.
3. Insert into `status_history` if initial state is recorded.

Audit log:

```text
action: request_created
user_id: customer_id
target: request_id
```

Status history:

```text
request_id: request_id
from_status: null
to_status: Requested
changed_by: customer_id
```

### 2. Provider Submits Offer

Route:

```text
POST /offers/submit
```

Middleware:

1. `auth`
2. `role:provider`
3. `validate:offer_submit`

Controller:

```text
OfferController::submit
```

Controller job:

1. Read validated `request_id`, `price`, `message`.
2. Read authenticated `provider_id`.
3. Call `OfferService::submitOffer`.
4. Return success response.

Service:

```text
OfferService::submitOffer
```

Service job:

1. Confirm user role is `provider`.
2. Load request.
3. Confirm request status is `Requested` or `Negotiating`.
4. Confirm provider is not the request owner.
5. Confirm provider has not already offered on this request.
6. Create offer with status such as `pending`.
7. If request was `Requested`, update status to `Negotiating`.
8. Record status history for `Requested -> Negotiating`.
9. Record audit log.

Repository:

1. `RequestRepository::findById`
2. `OfferRepository::findByProviderAndRequest`
3. `OfferRepository::create`
4. `RequestRepository::updateStatus`
5. `StatusHistoryRepository::record`
6. `AuditLogRepository::record`

DB:

1. Insert into `offers`.
2. Update `service_requests.status`.
3. Insert into `status_history`.
4. Insert into `audit_logs`.

Audit log:

```text
action: offer_submitted
user_id: provider_id
target: offer_id
```

Status history:

```text
from_status: Requested
to_status: Negotiating
```

### 3. Customer Accepts Offer

Route:

```text
PATCH /offers/{offer_id}/accept
```

Middleware:

1. `auth`
2. `role:customer`
3. `validate:offer_accept`

Controller:

```text
OfferController::accept
```

Controller job:

1. Read validated `offer_id`.
2. Read authenticated `customer_id`.
3. Call `OfferService::acceptOffer`.
4. Return success response.

Service:

```text
OfferService::acceptOffer
```

Service job:

1. Confirm user role is `customer`.
2. Load offer.
3. Load related request.
4. Confirm customer owns the request.
5. Confirm request status is `Negotiating`.
6. Confirm offer is acceptable.
7. Mark selected offer `accepted`.
8. Mark competing offers rejected or closed according to project rules.
9. Set `service_requests.accepted_offer_id`.
10. Update request status to `Assigned`.
11. Record status history.
12. Record audit log.

Repository:

1. `OfferRepository::findById`
2. `RequestRepository::findById`
3. `OfferRepository::accept`
4. `OfferRepository::rejectCompetingOffers`
5. `RequestRepository::setAcceptedOffer`
6. `RequestRepository::updateStatus`
7. `StatusHistoryRepository::record`
8. `AuditLogRepository::record`

DB:

1. Update `offers.status`.
2. Update `service_requests.accepted_offer_id`.
3. Update `service_requests.status`.
4. Insert into `status_history`.
5. Insert into `audit_logs`.

Audit log:

```text
action: offer_accepted
user_id: customer_id
target: offer_id
```

Status history:

```text
from_status: Negotiating
to_status: Assigned
```

### 4. Review Submission

Route:

```text
POST /reviews/submit
```

Middleware:

1. `auth`
2. `role:customer`
3. `validate:review_submit`

Controller:

```text
ReviewController::submit
```

Controller job:

1. Read validated `request_id`, `rating`, `comment`.
2. Read authenticated `customer_id`.
3. Call `ReviewService::submitReview`.
4. Return success response.

Service:

```text
ReviewService::submitReview
```

Service job:

1. Confirm user role is `customer`.
2. Load request.
3. Confirm request belongs to customer.
4. Confirm request status is `Completed`.
5. Confirm request has an accepted provider.
6. Confirm no review already exists for the request.
7. Create review.
8. Update provider rating summary if stored on `users`.
9. Update request status to `Reviewed`.
10. Record status history.
11. Record audit log.

Repository:

1. `RequestRepository::findById`
2. `ReviewRepository::existsForRequest`
3. `ReviewRepository::create`
4. `UserRepository::updateProviderRating`
5. `RequestRepository::updateStatus`
6. `StatusHistoryRepository::record`
7. `AuditLogRepository::record`

DB:

1. Insert into `reviews`.
2. Update `users.rating_average` and `users.total_reviews` if implemented.
3. Update `service_requests.status`.
4. Insert into `status_history`.
5. Insert into `audit_logs`.

Audit log:

```text
action: review_submitted
user_id: customer_id
target: review_id
```

Status history:

```text
from_status: Completed
to_status: Reviewed
```

### 5. Admin Category Management

Route:

```text
GET /admin/categories
POST /admin/categories
PATCH /admin/categories/{category_id}
```

Middleware:

1. `auth`
2. `role:admin`
3. `validate:category` for create/update

Controller:

```text
AdminController::categories
AdminController::createCategory
AdminController::updateCategory
```

Controller job:

1. Read validated category input.
2. Read authenticated admin user.
3. Call `AdminService`.
4. Return response or redirect.

Service:

```text
AdminService::createCategory
AdminService::updateCategory
```

Service job:

1. Confirm role is `admin`.
2. Check category uniqueness if required.
3. Create or update category.
4. Record audit log.

Repository:

1. `CategoryRepository::findByName`
2. `CategoryRepository::create`
3. `CategoryRepository::update`
4. `AuditLogRepository::record`

DB:

1. Insert or update `service_categories`.
2. Insert into `audit_logs`.

Audit log:

```text
action: category_created
user_id: admin_id
target: category_id
```

Status history:

Not needed for categories because `status_history` belongs to request lifecycle transitions.

---

## Security And Professional Practices

### SQL Injection Prevention

SQL injection happens when user input is treated as SQL code.

Prevention rules:

1. Use PDO prepared statements.
2. Bind values instead of concatenating strings.
3. Validate route parameters.
4. Whitelist sortable/filterable columns if dynamic ordering is needed.
5. Keep all SQL in repositories.

### Access Control

Access control must exist at multiple levels:

1. Middleware blocks wrong roles early.
2. Services verify ownership and workflow permissions.
3. Repositories do not expose unrestricted broad operations unless services truly need them.

Examples:

1. A customer can only accept offers on their own request.
2. A provider can only complete a request assigned to them.
3. An admin can manage categories and provider verification.

### Input Trust Boundaries

Never trust:

1. `$_GET`
2. `$_POST`
3. JSON body values
4. File uploads
5. Cookies
6. Session role values without server-side verification
7. Hidden form fields

The browser is outside the trust boundary. The backend must verify everything important.

### CSRF Basics

CSRF means Cross-Site Request Forgery. It tricks a logged-in user into submitting an unwanted request.

Protect state-changing browser routes:

1. `POST /requests/create`
2. `POST /offers/submit`
3. `PATCH /offers/{offer_id}/accept`
4. `POST /reviews/submit`
5. Admin create/update/delete routes

Basic approach:

1. Generate a CSRF token in the session.
2. Include it in forms.
3. Middleware verifies submitted token.
4. Reject missing or invalid tokens.

### Session Security

Recommended practices:

1. Regenerate session ID after login.
2. Store only necessary user data in session.
3. Use secure cookie settings in production.
4. Destroy session on logout.
5. Do not store password hashes in session.

### Error Leakage Prevention

Never show users:

1. Raw SQL errors
2. Stack traces
3. File paths
4. Database credentials
5. Internal class names when unnecessary

Log detailed errors internally. Return safe messages externally.

---

## Common Beginner Mistakes

### Fat Controllers

Bad pattern:

```text
Controller reads POST, checks role, runs SQL, changes status, writes audit log, echoes JSON.
```

Correct pattern:

```text
Controller reads validated input, calls service, returns response.
```

### Duplicate Validation

Bad pattern:

```text
AuthController, RequestController, and OfferController each manually validate email or IDs differently.
```

Correct pattern:

```text
Validator modules define reusable rules and validation middleware applies them.
```

### SQL Everywhere

Bad pattern:

```text
SQL in route files, controllers, services, and views.
```

Correct pattern:

```text
SQL only in repositories.
```

### Broken State Transitions

Bad pattern:

```text
Any controller can update request status to any value.
```

Correct pattern:

```text
Services enforce Requested -> Negotiating -> Assigned -> Completed -> Reviewed.
```

### Hardcoded Roles Everywhere

Bad pattern:

```text
Every controller has its own if statement for customer/provider/admin.
```

Correct pattern:

```text
Role middleware handles route access and services verify critical role rules.
```

### Missing Authorization

Authentication is not authorization.

Logged in means:

```text
We know who the user is.
```

Authorized means:

```text
This user is allowed to do this action on this resource.
```

Example:

A customer may be logged in, but that does not mean they can accept an offer on another customer's request.

### Repository Business Logic

Bad pattern:

```text
OfferRepository decides whether Negotiating can become Assigned.
```

Correct pattern:

```text
OfferService decides; OfferRepository updates when told.
```

### Validation As Security Theater

Frontend validation improves user experience. It does not secure the backend.

The backend must validate again.

---

## Practical Implementation Roadmap

### Step 1: Build Or Confirm The Router

Goal:

Create a central router that maps method + URI to controller action.

Checklist:

1. Detect HTTP method.
2. Detect URI path.
3. Support route parameters.
4. Support middleware lists.
5. Dispatch to controller action.
6. Return `404` for missing routes.
7. Keep route definitions explicit.

Beginner test:

Can you open one route file and answer:

```text
What handles POST /offers/submit?
```

If yes, routing is understandable.

### Step 2: Define Route Groups

Create clear groups:

1. Auth routes
2. Request routes
3. Offer routes
4. Review routes
5. Admin routes

Checklist:

1. Every route has method.
2. Every route has URI.
3. Every route maps to one controller action.
4. Every protected route has auth middleware.
5. Every role-specific route has role middleware.
6. Every input route has validation middleware.

### Step 3: Build Base Controller Behavior

Goal:

Controllers should share response formatting behavior.

Checklist:

1. Standard success response.
2. Standard error response.
3. Redirect helper for browser flows.
4. Safe extraction of validated data.
5. No SQL.
6. No direct repository calls.

### Step 4: Build Validators

Goal:

Reject invalid input before services run.

Build validators for:

1. Login
2. Registration
3. Request creation
4. Offer submission
5. Offer acceptance/countering
6. Review submission
7. Category creation/update

Checklist:

1. Required fields.
2. Data types.
3. Length limits.
4. Enum values.
5. Numeric ranges.
6. File constraints if uploads are added.
7. Structured error output.

### Step 5: Build Middleware

Goal:

Create reusable request gates.

Middleware needed:

1. Authentication middleware
2. Role middleware
3. Validation middleware
4. CSRF middleware for forms

Checklist:

1. Auth blocks anonymous users.
2. Role middleware blocks unauthorized roles.
3. Validation middleware blocks invalid input.
4. Errors use standard response shape.
5. Middleware runs before controllers.

### Step 6: Build Services

Goal:

Centralize marketplace rules.

Services needed:

1. `AuthService`
2. `RequestService`
3. `OfferService`
4. `ReviewService`
5. `AdminService`

Checklist:

1. Services enforce role rules.
2. Services enforce ownership rules.
3. Services enforce status transitions.
4. Services trigger audit logs.
5. Services trigger status history for request status changes.
6. Services coordinate transactions for multi-write workflows.
7. Services call repositories only.

### Step 7: Build Repositories

Goal:

Centralize SQL and persistence.

Repositories needed:

1. `UserRepository`
2. `RequestRepository`
3. `OfferRepository`
4. `ReviewRepository`
5. `AuditLogRepository`
6. `StatusHistoryRepository`
7. `CategoryRepository` if not already represented

Checklist:

1. All SQL uses PDO prepared statements.
2. No controller has SQL.
3. No route has SQL.
4. Repository methods are named by intent.
5. Query results are predictable arrays or simple objects.
6. Database exceptions are logged and handled safely.

### Step 8: Connect Everything

Goal:

Every route should work through the full chain.

Acceptance test chain:

```text
Route -> Middleware -> Controller -> Service -> Repository -> DB -> Response
```

For every route, verify:

1. It maps to exactly one controller action.
2. Middleware is correct.
3. Controller calls service only.
4. Service calls repositories only.
5. Invalid input stops before service.
6. Unauthorized roles are blocked.
7. SQL stays in repositories.
8. Business state checks stay in services.
9. Responses have consistent shape.

---

## Practice Tasks

### Route Design Drills

1. Design routes for provider browsing active requests by category.
2. Design routes for a customer viewing all offers on one request.
3. Design routes for an admin verifying a provider.
4. Design a route for marking assigned work completed.
5. For each route, write method, URI, middleware, and controller action.

Expected thinking:

```text
Who is allowed?
What input is required?
Which controller action handles it?
Which service owns the rule?
```

### Controller Responsibility Exercises

For each action, list what belongs in the controller and what does not:

1. Login
2. Create request
3. Submit offer
4. Accept offer
5. Submit review

Example answer pattern:

```text
Controller responsibility:
  Read validated input.
  Read authenticated user ID.
  Call service.
  Return response.

Not controller responsibility:
  SQL.
  Password hashing details.
  State transition checks.
  Audit log decisions.
```

### Service Logic Design Tasks

Design service rules for:

1. `RequestService::createRequest`
2. `OfferService::submitOffer`
3. `OfferService::acceptOffer`
4. `RequestService::markCompleted`
5. `ReviewService::submitReview`

For each, answer:

1. Which role is allowed?
2. Which resource must be loaded?
3. Which ownership check is needed?
4. Which current status is required?
5. Which status transition happens?
6. Which audit log is written?
7. Which status history entry is written?

### Validation Exercises

Write validation rules for:

1. Registration role must be `customer` or `provider`, not `admin`.
2. Offer price must be positive.
3. Review rating must be between 1 and 5.
4. Request description must not be empty.
5. Category ID must be present when creating a request.
6. Completion photo must be an allowed image type if uploads are enabled.

Then decide which checks are not validator checks:

1. Category exists.
2. Provider is verified.
3. Request is assigned to this provider.
4. Review is allowed only after completion.

Those belong in services.

### Architecture Debugging Scenarios

Scenario 1:

A provider can submit two offers for the same request.

Where to investigate:

1. `OfferService::submitOffer`
2. `OfferRepository::findByProviderAndRequest`
3. Database uniqueness constraints if added

Scenario 2:

A customer can review a request still in `Assigned`.

Where to investigate:

1. `ReviewService::submitReview`
2. State transition checks
3. Review route middleware role

Scenario 3:

Admin category creation returns a different error format than other forms.

Where to investigate:

1. `AdminController`
2. Response helper
3. Validation middleware

Scenario 4:

SQL injection is discovered in request browsing filters.

Where to investigate:

1. `RequestRepository`
2. Dynamic filter query building
3. Validator rules for filters

Scenario 5:

Offer acceptance updates the offer but not the request status.

Where to investigate:

1. `OfferService::acceptOffer`
2. Transaction handling
3. `RequestRepository::setAcceptedOffer`
4. `RequestRepository::updateStatus`
5. `StatusHistoryRepository::record`

---

## Phase 3 Acceptance Checklist

Use this checklist before considering Phase 3 complete.

### Routing

1. Route groups exist for `auth`, `requests`, `offers`, `reviews`, and `admin`.
2. Every route maps to exactly one controller action.
3. Route definitions are explicit and readable.
4. Protected routes include middleware.
5. Route parameters are validated.

### Controllers

1. Controllers parse request data.
2. Controllers call services only.
3. Controllers do not contain SQL.
4. Controllers do not enforce complex state transitions.
5. Controllers return consistent responses or redirects.

### Services

1. Services contain business logic.
2. Services enforce role and ownership rules.
3. Services enforce `Requested -> Negotiating -> Assigned -> Completed -> Reviewed`.
4. Services trigger `audit_logs`.
5. Services trigger `status_history`.
6. Services call repositories only.

### Repositories

1. All SQL lives in repositories.
2. PDO prepared statements are used.
3. Repository methods are reusable.
4. Repositories do not read `$_POST`, `$_GET`, or `$_SESSION`.
5. Repositories do not format HTTP responses.

### Middleware

1. Authentication middleware blocks anonymous users.
2. Role middleware blocks unauthorized roles.
3. Validation middleware blocks invalid input before services.
4. Middleware errors use the standard response format.

### Validators

1. Reusable validation rules live in validator modules.
2. Login, register, request, offer, review, and admin category inputs are covered.
3. Validators return structured errors.
4. Validators do not perform database workflow decisions.

### Project Constraints

1. Customer/provider/admin separation is enforced.
2. Category browsing is supported.
3. Provider workflows are supported.
4. Audit logging is integrated.
5. Status history is integrated.
6. The request state machine cannot be bypassed.

---

## Final Mental Model

Think like a backend engineer by asking these questions for every feature:

1. What route receives this request?
2. Which middleware protects it?
3. Which controller action handles HTTP input/output?
4. Which service owns the business rule?
5. Which repository owns the SQL?
6. Which validator blocks malformed input?
7. Which role is allowed?
8. Which state transition is allowed?
9. Should this action write `audit_logs`?
10. Should this action write `status_history`?
11. What safe response should the user receive?

The professional backend mindset is not "Where can I quickly put this PHP code?"

The professional backend mindset is:

```text
Where does this responsibility belong?
```

For Phase 3, the answer must follow this architecture:

```text
Browser
  -> Route
  -> Middleware
  -> Controller
  -> Service
  -> Repository
  -> Database
  -> Repository
  -> Service
  -> Controller
  -> Response
  -> Browser
```

If you can design every feature through that path, you are no longer writing random procedural PHP. You are building a maintainable backend system.
