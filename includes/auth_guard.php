<?php
// /includes/auth_guard.php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bootstrap_auth.php'; // ensure session + remember-me
function require_login(): void {
  if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/signin.php');
    exit;
  }
}
function require_role(string $role): void {
  require_login();
  if (($_SESSION['user_type'] ?? '') !== $role) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
  }
}
