<?php
/**
 * Token-based auth (no PHP sessions), per locked scope.
 * Now backed by signed JWTs instead of DB-stored opaque tokens.
 */
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/../config/jwt_config.php';

const TOKEN_LIFETIME_DAYS = 7;

/**
 * Issues a signed JWT for a user. Unlike the old implementation, this
 * does NOT write to the database - the token is self-verifying, so
 * there's nothing to look up. Multi-device login still works fine,
 * since each login just mints its own independent JWT.
 */
function issue_token(PDO $pdo, int $userId): array {
    $issuedAt = time();
    $expiresAtTs = $issuedAt + (TOKEN_LIFETIME_DAYS * 24 * 60 * 60);

    $payload = [
        'sub' => $userId, // "subject" - the user this token belongs to
        'iat' => $issuedAt, // "issued at"
        'exp' => $expiresAtTs, // "expires at"
    ];

    $token = jwt_encode($payload, JWT_SECRET);
    $expiresAt = (new DateTime())->setTimestamp($expiresAtTs)->format('Y-m-d H:i:s');

    return ['token' => $token, 'expires_at' => $expiresAt];
}

/**
 * Reads the Authorization header, trying every common way it can arrive
 * (different clients/servers vary in casing and which PHP superglobal
 * they populate). Returns the raw header value, or '' if not found
 * anywhere.
 */
function get_authorization_header(): string {
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                return $value;
            }
        }
    }

    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        return $_SERVER['HTTP_AUTHORIZATION'];
    }

    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }

    return '';
}

/**
 * Reads the Authorization: Bearer <token> header, verifies the JWT's
 * signature and expiry, then loads the associated user row (so we can
 * still catch deactivated accounts even though the token itself is
 * still technically valid). Sends a 401 and exits if anything's wrong.
 */
function require_auth(PDO $pdo): array {
    $authHeader = get_authorization_header();

    if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
        send_error('Missing or malformed Authorization header', 401);
    }

    $token = trim(substr($authHeader, 7));
    $payload = jwt_decode($token, JWT_SECRET);

    if ($payload === null || !isset($payload['sub'])) {
        send_error('Invalid or expired token', 401);
    }

    $stmt = $pdo->prepare(
        'SELECT u.id, u.name, u.email, u.role AS role_name, u.department_id
         FROM users u
         WHERE u.id = :id AND u.is_active = 1'
    );
    $stmt->execute(['id' => $payload['sub']]);
    $user = $stmt->fetch();

    if (!$user) {
        send_error('Invalid or expired token', 401);
    }

    return $user;
}