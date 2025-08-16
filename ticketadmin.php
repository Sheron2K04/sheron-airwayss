<?php
// C:\xampp\htdocs\projectweb\sheronair\ticketadmin.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/bootstrap_auth.php';
require_once __DIR__ . '/includes/db_connection.php';

// ---------- AuthZ: only ticket_admin and super_admin may access ----------
if (empty($_SESSION['user_id']) || !in_array(($_SESSION['role'] ?? ''), ['ticket_admin','super_admin'], true)) {
  header('Location: ' . BASE_URL . '/auth/signin.php');
  exit;
}

// Helpers
function flash(string $key): ?string {
  if (!empty($_SESSION[$key])) {
    $v = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $v;
  }
  return null;
}
function set_flash(string $key, string $msg): void { $_SESSION[$key] = $msg; }

// CSRF
if (empty($_SESSION['csrf_tickets'])) {
  $_SESSION['csrf_tickets'] = bin2hex(random_bytes(32));
}
$CSRF = $_SESSION['csrf_tickets'];

// ---------- Handle Add Ticket POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_ticket') {
  // CSRF check
  if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf_tickets'], (string)$_POST['csrf'])) {
    set_flash('flash_error', 'Invalid form. Please try again.');
    header('Location: ' . BASE_URL . '/ticketadmin.php');
    exit;
  }

  $passenger_name = trim($_POST['passenger_name'] ?? '');
  $flight_id      = (int)($_POST['flight_id'] ?? 0);
  $seat_number    = strtoupper(trim($_POST['seat_number'] ?? ''));
  $price          = (float)($_POST['price'] ?? 0);
  $status         = trim($_POST['status'] ?? 'Confirmed'); // Confirmed | Pending | Cancelled

  if ($passenger_name === '' || $flight_id <= 0 || $seat_number === '' || $price <= 0) {
    set_flash('flash_error', 'Please fill passenger name, flight, seat, and a positive price.');
    header('Location: ' . BASE_URL . '/ticketadmin.php');
    exit;
  }

  // Normalize/validate status to booking.status enum
  $statusMap = ['Confirmed','Cancelled','Completed'];
  if (!in_array($status, ['Confirmed','Pending','Cancelled'], true)) {
    $status = 'Confirmed';
  }
  // Map "Pending" to a safe booking status (there is no "Pending" in your booking CHECK)
  $bookingStatus = $status === 'Pending' ? 'Confirmed' : $status;
  if (!in_array($bookingStatus, $statusMap, true)) {
    $bookingStatus = 'Confirmed';
  }

  try {
    // Fetch flight + aircraft
    $q = $conn->prepare("
      SELECT f.flight_id, f.flight_number, f.aircraft_id,
             ao.city AS origin_city, ao.code AS origin_code,
             ad.city AS dest_city,  ad.code AS dest_code
      FROM flights f
      JOIN airports ao ON ao.airport_id = f.origin_id
      JOIN airports ad ON ad.airport_id = f.destination_id
      WHERE f.flight_id = :fid
      LIMIT 1
    ");
    $q->execute([':fid' => $flight_id]);
    $flight = $q->fetch();
    if (!$flight) {
      throw new RuntimeException('Flight not found.');
    }

    // Check the seat exists on that aircraft and is available
    $seatStmt = $conn->prepare("
      SELECT s.seat_id, s.class, s.is_available
      FROM seats s
      WHERE s.aircraft_id = :aid AND s.seat_number = :sn
      LIMIT 1
    ");
    $seatStmt->execute([':aid' => (int)$flight['aircraft_id'], ':sn' => $seat_number]);
    $seat = $seatStmt->fetch();
    if (!$seat) {
      throw new RuntimeException('Seat does not exist on this aircraft.');
    }
    if (!$seat['is_available']) {
      throw new RuntimeException('Seat is already taken.');
    }

    // Begin transaction
    $conn->beginTransaction();

    // Create a booking (attribute to current admin or NULL if you prefer)
    $bookingRef = 'BR' . str_pad((string)random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
    $insB = $conn->prepare("
      INSERT INTO bookings (booking_reference, user_id, flight_id, total_amount, status)
      VALUES (:ref, :uid, :fid, :amount, :status)
      RETURNING booking_id
    ");
    $insB->execute([
      ':ref'    => $bookingRef,
      ':uid'    => (int)$_SESSION['user_id'],  // or null if you want it not tied to a user
      ':fid'    => (int)$flight_id,
      ':amount' => $price,
      ':status' => $bookingStatus,
    ]);
    $booking_id = (int)$insB->fetchColumn();

    // Create passenger (minimal: full_name + seat_id). Passport is optional here.
    $insP = $conn->prepare("
      INSERT INTO passengers (booking_id, full_name, passport_number, seat_id)
      VALUES (:bid, :name, :pp, :sid)
      RETURNING passenger_id
    ");
    $insP->execute([
      ':bid'  => $booking_id,
      ':name' => $passenger_name,
      ':pp'   => 'N/A', // you can extend the form to collect this
      ':sid'  => (int)$seat['seat_id'],
    ]);
    $passenger_id = (int)$insP->fetchColumn();

    // Generate ticket number
    $ticket_number = 'TK-' . str_pad((string)random_int(0, 999999999), 9, '0', STR_PAD_LEFT);

    // Insert ticket (use seat class as ticket_class)
    $insT = $conn->prepare("
      INSERT INTO tickets (ticket_number, passenger_id, booking_id, flight_id, ticket_class, boarding_pass_issued)
      VALUES (:tno, :pid, :bid, :fid, :tclass, FALSE)
      RETURNING ticket_id
    ");
    $insT->execute([
      ':tno'    => $ticket_number,
      ':pid'    => $passenger_id,
      ':bid'    => $booking_id,
      ':fid'    => (int)$flight_id,
      ':tclass' => (string)$seat['class'], // 'Economy' | 'Premium Economy' | 'Business' | 'First'
    ]);
    $ticket_id = (int)$insT->fetchColumn();

    // Mark seat unavailable (your trigger will also handle on passengers insert; this is a guard)
    $updSeat = $conn->prepare("UPDATE seats SET is_available = FALSE WHERE seat_id = :sid");
    $updSeat->execute([':sid' => (int)$seat['seat_id']]);

    $conn->commit();

    set_flash('flash_ok', "Ticket created: <strong>{$ticket_number}</strong> for <strong>" . htmlspecialchars($passenger_name) . '</strong>.');
    header('Location: ' . BASE_URL . '/ticketadmin.php');
    exit;

  } catch (Throwable $e) {
    if ($conn->inTransaction()) { $conn->rollBack(); }
    error_log('[TicketAdmin] ' . $e->getMessage());
    set_flash('flash_error', $e->getMessage());
    header('Location: ' . BASE_URL . '/ticketadmin.php');
    exit;
  }
}

// ---------- Load flights for the Add Ticket modal select ----------
$flights = [];
try {
  $f = $conn->query("
    SELECT f.flight_id, f.flight_number,
           ao.city AS origin_city, ao.code AS origin_code,
           ad.city AS dest_city,  ad.code AS dest_code
    FROM flights f
    JOIN airports ao ON ao.airport_id = f.origin_id
    JOIN airports ad ON ad.airport_id = f.destination_id
    ORDER BY f.departure_time ASC
  ");
  $flights = $f->fetchAll();
} catch (Throwable $e) {
  $flights = [];
}

// ---------- Load recent tickets list ----------
$tickets = [];
try {
  $t = $conn->query("
    SELECT t.ticket_id, t.ticket_number, t.ticket_class,
           p.full_name AS passenger_name,
           s.seat_id, s.seat_number,
           b.total_amount, b.status AS booking_status,
           f.flight_number,
           ao.code AS origin_code, ad.code AS dest_code
    FROM tickets t
    JOIN passengers p ON p.passenger_id = t.passenger_id
    JOIN bookings b   ON b.booking_id   = t.booking_id
    JOIN flights f    ON f.flight_id    = t.flight_id
    JOIN seats s      ON s.seat_id      = p.seat_id
    JOIN airports ao  ON ao.airport_id  = f.origin_id
    JOIN airports ad  ON ad.airport_id  = f.destination_id
    ORDER BY t.ticket_id DESC
    LIMIT 50
  ");
  $tickets = $t->fetchAll();
} catch (Throwable $e) {
  $tickets = [];
}

// Flash messages
$flash_error = flash('flash_error');
$flash_ok    = flash('flash_ok');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Ticket Admin | Sheron Airways</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(135deg, #0A1A3F 0%, #142857 100%); }
    .ticket-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06); }
    .sidebar { transition: all .3s ease; }
  </style>
</head>
<body class="min-h-screen font-sans text-gray-800">
  <!-- Mobile Sidebar Toggle -->
  <div class="md:hidden fixed top-4 right-4 z-50">
    <button id="sidebarToggle" class="p-2 rounded-md bg-blue-700 text-white">
      <i class="fas fa-bars"></i>
    </button>
  </div>

  <!-- Sidebar -->
  <div id="sidebar" class="sidebar fixed inset-y-0 left-0 z-40 w-64 bg-[#0A1A3F] text-white transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
    <div class="flex items-center justify-center h-16 px-4 border-b border-blue-900/40">
      <div class="flex items-center">
        <i class="fas fa-plane text-blue-300 mr-2 rotate-45"></i>
        <span class="text-xl font-bold">SHERON AIRWAYS</span>
      </div>
    </div>
    <div class="flex flex-col h-full p-4">
      <nav class="flex-1 space-y-2 mt-6">
        <a href="<?php echo BASE_URL; ?>/index.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white/10">
          <i class="fas fa-home mr-3"></i> Home
        </a>
        <a href="<?php echo BASE_URL; ?>/ticketadmin.php" class="flex items-center px-4 py-3 rounded-lg bg-white/10">
          <i class="fas fa-ticket-alt mr-3"></i> Tickets
        </a>
        <a href="<?php echo BASE_URL; ?>/flightmanagementadmin.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white/10">
          <i class="fas fa-plane mr-3"></i> Flights
        </a>
      </nav>
      <div class="mt-auto pb-2">
        <a href="<?php echo BASE_URL; ?>/logout.php" class="w-full flex items-center justify-center px-4 py-3 rounded-lg bg-red-600 hover:bg-red-700 text-white">
          <i class="fas fa-sign-out-alt mr-2"></i> Logout
        </a>
      </div>
    </div>
  </div>

  <!-- Main -->
  <main class="md:ml-64 min-h-screen">
    <div class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-900">Ticket Management</h1>
        <div class="text-sm text-gray-600">
          Logged in as <strong><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Admin'); ?></strong>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Flash -->
      <?php if ($flash_error): ?>
        <div class="mb-4 p-3 rounded bg-red-50 text-red-700"><?php echo $flash_error; ?></div>
      <?php endif; ?>
      <?php if ($flash_ok): ?>
        <div class="mb-4 p-3 rounded bg-green-50 text-green-700"><?php echo $flash_ok; ?></div>
      <?php endif; ?>

      <!-- Header / Add button -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
          <h2 class="text-2xl font-bold text-white">Manage Tickets</h2>
          <p class="text-blue-200">View, add, edit, and delete flight tickets</p>
        </div>
        <button onclick="openAddTicketModal()" class="mt-4 md:mt-0 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white shadow">
          <i class="fas fa-plus mr-2"></i> Add New Ticket
        </button>
      </div>

      <!-- Tickets Table -->
      <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticket #</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Passenger</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flight</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Seat</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Booking Status</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php if (!$tickets): ?>
                <tr><td colspan="7" class="px-6 py-6 text-center text-gray-500">No tickets yet.</td></tr>
              <?php else: foreach ($tickets as $row): ?>
                <tr class="ticket-card hover:bg-gray-50">
                  <td class="px-6 py-4 text-sm font-medium text-gray-900">
                    <i class="fas fa-ticket-alt text-blue-500 mr-2"></i><?php echo htmlspecialchars($row['ticket_number']); ?>
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <?php echo htmlspecialchars($row['passenger_name']); ?>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-700">
                    <?php echo htmlspecialchars($row['flight_number'] . ' (' . $row['origin_code'] . ' → ' . $row['dest_code'] . ')'); ?>
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full"><?php echo htmlspecialchars($row['seat_number']); ?></span>
                  </td>
                  <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($row['ticket_class']); ?></td>
                  <td class="px-6 py-4 text-sm font-semibold">$<?php echo number_format((float)$row['total_amount'], 2); ?></td>
                  <td class="px-6 py-4 text-sm">
                    <?php
                      $badge = ['Confirmed'=>'green','Cancelled'=>'red','Completed'=>'blue'][$row['booking_status']] ?? 'gray';
                    ?>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $badge; ?>-100 text-<?php echo $badge; ?>-800">
                      <?php echo htmlspecialchars($row['booking_status']); ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <!-- Add Ticket Modal -->
  <div id="addTicketModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
      <div class="fixed inset-0 bg-black/40" aria-hidden="true"></div>
      <div class="relative bg-white rounded-lg text-left shadow-xl w-full max-w-lg">
        <form method="post" action="<?php echo BASE_URL; ?>/ticketadmin.php">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($CSRF); ?>">
          <input type="hidden" name="action" value="add_ticket">

          <div class="px-6 pt-6 pb-4">
            <h3 class="text-lg font-medium text-gray-900">Add New Ticket</h3>

            <div class="mt-4 space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Passenger full name</label>
                <input name="passenger_name" class="mt-1 w-full border rounded-md px-3 py-2" required>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Flight</label>
                <select name="flight_id" class="mt-1 w-full border rounded-md px-3 py-2" required>
                  <option value="">Select flight</option>
                  <?php foreach ($flights as $f): ?>
                    <option value="<?php echo (int)$f['flight_id']; ?>">
                      <?php
                        echo htmlspecialchars($f['flight_number'] . ' (' . $f['origin_code'] . ' → ' . $f['dest_code'] . ')');
                      ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Seat number</label>
                  <input name="seat_number" class="mt-1 w-full border rounded-md px-3 py-2" placeholder="e.g. 12A" required>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Price ($)</label>
                  <input name="price" type="number" min="1" step="0.01" class="mt-1 w-full border rounded-md px-3 py-2" required>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" class="mt-1 w-full border rounded-md px-3 py-2">
                  <option value="Confirmed">Confirmed</option>
                  <option value="Pending">Pending</option>
                  <option value="Cancelled">Cancelled</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Saved as booking status (Pending is stored as Confirmed due to DB constraints).</p>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 px-6 py-4 flex justify-end gap-2">
            <button type="button" onclick="closeModal('addTicketModal')" class="px-4 py-2 rounded border">Cancel</button>
            <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Add Ticket</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Toggle sidebar
    document.getElementById('sidebarToggle').addEventListener('click', () => {
      document.getElementById('sidebar').classList.toggle('-translate-x-full');
    });

    // Modals
    function openAddTicketModal() {
      document.getElementById('addTicketModal').classList.remove('hidden');
    }
    function closeModal(id) {
      document.getElementById(id).classList.add('hidden');
    }
  </script>
</body>
</html>
