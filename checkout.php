<?php
// C:\xampp\htdocs\projectweb\sheronair\checkout.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/bootstrap_auth.php'; // ensures $_SESSION is ready
require_once __DIR__ . '/includes/db_connection.php';

// Require login
if (empty($_SESSION['user_id'])) {
  header('Location: ' . BASE_URL . '/auth/signin.php');
  exit;
}

// Input: flight_id, passengers, ticket_class (Economy | Premium Economy | Business | First)
$flight_id     = (int)($_GET['flight_id'] ?? 0);
$passengers    = (int)($_GET['passengers'] ?? 1);
$ticket_class  = trim($_GET['ticket_class'] ?? 'Economy');

if ($flight_id <= 0 || $passengers <= 0) {
  http_response_code(400);
  die('Invalid request');
}

// Sanitize class
$valid_classes = ['Economy','Premium Economy','Business','First'];
if (!in_array($ticket_class, $valid_classes, true)) {
  $ticket_class = 'Economy';
}

// Fetch flight + airport details
$sql = "
SELECT f.flight_id, f.flight_number, f.departure_time, f.arrival_time, f.base_price,
       ao.name AS origin_name, ao.code AS origin_code, ao.city AS origin_city, ao.country AS origin_country,
       ad.name AS dest_name,  ad.code AS dest_code,  ad.city AS dest_city,  ad.country AS dest_country
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
  http_response_code(404);
  die('Flight not found');
}

// Class multipliers
$classMult = [
  'Economy'          => 1.00,
  'Premium Economy'  => 1.30,
  'Business'         => 1.80,
  'First'            => 2.20,
];

$unit_price = (float)$flight['base_price'] * $classMult[$ticket_class];
$total      = $unit_price * $passengers;

// CSRF token for payment form
if (empty($_SESSION['csrf_pay'])) {
  $_SESSION['csrf_pay'] = bin2hex(random_bytes(32));
}
$CSRF = $_SESSION['csrf_pay'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Checkout | Sheron Airways</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
  <header class="bg-[#0A1A3F] text-white px-6 py-4">
    <div class="max-w-5xl mx-auto flex items-center justify-between">
      <a class="font-bold" href="<?php echo BASE_URL; ?>/index.php"><i class="fa fa-plane"></i> Sheron Airways</a>
      <div>Checkout</div>
    </div>
  </header>

  <main class="max-w-5xl mx-auto p-6 grid md:grid-cols-2 gap-6">
    <!-- Flight summary -->
    <section class="bg-white rounded-xl shadow p-5">
      <h2 class="text-lg font-semibold mb-4">Review your trip</h2>

      <div class="space-y-2 text-sm">
        <div><span class="font-medium">Flight:</span> <?php echo htmlspecialchars($flight['flight_number']); ?></div>
        <div>
          <span class="font-medium">Route:</span>
          <?php
            echo htmlspecialchars($flight['origin_city'] . ' (' . $flight['origin_code'] . ') → ' .
                                  $flight['dest_city']   . ' (' . $flight['dest_code']   . ')');
          ?>
        </div>
        <div><span class="font-medium">Departure:</span>
          <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($flight['departure_time']))); ?>
        </div>
        <div><span class="font-medium">Arrival:</span>
          <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($flight['arrival_time']))); ?>
        </div>
        <div><span class="font-medium">Class:</span> <?php echo htmlspecialchars($ticket_class); ?></div>
        <div><span class="font-medium">Passengers:</span> <?php echo (int)$passengers; ?></div>
      </div>

      <hr class="my-4">

      <div class="text-sm">
        <div class="flex justify-between">
          <span>Ticket price (per person)</span>
          <span>$<?php echo number_format($unit_price, 2); ?></span>
        </div>
        <div class="flex justify-between font-semibold text-lg mt-2">
          <span>Total</span>
          <span>$<?php echo number_format($total, 2); ?></span>
        </div>
      </div>
    </section>

    <!-- Card form -->
    <section class="bg-white rounded-xl shadow p-5">
      <h2 class="text-lg font-semibold mb-4">Pay with card</h2>

      <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="mb-3 p-3 rounded bg-red-50 text-red-700 text-sm">
          <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
        </div>
      <?php endif; ?>

      <form method="post" action="<?php echo BASE_URL; ?>/handle_payment.php" novalidate>
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($CSRF); ?>">
        <input type="hidden" name="flight_id" value="<?php echo (int)$flight_id; ?>">
        <input type="hidden" name="passengers" value="<?php echo (int)$passengers; ?>">
        <input type="hidden" name="ticket_class" value="<?php echo htmlspecialchars($ticket_class); ?>">

        <div class="mb-3">
          <label class="block text-sm font-medium mb-1">Cardholder name</label>
          <input class="w-full border rounded-lg p-3" name="card_name" required>
        </div>

        <div class="mb-3">
          <label class="block text-sm font-medium mb-1">Card number</label>
          <input class="w-full border rounded-lg p-3" name="card_number" inputmode="numeric" autocomplete="cc-number" placeholder="4111 1111 1111 1111" required>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-3">
          <div>
            <label class="block text-sm font-medium mb-1">Expiry (MM/YY)</label>
            <input class="w-full border rounded-lg p-3" name="card_expiry" placeholder="MM/YY" required>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">CVV</label>
            <input class="w-full border rounded-lg p-3" name="card_cvv" inputmode="numeric" maxlength="4" required>
          </div>
        </div>

        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-3 font-semibold">
          Pay $<?php echo number_format($total, 2); ?>
        </button>

        <p class="text-xs text-gray-500 mt-3">
          This is a demo card processor. For production, integrate Stripe/PayPal/etc. No card data is stored.
        </p>
      </form>
    </section>
  </main>
</body>
</html>
