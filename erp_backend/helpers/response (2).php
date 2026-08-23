<?php
/**
 * Consistent JSON envelope for every API endpoint.
 */

header('Content-Type: application/json; charset=utf-8');
// Permissive CORS for local dev (Flutter mobile itself isn't CORS-bound,
// but this makes the API easy to hit from Postman/browser during testing).
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function send_success($data = null, int $code = 200): void {
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'data' => $data,
    ]);
    exit;
}

function send_error(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
    ]);
    exit;
}

function get_json_body(): array {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        send_error('Invalid or missing JSON body', 400);
    }
    return $decoded;
}
