<?php
// C:\xampp\htdocs\projectweb\sheronair\auth\handle_signin.php
declare(strict_types=1);

// Start session early and cleanly
if (session_status() === PHP_SESSION_NONE) session_start();

// Load config & DB
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connection.php';

// ---- SAFETY FALLBACKS / HELPERS --------------------------------------------

// If APP_ENV isn't defined in config.php, default to 'prod'
if (!defined('APP_ENV')) {
  define('APP_ENV', 'prod');
}

/**
 * Safe redirect that works even if headers were already sent.
 */
function safe_redirect(string $url): void {
  if (!headers_sent()) {
    header('Location: ' . $url);
    exit;
  }
  // Fallback if output already started
  echo '<!doctype html><meta charset="utf-8"><script>location.replace('
     . json_encode($url)
     . ');</script><noscript><meta http-equiv="refresh" content="0;url='
     . htmlspecialchars($url, ENT_QUOTES)
     . '"></noscript>';
  exit;
}

/**
 * Push a flash error and go back to the signin page safely.
 */
function back_with_error(string $msg): void {
  $_SESSION['flash_error'] = $msg;
  $url = (defined('BASE_URL') ? BASE_URL : '') . '/auth/signin.php';
  safe_redirect($url);
}

// ----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  back_with_error('Invalid request.');
}

$email    = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$remember = !empty($_POST['remember']);

if ($email === '' || $password === '') {
  back_with_error('Please enter email and password.');
}

try {
  // 1) Look up user
  $stmt = $conn->prepare("
    SELECT user_id, first_name, last_name, email, password, user_type, is_active
    FROM users
    WHERE email = :email
    LIMIT 1
  ");
  $stmt->execute([':email' => $email]);
  $user = $stmt->fetch();

  if (!$user) {
    back_with_error('Invalid email or password.');
  }
  if (!$user['is_active']) {
    back_with_error('Your account is inactive. Please contact support.');
  }

  // 2) Verify password
  if (!password_verify($password, $user['password'])) {
    back_with_error('Invalid email or password.');
  }

  // 3) Init session
  session_regenerate_id(true);
  $_SESSION['user_id']    = (int)$user['user_id'];
  $_SESSION['email']      = $user['email'];
  $_SESSION['role']       = $user['user_type']; // 'customer'|'ticket_admin'|'flight_admin'|'super_admin'
  $_SESSION['first_name'] = $user['first_name'];
  $_SESSION['last_name']  = $user['last_name'];

  // 4) Update last_login (non-fatal)
  try {
    $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = :id")
         ->execute([':id' => (int)$user['user_id']]);
  } catch (Throwable $e) { /* ignore */ }

  // 5) Remember me
  if ($remember) {
    // Ensure you have auth_tokens table as documented earlier
    $selector  = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $vhash     = hash('sha256', $validator);

    // Clean old tokens (optional)
    try {
      $conn->prepare("DELETE FROM auth_tokens WHERE user_id = :uid AND expires_at < NOW()")
           ->execute([':uid' => (int)$user['user_id']]);
    } catch (Throwable $e) { /* ignore */ }

    // Insert new token
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

    // Cookies (set 'secure'=>true if served over https)
    $cookieOpts = [
      'expires'  => time() + (30 * 24 * 60 * 60),
      'path'     => '/',
      'secure'   => false,
      'httponly' => true,
      'samesite' => 'Lax',
    ];
    setcookie('remember_selector',  $selector,  $cookieOpts);
    setcookie('remember_validator', $validator, $cookieOpts);
  }

  // 6) Redirect by role
  $base = defined('BASE_URL') ? BASE_URL : '';
  switch ($user['user_type']) {
    case 'ticket_admin':
      safe_redirect($base . '/ticketadmin.php');
      break;
    case 'flight_admin':
      safe_redirect($base . '/flightmanagementadmin.php');
      break;
    case 'super_admin':
      safe_redirect($base . '/admindashboard.php');
      break;
    default:
      safe_redirect($base . '/index.php');
      break;
  }

} catch (PDOException $e) {
  error_log('[Signin] PDO: ' . $e->getMessage());
  if (APP_ENV === 'dev') {
    back_with_error('DB error: ' . $e->getMessage());
  }
  back_with_error('Something went wrong. Please try again.');
} catch (Throwable $e) {
  error_log('[Signin] ' . $e->getMessage());
  if (APP_ENV === 'dev') {
    back_with_error('Server error: ' . $e->getMessage());
  }
  back_with_error('Something went wrong. Please try again.');
}
