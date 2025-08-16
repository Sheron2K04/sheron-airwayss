<?php
// C:\xampp\htdocs\projectweb\sheronair\auth\handle_signup.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connection.php';

function back_with_error(string $msg): void {
  $_SESSION['flash_error'] = $msg;
  header('Location: ' . BASE_URL . '/auth/signup.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  back_with_error('Invalid request.');
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$email      = strtolower(trim($_POST['email'] ?? ''));

$password   = $_POST['password'] ?? '';
$confirm    = $_POST['confirm_password'] ?? '';

if ($first_name === '' || $last_name === '' || $email === '' || $password === '' || $confirm === '') {
  back_with_error('Please fill all required fields.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  back_with_error('Please enter a valid email.');
}
if (strlen($password) < 6) {
  back_with_error('Password must be at least 6 characters.');
}
if ($password !== $confirm) {
  back_with_error('Passwords do not match.');
}

try {
  // Ensure email uniqueness
  $stmt = $conn->prepare('SELECT 1 FROM users WHERE email = :email');
  $stmt->execute([':email' => $email]);
  if ($stmt->fetchColumn()) {
    back_with_error('That email is already registered.');
  }

  $hash = password_hash($password, PASSWORD_BCRYPT);

  // Insert user (user_type defaults to 'customer', is_active TRUE)
  $ins = $conn->prepare("
    INSERT INTO users (first_name, last_name, email, password, user_type, is_active)
    VALUES (:first_name, :last_name, :email, :password,  'customer', TRUE)
    RETURNING user_id
  ");
  $ins->execute([
    ':first_name' => $first_name,
    ':last_name'  => $last_name,
    ':email'      => $email,
    ':password'   => $hash,
    
  ]);

  $_SESSION['flash_ok'] = 'Account created! Please sign in.';
  header('Location: ' . BASE_URL . '/auth/signin.php');
  exit;

} catch (PDOException $e) {
  error_log('[Signup] PDO: ' . $e->getMessage());
  if (APP_ENV === 'dev') {
    back_with_error('DB error: ' . $e->getMessage());
  }
  back_with_error('Something went wrong. Please try again.');
} catch (Throwable $e) {
  error_log('[Signup] ' . $e->getMessage());
  if (APP_ENV === 'dev') {
    back_with_error('Server error: ' . $e->getMessage());
  }
  back_with_error('Something went wrong. Please try again.');
}
