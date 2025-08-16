<?php
// C:\xampp\htdocs\projectweb\sheronair\handle_payment.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/bootstrap_auth.php';
require_once __DIR__ . '/includes/db_connection.php';

function back_with_error(string $msg, array $params = []): void {
  $_SESSION['flash_error'] = $msg;
  $q = http_build_query($params);
  $url = BASE_URL . '/checkout.php' . ($q ? ('?' . $q) : '');
  header('Location: ' . $url);
  exit;
}

// Require login
if (empty($_SESSION['user_id'])) {
  header('Location: ' . BASE_URL . '/auth/signin.php');
  exit;
}

// Validate CSRF
$csrf = $_POST['csrf'] ?? '';
if (!$csrf || empty($_SESSION['csrf_pay']) || !hash_equals($_SESSION['csrf_pay'], $csrf)) {
  back_with_error('Invalid or expired form. Please try again.');
}

// Read inputs
$flight_id    = (int)($_POST['flight_id'] ?? 0);
$passengers   = (int)($_POST['passengers'] ?? 0);
$ticket_class = trim($_POST['ticket_class'] ?? 'Economy');

$card_name   = trim($_POST['card_name'] ?? '');
$card_number = preg_replace('/\D+/', '', $_POST['card_number'] ?? '');
$card_expiry = trim($_POST['card_expiry'] ?? ''); // MM/YY
$card_cvv    = preg_replace('/\D+/', '', $_POST['card_cvv'] ?? '');

if ($flight_id <= 0 || $passengers <= 0) {
  back_with_error('Invalid request.', ['flight_id'=>$flight_id, 'passengers'=>$passengers, 'ticket_class'=>$ticket_class]);
}

// Basic card validation (demo)
if ($card_name === '' || $card_number === '' || $card_expiry === '' || $card_cvv === '') {
  back_with_error('Please fill all card details.', ['flight_id'=>$flight_id, 'passengers'=>$passengers, 'ticket_class'=>$ticket_class]);
}
if (strlen($card_number) < 13 || strlen($card_number) > 19) {
  back_with_error('Invalid card number.', ['flight_id'=>$flight_id, 'passengers'=>$passengers, 'ticket_class'=>$ticket_class]);
}
if (!preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) {
  back_with_error('Invalid expiry format. Use MM/YY.', ['flight_id'=>$flight_id, 'passengers'=>$passengers, 'ticket_class'=>$ticket_class]);
}
list($mm,$yy) = explode('/', $card_expiry);
$mm = (int)$mm; $yy = (int)$yy;
// Convert YY to 20YY (naive)
$yy = ($yy >= 70 ? 1900+$yy : 2000+$yy);
if ($mm < 1 || $mm > 12) {
  back_with_error('Invalid expiry month.', ['flight_id'=>$flight_id, 'passengers'=>$passengers, 'ticket_class'=>$ticket_class]);
}
// Expired?
$expLastDay = (new DateTimeImmutable("$yy-$mm-01"))->modify('last day of this month')->setTime(23,59,59);
if ($expLastDay < new DateTimeImmutable('now')) {
  back_with_error('Card expired.', ['flight_id'=>$flight_id, 'passengers'=>$passengers, 'ticket_class'=>$ticket_class]);
}
if (strlen($card_cvv) < 3 || strlen($card_cvv) > 4) {
  back_with_error('Invalid CVV.', ['flight_id'=>$flight_id, 'passengers'=>$passengers, 'ticket_class'=>$ticket_class]);
}

// Fetch flight + price
$sql = "
SELECT f.flight_id, f.flight_number, f.departure_time, f.arrival_time, f.base_price,
       ao.code AS origin_code, ao.city AS origin_city,
       ad.code AS dest_code,  ad.city AS dest_city
FROM flights f
JOIN airports ao ON ao.airport_id = f.origin_id
JOIN airports ad ON ad.airport_id = f.destination_id
WHERE f.flight_id = :fid
LIMIT 1;
";
$stmt = $conn->prepare($sql);
$stmt->execute([':fid' => $flight_id]);
$flight = $stmt->fetch();
if (!$flight) {
  back_with_error('Flight not found.');
}

// Sanitize class + multiplier
$valid_classes = ['Economy','Premium Economy','Business','First'];
if (!in_array($ticket_class, $valid_classes, true)) {
  $ticket_class = 'Economy';
}
$classMult = [
  'Economy'          => 1.00,
  'Premium Economy'  => 1.30,
  'Business'         => 1.80,
  'First'            => 2.20,
];
$unit_price = (float)$flight['base_price'] * $classMult[$ticket_class];
$total      = $unit_price * $passengers;

// Simulate card authorization (ALWAYS APPROVE in demo)
$authorized = true;

// Create booking + payment atomically
try {
  $conn->beginTransaction();

  // Create booking
  $bookingRef = 'BR' . str_pad((string)random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
  $insB = $conn->prepare("
    INSERT INTO bookings (booking_reference, user_id, flight_id, total_amount, status)
    VALUES (:ref, :uid, :fid, :amount, 'Confirmed')
    RETURNING booking_id
  ");
  $insB->execute([
    ':ref'    => $bookingRef,
    ':uid'    => (int)$_SESSION['user_id'],
    ':fid'    => (int)$flight_id,
    ':amount' => $total,
  ]);
  $booking_id = (int)$insB->fetchColumn();

  // Payment row
  $txnId = 'TXN' . str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
  $insP = $conn->prepare("
    INSERT INTO payments (booking_id, amount, payment_method, transaction_id, status)
    VALUES (:bid, :amt, 'Credit Card', :txn, :status)
    RETURNING payment_id
  ");
  $insP->execute([
    ':bid'    => $booking_id,
    ':amt'    => $total,
    ':txn'    => $txnId,
    ':status' => $authorized ? 'Completed' : 'Failed',
  ]);
  $payment_id = (int)$insP->fetchColumn();

  $conn->commit();

  // Clear CSRF for payment to avoid resubmission
  unset($_SESSION['csrf_pay']);

  // Redirect to success/receipt
  $q = http_build_query([
    'booking_id'   => $booking_id,
    'payment_id'   => $payment_id,
    'flight_id'    => $flight_id,
    'passengers'   => $passengers,
    'ticket_class' => $ticket_class,
  ]);
  header('Location: ' . BASE_URL . '/payment_success.php?' . $q);
  exit;

} catch (Throwable $e) {
  if ($conn->inTransaction()) $conn->rollBack();
  error_log('[Payment] ' . $e->getMessage());
  back_with_error('Payment failed. Please try again.');
}
