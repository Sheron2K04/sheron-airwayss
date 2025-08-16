<?php
// C:\xampp\htdocs\projectweb\sheronair\auth\handle_signin.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connection.php'; // must define $conn = new PDO(... pgsql)

// --------------------------- Fallbacks ---------------------------
if (!defined('APP_ENV')) { define('APP_ENV', 'prod'); }                 // 'dev' | 'prod'
if (!defined('BASE_URL')) { define('BASE_URL', 'http://localhost'); }  // safety fallback
if (!defined('REMEMBER_ME_ENABLED')) { define('REMEMBER_ME_ENABLED', false); } // set true in config if you created auth_tokens

// --------------------------- Helpers ----------------------------
/** Redirect even if headers already sent */
function safe_redirect(string $url): void {
  if (!headers_sent()) { header('Location: ' . $url); exit; }
  echo '<!doctype html><meta charset="utf-8"><script>location.replace('
     . json_encode($url)
     . ');</script><noscript><meta http-equiv="refresh" content="0;url='
     . htmlspecialchars($url, ENT_QUOTES)
     . '"></noscript>';
  exit;
}

/** Flash error and go back to signin */
function back_with_error(string $msg): void {
  $_SESSION['flash_error'] = $msg;
  safe_redirect(BASE_URL . '/auth/signin.php');
}

/** Map user_type → dashboard URL */
function dashboard_for_role(string $role): string {
  $map = [
    'customer'      => '/index.php',                 // or your own customer dashboard
    'ticket_admin'  => '/ticketadmin.php',
    'flight_admin'  => '/flightmanagementadmin.php',
    'hotel_manager' => '/hotel/dashboard.php',       // make sure this exists or change it
    'super_admin'   => '/admindashboard.php',
  ];
  return BASE_URL . ($map[$role] ?? '/index.php');
}

// --------------------------- Guard ------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  back_with_error('Invalid request.');
}

// --------------------------- Inputs -----------------------------
$email    = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');
$remember = !empty($_POST['remember']);

if ($email === '' || $password === '') {
  back_with_error('Please enter email and password.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  back_with_error('Please enter a valid email address.');
}

// --------------------------- Auth -------------------------------
try {
  // Look up user
  $stmt = $conn->prepare("
    SELECT user_id, first_name, last_name, email, password, user_type, is_active
    FROM users
    WHERE email = :email
    LIMIT 1
  ");
  $stmt->execute([':email' => $email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  // Validate credentials
  if (!$user || !password_verify($password, (string)$user['password'])) {
    back_with_error('Invalid email or password.');
  }
  if (!(bool)($user['is_active'] ?? true)) {
    back_with_error('Your account is inactive. Please contact support.');
  }

  // Session
  session_regenerate_id(true);
  $_SESSION['user_id']    = (int)$user['user_id'];
  $_SESSION['email']      = (string)$user['email'];
  $_SESSION['role']       = (string)$user['user_type'];
  $_SESSION['first_name'] = (string)$user['first_name'];
  $_SESSION['last_name']  = (string)$user['last_name'];

  // Update last_login (non-fatal)
  try {
    $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = :id")
         ->execute([':id' => (int)$user['user_id']]);
  } catch (Throwable $e) {
    // ignore
  }

  // -------------------- Optional: Remember Me -------------------
  if ($remember && REMEMBER_ME_ENABLED) {
    try {
      // Generate selector + validator (store only hash of validator)
      $selector  = bin2hex(random_bytes(12));
      $validator = bin2hex(random_bytes(32));
      $vhash     = hash('sha256', $validator);

      // Clean expired tokens for this user (optional)
      $conn->prepare("DELETE FROM auth_tokens WHERE user_id = :uid AND expires_at < NOW()")
           ->execute([':uid' => (int)$user['user_id']]);

      // Insert token (requires auth_tokens table)
      $tok = $conn->prepare("
        INSERT INTO auth_tokens (selector, user_id, validator_hash, expires_at)
        VALUES (:selector, :uid, :vhash, NOW() + INTERVAL '30 days')
        ON CONFLICT (selector) DO NOTHING
      ");
      $tok->execute([
        ':selector' => $selector,
        ':uid'      => (int)$user['user_id'],
        ':vhash'    => $vhash,
      ]);

      // Set cookies only if insert happened
      if ($tok->rowCount() > 0) {
        $opts = [
          'expires'  => time() + (30 * 24 * 60 * 60),
          'path'     => '/',
          'secure'   => false,  // set true if you serve over HTTPS
          'httponly' => true,
          'samesite' => 'Lax',
        ];
        setcookie('remember_selector',  $selector,  $opts);
        setcookie('remember_validator', $validator, $opts);
      }
    } catch (PDOException $e) {
      // If the table doesn't exist (42P01) or any DB issue, log and continue (do not block login)
      if ($e->getCode() !== '42P01') {
        error_log('[Signin][RememberMe] ' . $e->getCode() . ' ' . $e->getMessage());
      }
    }
  }

  // --------------------------- Redirect -------------------------
  safe_redirect(dashboard_for_role((string)$user['user_type']));

} catch (PDOException $e) {
  error_log('[Signin][PDO] ' . $e->getCode() . ' ' . $e->getMessage());
  if (APP_ENV === 'dev') back_with_error('DB error: ' . $e->getMessage());
  back_with_error('Something went wrong. Please try again.');
} catch (Throwable $e) {
  error_log('[Signin] ' . $e->getMessage());
  if (APP_ENV === 'dev') back_with_error('Server error: ' . $e->getMessage());
  back_with_error('Something went wrong. Please try again.');
}
