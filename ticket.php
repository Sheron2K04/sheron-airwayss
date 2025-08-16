<?php
// C:\xampp\htdocs\projectweb\sheronair\ticket.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/includes/config.php'; // should define BASE_URL and (optionally) APP_ENV

// Default APP_ENV if not set
if (!defined('APP_ENV')) {
  define('APP_ENV', 'prod'); // set to 'dev' in config.php for verbose errors
}

/** Render a friendly error page and exit */
function bad(string $msg, int $code = 400, ?string $dev = null): never {
  http_response_code($code);
  $safeMsg = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
  $devBlock = (APP_ENV === 'dev' && $dev)
    ? "<pre style='white-space:pre-wrap;background:#fff4;border:1px solid #eee;padding:.75rem;border-radius:8px;margin-top:.5rem;color:#6b7280;'>"
      . htmlspecialchars($dev, ENT_QUOTES, 'UTF-8')
      . "</pre>"
    : "";
  echo "<!doctype html><meta charset='utf-8'><body style='font-family:system-ui;background:#f8fafc'>
          <div style='max-width:720px;margin:3rem auto;padding:1.25rem;border:1px solid #e5e7eb;border-radius:12px;background:#fff'>
            <h1 style='margin:0 0 .25rem;font-size:1.25rem'>Ticket</h1>
            <p style='color:#ef4444;margin:.25rem 0 1rem 0;'>$safeMsg</p>
            $devBlock
            <a href='" . htmlspecialchars((defined('BASE_URL')?BASE_URL:'') . "/index.php", ENT_QUOTES, 'UTF-8') . "'>Back to home</a>
          </div>
        </body>";
  exit;
}

/* ------------------ Acquire PDO ($pdo) safely ------------------ */
$pdo = null;

// 1) includes/db_connection.php → should set $conn = new PDO(...)
$pathConn = __DIR__ . '/includes/db_connection.php';
if (is_file($pathConn)) {
  require_once $pathConn;
  if (isset($conn) && $conn instanceof PDO) {
    $pdo = $conn;
  }
}

// 2) config/database.php → may set $db = new PDO(...)
if (!$pdo) {
  $pathCfg = __DIR__ . '/config/database.php';
  if (is_file($pathCfg)) {
    require_once $pathCfg;
    if (isset($db) && $db instanceof PDO) {
      $pdo = $db;
    }
  }
}

if (!$pdo) {
  bad('Server error: database connection not available.', 500, 'No PDO handle from includes/db_connection.php or config/database.php');
}

// Ensure it’s really PDO (not mysqli)
if (!($pdo instanceof PDO)) {
  bad('Server error: invalid DB handle.', 500, 'Expected instance of PDO, got ' . get_debug_type($pdo));
}

// Reasonable PDO attributes
try {
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (Throwable $e) {
  // non-fatal
}

/* ---------------------- Inputs & validation --------------------- */
$flightId   = filter_input(INPUT_GET, 'flightId', FILTER_VALIDATE_INT) ?: 0;
$passengers = filter_input(INPUT_GET, 'passengers', FILTER_VALIDATE_INT) ?: 1;
$dateParam  = isset($_GET['date']) ? trim((string)$_GET['date']) : '';

if ($flightId <= 0) {
  bad('Missing or invalid flightId.', 400);
}
if ($passengers < 1) $passengers = 1;

// Date is optional (display only)
$travelDate = null;
if ($dateParam !== '') {
  $d = DateTime::createFromFormat('Y-m-d', $dateParam);
  $errs = DateTime::getLastErrors();
  if ($d && !$errs['warning_count'] && !$errs['error_count']) {
    $travelDate = $d;
  }
}

/* ------------------------ Query the flight ---------------------- */
try {
  $sql = "
    SELECT
      f.flight_id,
      f.flight_number,
      f.departure_time,
      f.arrival_time,
      f.base_price,
      ao.code     AS dep_code,
      ao.name     AS dep_name,
      ao.city     AS dep_city,
      ao.country  AS dep_country,
      ad.code     AS arr_code,
      ad.name     AS arr_name,
      ad.city     AS arr_city,
      ad.country  AS arr_country,
      ac.model    AS aircraft_model
    FROM flights f
    JOIN airports ao ON ao.airport_id = f.origin_id
    JOIN airports ad ON ad.airport_id = f.destination_id
    LEFT JOIN aircraft ac ON ac.aircraft_id = f.aircraft_id
    WHERE f.flight_id = :id
    LIMIT 1
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':id' => $flightId]);
  $flight = $st->fetch();
} catch (PDOException $e) {
  error_log('[ticket.php][PDO] '.$e->getCode().' '.$e->getMessage());
  bad('Server error while loading flight.', 500, $e->getCode() . ' ' . $e->getMessage());
}

if (!$flight) {
  bad('Flight not found.', 404);
}

/* ------------------------ Derived values ------------------------ */
try {
  $dep = new DateTime((string)$flight['departure_time']);
  $arr = new DateTime((string)$flight['arrival_time']);
} catch (Throwable $e) {
  $dep = new DateTime();
  $arr = (clone $dep)->modify('+2 hours');
}

$diff = $dep->diff($arr);
$hours = $diff->h + ($diff->d * 24);
$duration = sprintf('%dh %02dm', $hours, $diff->i);

$pricePer = (float)$flight['base_price'];
$subtotal = $pricePer * $passengers;
$taxes    = round($subtotal * 0.15, 2); // demo tax
$total    = round($subtotal + $taxes, 2);

$travelDateStr = ($travelDate ?: $dep)->format('D, j M Y');

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Ticket • Sheron Airways</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
  <header class="bg-[#0A1A3F] text-white">
    <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
      <a href="<?= htmlspecialchars((defined('BASE_URL')?BASE_URL:'') . '/index.php', ENT_QUOTES) ?>" class="font-semibold text-xl flex items-center gap-2">
        <i class="fa-solid fa-plane text-blue-400"></i> Sheron Airways
      </a>
      <div class="flex items-center gap-3">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-md text-sm">Print</button>
        <a href="<?= htmlspecialchars((defined('BASE_URL')?BASE_URL:'') . '/index.php', ENT_QUOTES) ?>" class="underline text-sm">Home</a>
      </div>
    </div>
  </header>

  <main class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
    <!-- Ticket Card -->
    <div class="bg-white shadow-lg rounded-2xl overflow-hidden">
      <div class="p-6 border-b">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-xs uppercase tracking-wider text-gray-500">Flight</div>
            <div class="text-2xl font-bold"><?= htmlspecialchars((string)$flight['flight_number'], ENT_QUOTES) ?></div>
          </div>
          <div class="text-right">
            <div class="text-xs uppercase tracking-wider text-gray-500">Travel Date</div>
            <div class="text-lg font-semibold"><?= htmlspecialchars($travelDateStr, ENT_QUOTES) ?></div>
          </div>
        </div>
      </div>

      <div class="p-6 grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div>
          <div class="text-xs uppercase tracking-wider text-gray-500">From</div>
          <div class="text-3xl font-extrabold"><?= htmlspecialchars((string)$flight['dep_code'], ENT_QUOTES) ?></div>
          <div class="text-sm text-gray-600">
            <?= htmlspecialchars((string)$flight['dep_city'], ENT_QUOTES) ?> — <?= htmlspecialchars((string)$flight['dep_name'], ENT_QUOTES) ?>
          </div>
          <div class="mt-2 text-gray-500 text-sm"><?= htmlspecialchars((string)$flight['dep_country'], ENT_QUOTES) ?></div>
        </div>

        <div class="flex items-center justify-center">
          <div class="text-center">
            <div class="text-xs uppercase tracking-wider text-gray-500">Duration</div>
            <div class="font-semibold"><?= htmlspecialchars($duration, ENT_QUOTES) ?></div>
            <div class="w-40 h-px bg-gray-300 my-2"></div>
            <div class="text-xs text-gray-500">Non-stop</div>
          </div>
        </div>

        <div class="text-right">
          <div class="text-xs uppercase tracking-wider text-gray-500">To</div>
          <div class="text-3xl font-extrabold"><?= htmlspecialchars((string)$flight['arr_code'], ENT_QUOTES) ?></div>
          <div class="text-sm text-gray-600">
            <?= htmlspecialchars((string)$flight['arr_city'], ENT_QUOTES) ?> — <?= htmlspecialchars((string)$flight['arr_name'], ENT_QUOTES) ?>
          </div>
          <div class="mt-2 text-gray-500 text-sm"><?= htmlspecialchars((string)$flight['arr_country'], ENT_QUOTES) ?></div>
        </div>
      </div>

      <div class="px-6 pb-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 bg-gray-50 rounded-xl p-4">
          <div>
            <div class="text-xs uppercase tracking-wider text-gray-500">Departure</div>
            <div class="font-semibold"><?= $dep->format('D, j M Y • H:i') ?></div>
          </div>
          <div>
            <div class="text-xs uppercase tracking-wider text-gray-500">Arrival</div>
            <div class="font-semibold"><?= $arr->format('D, j M Y • H:i') ?></div>
          </div>
          <div>
            <div class="text-xs uppercase tracking-wider text-gray-500">Aircraft</div>
            <div class="font-semibold"><?= htmlspecialchars((string)($flight['aircraft_model'] ?: '—'), ENT_QUOTES) ?></div>
          </div>
        </div>
      </div>

      <div class="px-6 pb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">Fare Summary</h3>
        <div class="bg-gray-50 rounded-xl p-4">
          <div class="flex justify-between text-sm">
            <span><?= (int)$passengers ?> Passenger<?= $passengers > 1 ? 's' : '' ?> × €<?= number_format($pricePer, 2) ?></span>
            <span>€<?= number_format($subtotal, 2) ?></span>
          </div>
          <div class="flex justify-between text-sm mt-1">
            <span>Taxes & Fees (15%)</span>
            <span>€<?= number_format($taxes, 2) ?></span>
          </div>
          <div class="flex justify-between font-bold border-t pt-2 mt-2">
            <span>Total</span>
            <span>€<?= number_format($total, 2) ?></span>
          </div>
        </div>
      </div>

      <div class="px-6 pb-8">
        <div class="text-xs text-gray-500">
          <p>• 30kg checked baggage included • Meals included • In-flight entertainment</p>
          <p>• Non-refundable within 24 hours of departure. Name change not permitted.</p>
        </div>
      </div>
    </div>

    <div class="mt-6 flex items-center justify-between">
      <a href="<?= htmlspecialchars((defined('BASE_URL')?BASE_URL:'') . '/search_results.php', ENT_QUOTES) ?>" class="text-blue-600 hover:text-blue-800">
        ← Back to search results
      </a>
      <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg">
        Print Ticket
      </button>
    </div>
  </main>

  <script src="https://kit.fontawesome.com/a2e0e6ad5b.js" crossorigin="anonymous"></script>
</body>
</html>
