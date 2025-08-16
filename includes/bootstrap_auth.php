<?php
/**
 * C:\xampp\htdocs\projectweb\sheronair\includes\bootstrap_auth.php
 *
 * Responsibilities:
 *  - Start a PHP session (if not already started)
 *  - Include the database connection (PDO $conn)
 *  - If no active session, try cookie-based auto-login ("remember me")
 *  - Normalize expected session keys
 *  - Never produce output (avoid "headers already sent")
 *
 * Requires:
 *   - includes/db_connection.php  (defines $conn)
 *   - Optional cookies:
 *       remember_selector, remember_validator
 *
 * Schema expected:
 *   TABLE auth_tokens(
 *     selector VARCHAR PRIMARY KEY,
 *     user_id INT REFERENCES users(user_id) ON DELETE CASCADE,
 *     validator_hash TEXT NOT NULL,
 *     expires_at TIMESTAMP NOT NULL
 *   )
 *   TABLE users(
 *     user_id SERIAL PRIMARY KEY,
 *     first_name TEXT, last_name TEXT, email TEXT UNIQUE,
 *     password TEXT, phone TEXT, user_type TEXT, is_active BOOLEAN, last_login TIMESTAMP
 *   )
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Always pull DB (we’ll need it for remember-me)
require_once __DIR__ . '/db_connection.php';

// Normalize / pre-create the keys we use elsewhere
$_SESSION['user_id']    = $_SESSION['user_id']    ?? null;
$_SESSION['email']      = $_SESSION['email']      ?? null;
$_SESSION['role']       = $_SESSION['role']       ?? null; // maps to users.user_type
$_SESSION['first_name'] = $_SESSION['first_name'] ?? null;
$_SESSION['last_name']  = $_SESSION['last_name']  ?? null;

// If the user is already logged in, we can stop here.
if (!empty($_SESSION['user_id'])) {
    return;
}

// --- Attempt cookie-based auto-login ---
$selector  = $_COOKIE['remember_selector']  ?? null;
$validator = $_COOKIE['remember_validator'] ?? null;

if ($selector && $validator) {
    try {
        // Fetch token + user in one query
        $stmt = $conn->prepare("
            SELECT
                at.user_id,
                at.validator_hash,
                at.expires_at,
                u.user_id      AS u_id,
                u.email        AS u_email,
                u.user_type    AS u_role,
                u.first_name   AS u_first,
                u.last_name    AS u_last,
                u.is_active    AS u_active
            FROM auth_tokens at
            JOIN users u ON u.user_id = at.user_id
            WHERE at.selector = :selector
            LIMIT 1
        ");
        $stmt->execute([':selector' => $selector]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Helper to hard-delete a token and clear cookies (no output!)
        $clearTokenAndCookies = function() use ($conn, $selector) {
            try {
                $del = $conn->prepare("DELETE FROM auth_tokens WHERE selector = :selector");
                $del->execute([':selector' => $selector]);
            } catch (Throwable $e) {
                // ignore
            }
            // Expire cookies
            setcookie('remember_selector', '', time() - 3600, '/', '', false, true);
            setcookie('remember_validator', '', time() - 3600, '/', '', false, true);
        };

        if ($row) {
            // Check token expiry
            $now = new DateTimeImmutable('now');
            $exp = new DateTimeImmutable($row['expires_at']);
            if ($exp > $now && $row['u_active']) {
                // Validate the validator (client token) against stored hash
                $calc = hash('sha256', $validator);
                if (hash_equals($row['validator_hash'], $calc)) {
                    // ✅ Token valid → Log in user via session
                    session_regenerate_id(true);
                    $_SESSION['user_id']    = (int)$row['u_id'];
                    $_SESSION['email']      = $row['u_email'];
                    $_SESSION['role']       = $row['u_role'];      // 'customer' | 'ticket_admin' | 'flight_admin' | 'super_admin'
                    $_SESSION['first_name'] = $row['u_first'];
                    $_SESSION['last_name']  = $row['u_last'];

                    // Rotate validator and extend expiry (+30 days)
                    $newValidator = bin2hex(random_bytes(32));
                    $newHash      = hash('sha256', $newValidator);

                    $rot = $conn->prepare("
                        UPDATE auth_tokens
                        SET validator_hash = :vhash,
                            expires_at = (NOW() + INTERVAL '30 days')
                        WHERE selector = :selector
                    ");
                    $rot->execute([
                        ':vhash'    => $newHash,
                        ':selector' => $selector,
                    ]);

                    // Refresh cookies (30 days)
                    $cookieOpts = [
                        'expires'  => time() + (30 * 24 * 60 * 60),
                        'path'     => '/',
                        'secure'   => false,   // set true if you serve over https
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ];
                    setcookie('remember_selector',  $selector,    $cookieOpts);
                    setcookie('remember_validator', $newValidator, $cookieOpts);

                    // Optionally update last_login (ignore failures)
                    try {
                        $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = :id")
                             ->execute([':id' => (int)$row['u_id']]);
                    } catch (Throwable $e) { /* ignore */ }

                    return; // session is now populated; caller page continues as logged in
                } else {
                    // Validator mismatch → possible theft or stale cookie
                    $clearTokenAndCookies();
                    return;
                }
            } else {
                // Expired or user inactive → delete token and clear cookies
                $clearTokenAndCookies();
                return;
            }
        }

        // No such token → clear cookies (silent)
        setcookie('remember_selector', '', time() - 3600, '/', '', false, true);
        setcookie('remember_validator', '', time() - 3600, '/', '', false, true);

    } catch (Throwable $e) {
        // Swallow errors; we never want to emit output in a bootstrap.
        // You can log if needed:
        // error_log('[bootstrap_auth] remember-me error: ' . $e->getMessage());
    }
}

// If we reach here, user remains unauthenticated; caller can decide next steps.
// No output.
