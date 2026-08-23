<?php
/**
 * Token-based auth (no PHP sessions), per locked scope.
 */

require_once __DIR__ . '/response.php';

const TOKEN_LIFETIME_DAYS = 7;

function generate_token(): string {
    return bin2hex(random_bytes(32));
}

/**
 * Issue a token for a user and store it. Old tokens are left in place
 * (multi-device login allowed) — expired ones are just ignored on lookup.
 */
function issue_token(PDO $pdo, int $userId): array {
    $token = generate_token();
    $expiresAt = (new DateTime())->modify('+' . TOKEN_LIFETIME_DAYS . ' days')->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare(
        'INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)'
    );
    $stmt->execute([
        'user_id' => $userId,
        'token' => $token,
        'expires_at' => $expiresAt,
    ]);

    return ['token' => $token, 'expires_at' => $expiresAt];
}

/**
 * Reads the Authorization: Bearer <token> header, validates it against
 * auth_tokens (unexpired), and returns the associated user row.
 * Sends a 401 and exits if the token is missing/invalid/expired.
 */
function require_auth(PDO $pdo): array {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

    if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
        send_error('Missing or malformed Authorization header', 401);
    }

    $token = trim(substr($authHeader, 7));

    $stmt = $pdo->prepare(
        'SELECT u.id, u.name, u.email, u.role_id, u.department_id, r.name AS role_name
         FROM auth_tokens t
         JOIN users u ON u.id = t.user_id
         JOIN roles r ON r.id = u.role_id
         WHERE t.token = :token AND t.expires_at > NOW() AND u.is_active = 1'
    );
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        send_error('Invalid or expired token', 401);
    }

    return $user;
}
