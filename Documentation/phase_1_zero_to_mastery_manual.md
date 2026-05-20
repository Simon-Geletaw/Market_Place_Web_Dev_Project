# Phase 1 Zero-to-Mastery Implementation Manual
## PHP Fundamentals + HTTP Basics

This manual turns `requirements/phase_1_requirements.md` into a working PHP foundation for the marketplace backend.

---

## 1. The Execution Lifecycle

### The Request Journey

1. A browser or API client sends an HTTP request, for example `GET /api/health`.
2. Apache receives the request inside XAMPP.
3. `backend/public/.htaccess` rewrites non-file requests to `backend/public/index.php`.
4. PHP starts a fresh process/request context and executes `index.php` from top to bottom.
5. `index.php` loads helper, middleware, controller, and route files with `require_once`.
6. `route_key()` combines the HTTP method and path, such as `GET /api/health`.
7. PHP checks that key inside the route table from `backend/app/routes/api.php`.
8. If the route exists, PHP creates the controller object and calls the controller method.
9. The controller returns a standard response array.
10. `send_json()` sets the HTTP status code, sets the `Content-Type` header, converts the array to JSON, prints it, and the script exits.

### State & Memory

PHP is stateless because every HTTP request starts with a clean memory space. Variables from one request do not automatically exist in the next request.

During one request, PHP stores data in memory as arrays, strings, objects, and scalars:

- `$_SERVER` contains request metadata such as method and URI.
- `$_GET` contains query string values.
- `php://input` contains the raw request body.
- `$routes` contains the current route map.
- `$controller` contains the current controller object.

This phase does not permanently solve state yet. It prepares the foundation. Later phases solve state with sessions for login and MySQL for durable marketplace data.

---

## 2. The Architectural Map

### File Tree

```text
backend/
  app/
    controllers/
      PayloadController.php        [New]
      StatusController.php         [Modified]
    helpers/
      request.php                  [Modified]
      response.php                 [Modified]
    middleware/
      validation.php               [Modified]
    routes/
      api.php                      [New]
  config/
    app.php                        [New]
  public/
    .htaccess                      [New]
    index.php                      [Modified]
Documentation/
  phase_1_zero_to_mastery_manual.md [New]
```

### The Bootstrap Logic

`public/index.php` is the front controller. Every backend request starts there.

Dependency flow:

```text
Apache
  -> public/.htaccess
  -> public/index.php
     -> helpers/response.php
     -> helpers/request.php
     -> middleware/validation.php
     -> controllers/StatusController.php
     -> controllers/PayloadController.php
     -> routes/api.php
```

This project currently uses `require_once`, not namespaces. That means PHP loads files manually and classes/functions become available in the global PHP runtime for that request.

Use `require_once` when a file must be loaded exactly one time. If the same helper is loaded twice, PHP avoids redeclaring the same function or class.

---

## 3. The Syntax Laboratory

### `backend/public/index.php`

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/response.php';
require_once __DIR__ . '/../app/helpers/request.php';
require_once __DIR__ . '/../app/middleware/validation.php';
require_once __DIR__ . '/../app/controllers/StatusController.php';
require_once __DIR__ . '/../app/controllers/PayloadController.php';

$routes = require __DIR__ . '/../app/routes/api.php';

try {
    $key = route_key();

    if (!array_key_exists($key, $routes)) {
        send_json(error_response('Not Found', ['route' => $key], 404));
        exit;
    }

    [$className, $methodName] = $routes[$key];
    $controller = new $className();

    send_json($controller->$methodName());
    exit;
} catch (InvalidArgumentException $exception) {
    send_json(error_response($exception->getMessage(), [], 400));
    exit;
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    send_json(error_response('Internal Server Error', [], 500));
    exit;
}
```

| Syntax/Symbol | The Meaning | The Engine's Job | The Newbie Trap |
|---|---|---|---|
| `<?php` | Start PHP mode | Begin interpreting PHP code | Forgetting it makes PHP print code as text |
| `declare(strict_types=1);` | Enforce stricter type behavior | Apply strict scalar type checks in this file | Thinking it affects every file globally |
| `require_once` | Load another PHP file one time | Read and execute the target file if not already loaded | Using a wrong relative path |
| `__DIR__` | Current file directory | Resolve an absolute directory path | Hardcoding machine-specific paths |
| `$routes = require ...` | Load route array | Execute `api.php` and assign its returned array | Forgetting `api.php` must `return` something |
| `try` | Start protected block | Catch exceptions thrown inside | Catching errors but hiding all useful logs |
| `route_key()` | Build method/path route id | Call helper function | Defining a route as `/api/health/` while the helper normalizes it |
| `array_key_exists` | Check route exists | Look for exact key in array | Using wrong HTTP method, such as POST instead of GET |
| `[$className, $methodName]` | Array destructuring | Assign first and second values to variables | Route array must contain exactly class and method |
| `new $className()` | Create controller object dynamically | Instantiate the class named by the string | Forgetting to require the controller file |
| `$controller->$methodName()` | Call method dynamically | Execute method stored in `$methodName` | Misspelling the method in route table |
| `send_json(...)` | Send final API response | Set headers/status and echo JSON | Echoing extra text before JSON |
| `exit` | Stop script now | Terminate request execution | Continuing after response can produce duplicate output |
| `catch (InvalidArgumentException...)` | Handle bad input | Convert input parsing error to 400 JSON | Returning 500 for user mistakes |
| `catch (Throwable...)` | Handle unexpected failure | Log internal error and return safe 500 | Showing raw exception details to users |

### `backend/app/routes/api.php`

```php
<?php

declare(strict_types=1);

return [
    'GET /ok' => [StatusController::class, 'ok'],
    'GET /api/health' => [StatusController::class, 'ok'],
    'POST /api/validate-demo' => [PayloadController::class, 'validateDemo'],
];
```

| Syntax/Symbol | The Meaning | The Engine's Job | The Newbie Trap |
|---|---|---|---|
| `return [` | Return an array from the file | Give `index.php` the route map | Writing `$routes = [...]` here without returning it |
| `'GET /api/health'` | Route key | Match exact method and path | Routes are case and method sensitive |
| `StatusController::class` | Full class name string | Resolve class name safely | Typing `'StatusController'` manually can drift during refactors |
| `['Class', 'method']` | Controller action pair | Tell dispatcher what to instantiate and call | Pointing to a method that does not exist |

### `backend/app/helpers/request.php`

```php
<?php

declare(strict_types=1);

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = '/' . trim($path, '/');

    return $path === '/' ? '/' : rtrim($path, '/');
}

function request_query(): array
{
    return $_GET;
}

function request_json_body(): array
{
    $rawBody = file_get_contents('php://input');

    if ($rawBody === false || trim($rawBody) === '') {
        return [];
    }

    $decoded = json_decode($rawBody, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        throw new InvalidArgumentException('Invalid JSON body.');
    }

    return $decoded;
}

function request_input(): array
{
    return [
        'query' => request_query(),
        'body' => request_json_body(),
    ];
}

function route_key(): string
{
    return request_method() . ' ' . request_path();
}
```

| Syntax/Symbol | The Meaning | The Engine's Job | The Newbie Trap |
|---|---|---|---|
| `function name(): string` | Function returns string | Enforce declared return type | Returning `null` causes a TypeError |
| `$_SERVER` | Server/request metadata | Read values provided by web server | Assuming keys always exist |
| `??` | Fallback operator | Use right value if left is missing/null | Confusing it with `?:` |
| `strtoupper` | Uppercase string | Normalize HTTP method | Routes fail if method casing differs |
| `parse_url(..., PHP_URL_PATH)` | Extract path only | Remove query string from URI | Matching `/api/health?x=1` as a different route |
| `trim($path, '/')` | Remove slashes at both ends | Normalize path | Accidentally removing slashes from the middle is not what trim does |
| `$_GET` | Query parameters | Return parsed query string array | Using it for JSON body data |
| `file_get_contents('php://input')` | Read raw HTTP body | Fetch request payload stream | Reading it multiple times in advanced cases |
| `json_decode($rawBody, true)` | Convert JSON to array | Parse JSON string | Forgetting `true` returns objects instead of arrays |
| `json_last_error()` | Check parse result | Detect invalid JSON | Trusting `json_decode` without checking |
| `throw new InvalidArgumentException` | Signal bad input | Jump to matching catch block | Throwing raw messages for secrets |
| `.` | String concatenation | Join method, space, and path | Using `+` like JavaScript |

### `backend/app/helpers/response.php`

```php
<?php

declare(strict_types=1);

function json_response(bool $success, string $message, array $data = [], int $statusCode = 200): array
{
    return [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'status_code' => $statusCode,
    ];
}

function send_json(array $response): void
{
    $statusCode = (int) ($response['status_code'] ?? 200);

    if (PHP_SAPI !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
    }

    echo json_encode($response, JSON_UNESCAPED_SLASHES);
}

function success_response(string $message, array $data = [], int $statusCode = 200): array
{
    return json_response(true, $message, $data, $statusCode);
}

function error_response(string $message, array $data = [], int $statusCode = 400): array
{
    return json_response(false, $message, $data, $statusCode);
}
```

| Syntax/Symbol | The Meaning | The Engine's Job | The Newbie Trap |
|---|---|---|---|
| `bool`, `string`, `array`, `int` | Type declarations | Validate incoming arguments | Passing `"200"` instead of `200` under strict types |
| `= []` | Default parameter value | Use empty array if omitted | Mutable default problem exists in some languages, but PHP arrays here are safe |
| `=>` | Array key assignment | Build associative array | Confusing it with `->` |
| `(int)` | Cast to integer | Convert status code to number | Casting invalid strings gives `0` |
| `PHP_SAPI` | PHP runtime mode | Detect CLI vs Apache/FPM | Sending headers during CLI tests |
| `header(...)` | Set HTTP response header | Tell client response is JSON | Calling after output causes header warnings |
| `http_response_code` | Set HTTP status | Make response 200/400/404/500 | Returning error JSON with HTTP 200 |
| `json_encode` | Convert PHP array to JSON | Serialize response for client | Encoding can fail on invalid data types |
| `JSON_UNESCAPED_SLASHES` | Cleaner JSON URLs/paths | Avoid escaping `/` | Not required, just improves readability |

### `backend/app/middleware/validation.php`

```php
<?php

declare(strict_types=1);

function validate_payload(array $data, array $rules): array
{
    $errors = [];

    foreach ($rules as $field => $ruleList) {
        $rulesForField = is_array($ruleList) ? $ruleList : explode('|', (string) $ruleList);
        $value = $data[$field] ?? null;

        if (in_array('required', $rulesForField, true) && ($value === null || $value === '')) {
            $errors[$field][] = 'The ' . $field . ' field is required.';
            continue;
        }

        if ($value !== null && in_array('string', $rulesForField, true) && !is_string($value)) {
            $errors[$field][] = 'The ' . $field . ' field must be a string.';
        }
    }

    return $errors;
}
```

| Syntax/Symbol | The Meaning | The Engine's Job | The Newbie Trap |
|---|---|---|---|
| `foreach` | Loop over rules | Visit each field/rule pair | Modifying the array while looping |
| `$field => $ruleList` | Key and value | Assign field name and its rules | Forgetting associative array keys matter |
| `is_array` | Check type | Support both array and pipe string rules | Assuming all input has the expected type |
| `explode('|', ...)` | Split string into array | Convert `required|string` into rules | Extra spaces become part of rule names |
| `$data[$field] ?? null` | Read optional input | Avoid undefined index notice | Treating missing and null as different without intention |
| `in_array(..., true)` | Strict search | Avoid loose type bugs | Omitting `true` can create strange matches |
| `===` | Strict equality | Compare value and type | Using `==` hides type mistakes |
| `$errors[$field][]` | Append field error | Create nested list of messages | Forgetting `[]` overwrites previous errors |
| `continue` | Skip rest of loop | Avoid checking more rules after required fails | Continuing too early can hide useful errors |
| `!is_string` | Not a string | Enforce expected payload type | Accepting arrays where text is expected |

### `backend/app/controllers/StatusController.php`

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers/response.php';

final class StatusController
{
    public function ok(): array
    {
        return success_response('OK', [
            'service' => 'marketplace-backend',
            'php_version' => PHP_VERSION,
        ]);
    }
}
```

| Syntax/Symbol | The Meaning | The Engine's Job | The Newbie Trap |
|---|---|---|---|
| `final class` | Class cannot be extended | Define a locked controller class | Using inheritance before you need it |
| `public function` | Callable method | Allow dispatcher to call it | Making route method private |
| `$this` | Current object | Not used here, but available inside methods | Trying to use `$this` outside a class |
| `PHP_VERSION` | Current PHP version | Return runtime version string | Exposing too much version info in public production APIs |

### `backend/app/controllers/PayloadController.php`

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers/request.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middleware/validation.php';

final class PayloadController
{
    public function validateDemo(): array
    {
        $input = request_input();
        $body = $input['body'];
        $errors = validate_payload($body, [
            'name' => ['required', 'string'],
        ]);

        if ($errors !== []) {
            return error_response('Validation failed.', ['errors' => $errors], 422);
        }

        return success_response('Payload accepted.', [
            'query' => $input['query'],
            'body' => $body,
        ]);
    }
}
```

| Syntax/Symbol | The Meaning | The Engine's Job | The Newbie Trap |
|---|---|---|---|
| `$input = request_input();` | Get query and body | Parse request once for controller | Mixing query and body accidentally |
| `$input['body']` | JSON body array | Read body data by key | Undefined key if helper shape changes |
| `validate_payload(...)` | Apply validation rules | Return errors instead of continuing blindly | Running business logic before validation |
| `['name' => ...]` | Rules for field | Require a string `name` | Forgetting field names must match JSON exactly |
| `$errors !== []` | Has validation errors | Detect non-empty error array | Using truthiness inconsistently |
| `422` | Unprocessable Entity | Tell client JSON was valid but data failed rules | Using 400 for every client-side error |

### `backend/public/.htaccess`

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

| Syntax/Symbol | The Meaning | The Engine's Job | The Newbie Trap |
|---|---|---|---|
| `RewriteEngine On` | Enable Apache rewriting | Allow clean URLs | Apache `mod_rewrite` must be enabled |
| `RewriteCond ... !-f` | If not an existing file | Do not rewrite assets | Breaking CSS/JS by rewriting everything |
| `RewriteCond ... !-d` | If not an existing directory | Do not rewrite directories | Unexpected directory access behavior |
| `RewriteRule ^ index.php [QSA,L]` | Send request to front controller | Preserve query string and stop rewriting | Forgetting `QSA` can drop query params |

---

## 4. Concept Management

### Concept 1: Front Controller

Real world: A building has one reception desk. Visitors do not wander into random offices. Reception checks who they are asking for and sends them to the right person.

Code: `public/index.php` is reception. Every backend request enters there. It checks the route table and forwards to the correct controller.

### Concept 2: Stateless HTTP

Real world: Every time a customer calls a support center, the agent does not automatically remember the last call unless there is a ticket number or database record.

Code: PHP forgets variables after each request. Later, sessions and MySQL become the ticket system. For now, every request must bring its own method, path, query, and JSON body.

### Concept 3: Validation Before Business Logic

Real world: A bank teller checks that a withdrawal form has an account number and amount before touching the vault.

Code: `PayloadController` calls `validate_payload()` before accepting input. Later, registration, request creation, offers, completion, and reviews will use the same pattern.

---

## 5. Step-by-Step Finish Guide

### Step 1: Confirm Apache and PHP Are Running

Open XAMPP Control Panel and start Apache.

Verification:

Open:

```text
http://localhost/Market_Place_Web_Dev_Project/backend/public/api/health
```

Success looks like:

```json
{"success":true,"message":"OK","data":{"service":"marketplace-backend","php_version":"8.x.x"},"status_code":200}
```

### Step 2: Confirm the Old Health Alias

Open:

```text
http://localhost/Market_Place_Web_Dev_Project/backend/public/ok
```

Success is the same JSON shape as `/api/health`.

### Step 3: Test Unknown Routes

Open:

```text
http://localhost/Market_Place_Web_Dev_Project/backend/public/missing
```

Success for this negative test means HTTP 404 with:

```json
{"success":false,"message":"Not Found","data":{"route":"GET /missing"},"status_code":404}
```

### Step 4: Test Valid JSON Body

Use Postman:

- Method: `POST`
- URL: `http://localhost/Market_Place_Web_Dev_Project/backend/public/api/validate-demo?source=postman`
- Header: `Content-Type: application/json`
- Body:

```json
{"name":"Abel"}
```

Success looks like HTTP 200:

```json
{"success":true,"message":"Payload accepted.","data":{"query":{"source":"postman"},"body":{"name":"Abel"}},"status_code":200}
```

### Step 5: Test Missing Required Field

Send:

```json
{}
```

Success for this negative test means HTTP 422:

```json
{"success":false,"message":"Validation failed.","data":{"errors":{"name":["The name field is required."]}},"status_code":422}
```

### Step 6: Test Invalid JSON

Send this broken body:

```json
{"name":
```

Success for this negative test means HTTP 400:

```json
{"success":false,"message":"Invalid JSON body.","data":[],"status_code":400}
```

### Step 7: Lint PHP Files

PowerShell:

```powershell
C:\xampp\php\php.exe -l backend\public\index.php
C:\xampp\php\php.exe -l backend\app\helpers\request.php
C:\xampp\php\php.exe -l backend\app\helpers\response.php
C:\xampp\php\php.exe -l backend\app\controllers\PayloadController.php
C:\xampp\php\php.exe -l backend\app\routes\api.php
C:\xampp\php\php.exe -l backend\app\middleware\validation.php
```

Success:

```text
No syntax errors detected
```

### Debugging 101

Check these logs first:

```text
C:\xampp\apache\logs\error.log
C:\xampp\php\logs\php_error_log
```

Look for:

- `Parse error`: PHP syntax mistake.
- `Fatal error`: Missing class, missing function, wrong file path.
- `Warning: Cannot modify header information`: Something echoed output before `header()`.
- `Invalid JSON body`: Client sent broken JSON.
- `Call to undefined function`: A required helper file was not loaded.

---

## 6. Security & Senior Insights

### Number 1 Security Risk In This Phase

The biggest risk is trusting raw client input.

Attackers control query strings and JSON bodies. If your code blindly uses that input later in SQL, HTML, or file paths, the project becomes vulnerable.

This phase prevents the first layer of that risk by:

- Rejecting invalid JSON with HTTP 400.
- Validating required fields before controller success.
- Returning safe, standardized error responses.
- Hiding unexpected internal exception details behind `Internal Server Error`.
- Logging server-side errors with `error_log()`.

Later phases must add prepared statements for SQL and HTML escaping for output.

### Senior Developer Tip

Keep the response shape boring and consistent. SRE work becomes easier when every endpoint returns:

```json
{
  "success": true,
  "message": "...",
  "data": {},
  "status_code": 200
}
```

This makes frontend handling, Postman testing, log search, monitoring, and incident debugging much faster. Consistency is a reliability feature, not just a style preference.

