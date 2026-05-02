<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$publicRoot = $root . DIRECTORY_SEPARATOR . 'public';
$router = $publicRoot . DIRECTORY_SEPARATOR . 'router.php';
$host = '127.0.0.1';
$port = 8765;
$baseUrl = "http://{$host}:{$port}";

if (!is_file($router)) {
    fwrite(STDERR, "Missing router: {$router}" . PHP_EOL);
    exit(1);
}

$command = sprintf(
    '"%s" -S %s:%d -t "%s" "%s"',
    PHP_BINARY,
    $host,
    $port,
    $publicRoot,
    $router
);

$process = proc_open($command, [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
], $pipes);

if (!is_resource($process)) {
    fwrite(STDERR, 'Could not start PHP development server.' . PHP_EOL);
    exit(1);
}

try {
    wait_for_server($host, $port);

    $tests = [
        'health endpoint' => function () use ($baseUrl): void {
            $response = http_request('GET', $baseUrl . '/api/health');

            assert_status($response, 200);
            assert_json_field($response, 'success', true);
            assert_json_field($response, 'message', 'OK');
        },
        'unknown route' => function () use ($baseUrl): void {
            $response = http_request('GET', $baseUrl . '/missing');

            assert_status($response, 404);
            assert_json_field($response, 'success', false);
            assert_json_field($response, 'message', 'Not Found');
        },
        'valid JSON body' => function () use ($baseUrl): void {
            $response = http_request('POST', $baseUrl . '/api/validate-demo?source=test', ['name' => 'Abel']);

            assert_status($response, 200);
            assert_json_field($response, 'success', true);
            assert_json_field($response, 'data.body.name', 'Abel');
            assert_json_field($response, 'data.query.source', 'test');
        },
        'missing required field' => function () use ($baseUrl): void {
            $response = http_request('POST', $baseUrl . '/api/validate-demo', []);

            assert_status($response, 422);
            assert_json_field($response, 'success', false);
            assert_json_field($response, 'message', 'Validation failed.');
        },
        'invalid JSON body' => function () use ($baseUrl): void {
            $response = http_request_raw('POST', $baseUrl . '/api/validate-demo', '{"name":');

            assert_status($response, 400);
            assert_json_field($response, 'success', false);
            assert_json_field($response, 'message', 'Invalid JSON body.');
        },
    ];

    $failures = [];

    foreach ($tests as $name => $test) {
        try {
            $test();
            echo "[PASS] {$name}" . PHP_EOL;
        } catch (Throwable $exception) {
            $failures[] = "[FAIL] {$name}: {$exception->getMessage()}";
            echo end($failures) . PHP_EOL;
        }
    }

    if ($failures !== []) {
        exit(1);
    }

    echo 'Phase 1 acceptance checks passed.' . PHP_EOL;
} finally {
    foreach ($pipes as $pipe) {
        if (is_resource($pipe)) {
            fclose($pipe);
        }
    }

    proc_terminate($process);
    proc_close($process);
}

function wait_for_server(string $host, int $port): void
{
    $deadline = microtime(true) + 5;

    do {
        $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 0.2);

        if (is_resource($socket)) {
            fclose($socket);
            return;
        }

        usleep(100000);
    } while (microtime(true) < $deadline);

    throw new RuntimeException("Server did not start on {$host}:{$port}.");
}

function http_request(string $method, string $url, ?array $jsonBody = null): array
{
    return http_request_raw($method, $url, $jsonBody === null ? null : json_encode($jsonBody, JSON_UNESCAPED_SLASHES));
}

function http_request_raw(string $method, string $url, ?string $body = null): array
{
    $headers = [];

    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Content-Length: ' . strlen($body);
    }

    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $body ?? '',
            'ignore_errors' => true,
            'timeout' => 5,
        ],
    ]);

    $content = file_get_contents($url, false, $context);

    if ($content === false) {
        throw new RuntimeException("HTTP request failed: {$method} {$url}");
    }

    $status = 0;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches) === 1) {
            $status = (int) $matches[1];
            break;
        }
    }

    $json = json_decode($content, true);

    if (!is_array($json)) {
        throw new RuntimeException("Response is not JSON: {$content}");
    }

    return [
        'status' => $status,
        'json' => $json,
    ];
}

function assert_status(array $response, int $expected): void
{
    if ($response['status'] !== $expected) {
        throw new RuntimeException("Expected status {$expected}, got {$response['status']}.");
    }
}

function assert_json_field(array $response, string $path, mixed $expected): void
{
    $actual = $response['json'];

    foreach (explode('.', $path) as $segment) {
        if (!is_array($actual) || !array_key_exists($segment, $actual)) {
            throw new RuntimeException("Missing JSON field {$path}.");
        }

        $actual = $actual[$segment];
    }

    if ($actual !== $expected) {
        throw new RuntimeException("Expected {$path} to be " . json_encode($expected) . ', got ' . json_encode($actual) . '.');
    }
}
