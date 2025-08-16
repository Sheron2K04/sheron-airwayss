<?php
// C:\xampp\htdocs\projectweb\sheronair\payment_success.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/bootstrap_auth.php';
require_once __DIR__ . '/includes/db_connection.php';

// Require login
if (empty($_SESSION['user_id'])) {
  header('Location: ' . BASE_URL . '/auth/signin.php');
  exit;
}

$booking_id   = (int)($_GET['booking_id'] ?? 0);
$payment_id   = (int)($_GET['payment_id'] ?? 0);
$flight_id    = (int)($_GET['flight_id'] ?? 0);
$passengers   = (int)($_GET['passengers'] ?? 0);
$ticket_class = trim($_GET['ticket_class'] ?? 'Economy');

if ($booking_id <= 0 || $payment_id <= 0 || $flight_id <= 0) {
  http_response_code(400);
  die('Invalid receipt link.');
}

// Fetch receipt info
$sql = "
SELECT b.booking_reference, b.total_amount, b.booking_date,
       p.transaction_id, p.status as payment_status, p.payment_date,
       f.flight_number, f.departure_time, f.arrival_time,
       ao.city as origin_city, ao.code as origin_code,
       ad.city as dest_city,  ad.code as dest_code
FROM bookings b
JOIN payments p ON p.booking_id = b.booking_id
JOIN flights f   ON f.flight_id = b.flight_id
JOIN airports ao ON ao.airport_id = f.origin_id
JOIN airports ad ON ad.airport_id = f.destination_id
WHERE b.booking_id = :bid AND p.payment_id = :pid
LIMIT 1;
";
$stmt = $conn->prepare($sql);
$stmt->execute([':bid'=>$booking_id, ':pid'=>$payment_id]);
$rec = $stmt->fetch();

if (!$rec) {
  http_response_code(404);
  die('Receipt not found.');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Payment Success | Sheron Airways</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
  <header class="bg-[#0A1A3F] text-white px-6 py-4">
    <div class="max-w-4xl mx-auto flex justify-between items-center">
      <a class="font-bold" href="<?php echo BASE_URL; ?>/index.php">Sheron Airways</a>
      <div>Payment Success</div>
    </div>
  </header>

  <main class="max-w-4xl mx-auto p-6">
    <div class="bg-white rounded-xl shadow p-6">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Receipt</h1>
        <span class="px-3 py-1 text-sm rounded-full
          <?php echo $rec['payment_status']==='Completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>">
          <?php echo htmlspecialchars($rec['payment_status']); ?>
        </span>
      </div>

      <div class="grid md:grid-cols-2 gap-6 text-sm">
        <div>
          <h2 class="font-medium mb-2">Booking</h2>
          <div>Reference: <span class="font-mono"><?php echo htmlspecialchars($rec['booking_reference']); ?></span></div>
          <div>Date: <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($rec['booking_date']))); ?></div>
          <div>Passengers: <?php echo (int)$passengers; ?></div>
          <div>Class: <?php echo htmlspecialchars($ticket_class); ?></div>
          <div class="mt-2 font-semibold">Total paid: $<?php echo number_format($rec['total_amount'], 2); ?></div>
        </div>
        <div>
          <h2 class="font-medium mb-2">Payment</h2>
          <div>Transaction ID: <span class="font-mono"><?php echo htmlspecialchars($rec['transaction_id']); ?></span></div>
          <div>Date: <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($rec['payment_date']))); ?></div>
        </div>
      </div>

      <hr class="my-6">

      <div class="grid md:grid-cols-2 gap-6 text-sm">
        <div>
          <h2 class="font-medium mb-2">Flight</h2>
          <div>Number: <?php echo htmlspecialchars($rec['flight_number']); ?></div>
          <div>
            Route:
            <?php echo htmlspecialchars($rec['origin_city'] . ' (' . $rec['origin_code'] . ') → ' . $rec['dest_city'] . ' (' . $rec['dest_code'] . ')'); ?>
          </div>
        </div>
        <div>
          <h2 class="font-medium mb-2">Times</h2>
          <div>Departure: <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($rec['departure_time']))); ?></div>
          <div>Arrival:   <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($rec['arrival_time']))); ?></div>
        </div>
      </div>

      <div class="mt-6 flex gap-3">
        <a href="<?php echo BASE_URL; ?>/index.php" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700">Go to Home</a>
        <button onclick="window.print()" class="border px-5 py-2 rounded-lg hover:bg-gray-50">Print</button>
      </div>
    </div>
  </main>
</body>
</html>
