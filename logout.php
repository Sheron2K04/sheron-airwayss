<?php
require_once __DIR__ . '/includes/config.php';
session_start();

// Clear remember-me cookies (optional cleanup)
$cookiePath = '/projectweb/sheronair';
setcookie('remember_selector', '', time() - 3600, $cookiePath);
setcookie('remember_validator', '', time() - 3600, $cookiePath);

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}
session_destroy();

header('Location: ' . BASE_URL . '/auth/signin.php');
exit;
