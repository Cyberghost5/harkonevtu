<?php
/**
 * ============================================================================
 * Glo Gifting API Bridge Proxy (proxy.php)
 * ============================================================================
 * 
 * ARCHITECTURE:
 * Main VPS (Laravel App)  --->  proxy.php (Shared Host)  --->  gifting-api.gloworld.com
 * 
 * POSTMAN MANUAL TESTING EXAMPLE:
 * ----------------------------------------------------------------------------
 * Method: POST
 * URL:    https://your-shared-host.com/proxy.php
 * Headers:
 *   Content-Type: application/json
 *   X-Proxy-Secret: YOUR_STRONG_GENERATED_SECRET_KEY
 * 
 * Request Body (JSON):
 * {
 *   "msisdn": "2348050000000",
 *   "plan_id": "101",
 *   "client_reference": "REF-20260915-001"
 * }
 * 
 * Expected Successful Response (HTTP 200 OK):
 * {
 *   "status": "success",
 *   "message": "Data bundle gifted successfully",
 *   "transaction_id": "GLO-TXN-98765432"
 * }
 * 
 * Expected Error Response (e.g. HTTP 403 Forbidden):
 * {
 *   "error": "Forbidden: Invalid or missing X-Proxy-Secret header"
 * }
 * ----------------------------------------------------------------------------
 * 
 * SECURITY REMINDERS:
 * 1. SHARED SECRET: Generate a strong, high-entropy 64-character hex key using:
 *      openssl rand -hex 32
 *    Set this value in PROXY_SHARED_SECRET below OR as an environment variable
 *    in your shared host's cPanel / environment setting.
 * 
 * 2. SERVER / HOST LEVEL RESTRICTIONS:
 *    As an additional defense-in-depth layer beyond the shared secret, restrict
 *    execution of this proxy.php script to ONLY accept incoming requests from your
 *    Main VPS's IP address.
 *    If using Apache (.htaccess), add:
 *      <Files "proxy.php">
 *          Require ip YOUR_MAIN_VPS_IP_HERE
 *      </Files>
 *    Or configure IP restrictions in your shared hosting control panel (cPanel/Plesk).
 * ============================================================================
 */

// ----------------------------------------------------------------------------
// Configuration Constants
// ----------------------------------------------------------------------------
define('PROXY_SHARED_SECRET', getenv('PROXY_SHARED_SECRET') ?: 'REPLACE_WITH_YOUR_STRONG_SHARED_SECRET');
define('TARGET_API_URL', 'https://gifting-api.gloworld.com/v1/distribution');
define('CURL_TIMEOUT', 25);               // 25-second cURL timeout
define('RATE_LIMIT_MAX', 30);              // Maximum requests per minute
define('RATE_LIMIT_WINDOW', 60);           // Time window in seconds
define('LOG_FILE', __DIR__ . '/proxy.log');
define('RATE_LIMIT_FILE', sys_get_temp_dir() . '/gifting_proxy_ratelimit.json');

// Set JSON output header helper
function sendJsonResponse(int $statusCode, array $data): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

// ----------------------------------------------------------------------------
// 1. Validate Secret Header (X-Proxy-Secret)
// ----------------------------------------------------------------------------
$clientSecret = '';

if (isset($_SERVER['HTTP_X_PROXY_SECRET'])) {
    $clientSecret = $_SERVER['HTTP_X_PROXY_SECRET'];
} elseif (function_exists('getallheaders')) {
    $headers = getallheaders();
    foreach ($headers as $key => $value) {
        if (strcasecmp($key, 'X-Proxy-Secret') === 0) {
            $clientSecret = $value;
            break;
        }
    }
}

if (empty($clientSecret) || !hash_equals(PROXY_SHARED_SECRET, $clientSecret)) {
    // Log unauthorized attempt without logging the incoming secret
    logAudit(403, 'Unauthorized access attempt - invalid or missing X-Proxy-Secret header');
    sendJsonResponse(403, [
        'error' => 'Forbidden: Invalid or missing X-Proxy-Secret header'
    ]);
}

// ----------------------------------------------------------------------------
// 2. Rate Limiting Check (Max 30 requests / minute)
// ----------------------------------------------------------------------------
enforceRateLimit();

// ----------------------------------------------------------------------------
// 3. Validate Content-Type before accessing php://input
// ----------------------------------------------------------------------------
$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

if (stripos($contentType, 'application/json') === false) {
    logAudit(400, 'Invalid Content-Type header');
    sendJsonResponse(400, [
        'error' => 'Bad Request: Content-Type must be application/json'
    ]);
}

// Safely read raw input stream after Content-Type verification
$rawPayload = file_get_contents('php://input');

if ($rawPayload === false || trim($rawPayload) === '') {
    logAudit(400, 'Empty request payload');
    sendJsonResponse(400, [
        'error' => 'Bad Request: Empty request body'
    ]);
}

// ----------------------------------------------------------------------------
// 4. Forward Payload to Glo Gifting API via cURL
// ----------------------------------------------------------------------------
$ch = curl_init(TARGET_API_URL);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $rawPayload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => CURL_TIMEOUT,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$responseBody = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
curl_close($ch);

// Handle cURL errors (e.g. connection timeouts)
if ($responseBody === false || $curlErrno !== 0) {
    $errorMsg = "cURL error {$curlErrno}: {$curlError} for " . TARGET_API_URL;
    logAudit(504, "cURL Failure: {$errorMsg}", $rawPayload);
    sendJsonResponse(504, [
        'error' => $errorMsg,
        'message' => $errorMsg
    ]);
}

// ----------------------------------------------------------------------------
// 5. Audit Log & Mirror Response Back to Caller
// ----------------------------------------------------------------------------
logAudit($httpCode, 'Request processed', $rawPayload);

http_response_code($httpCode > 0 ? $httpCode : 500);
header('Content-Type: application/json; charset=utf-8');
echo $responseBody;
exit;

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Basic file-based rate limiter (Max 30 requests per minute bucket).
 */
function enforceRateLimit(): void {
    $now = time();
    $currentWindow = floor($now / RATE_LIMIT_WINDOW);

    $rateData = [
        'window' => $currentWindow,
        'count' => 0
    ];

    if (file_exists(RATE_LIMIT_FILE)) {
        $content = @file_get_contents(RATE_LIMIT_FILE);
        if ($content !== false) {
            $parsed = @json_decode($content, true);
            if (is_array($parsed) && isset($parsed['window'], $parsed['count'])) {
                if ($parsed['window'] === $currentWindow) {
                    $rateData = $parsed;
                }
            }
        }
    }

    $rateData['count']++;
    @file_put_contents(RATE_LIMIT_FILE, json_encode($rateData), LOCK_EX);

    if ($rateData['count'] > RATE_LIMIT_MAX) {
        logAudit(429, 'Rate limit exceeded (' . RATE_LIMIT_MAX . ' req/min)');
        sendJsonResponse(429, [
            'error' => 'Too Many Requests: Rate limit of ' . RATE_LIMIT_MAX . ' requests per minute exceeded.'
        ]);
    }
}

/**
 * Log audit details to local log file (excluding secret).
 */
function logAudit(int $statusCode, string $message, string $payload = ''): void {
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN_IP';
    
    // Truncate payload for audit safety and log size control
    $cleanPayload = '';
    if (!empty($payload)) {
        $cleanPayload = ' | Payload: ' . substr(preg_replace('/\s+/', ' ', $payload), 0, 250);
    }

    $logLine = sprintf("[%s] IP: %s | Status: %d | Note: %s%s\n", $timestamp, $ip, $statusCode, $message, $cleanPayload);
    @file_put_contents(LOG_FILE, $logLine, FILE_APPEND | LOCK_EX);
}
