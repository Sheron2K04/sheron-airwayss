<?php
// C:\xampp\htdocs\projectweb\sheronair\flightmanagementadmin.php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/bootstrap_auth.php'; // starts session + remember-me
require_once __DIR__ . '/includes/db_connection.php';

// ---- Access Control: only flight_admin or super_admin ----
if (empty($_SESSION['user_id']) || !in_array(($_SESSION['role'] ?? ''), ['flight_admin','super_admin'], true)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Forbidden</title></head><body style="font-family:sans-serif;padding:24px;">';
    echo '<h1>403 — Access denied</h1><p>You must be a Flight Admin or Super Admin to view this page.</p>';
    echo '<p><a href="'.htmlspecialchars(BASE_URL).'/auth/signin.php">Sign in</a></p>';
    echo '</body></html>';
    exit;
}

// Flash helpers
function set_flash(string $type, string $msg): void {
    $_SESSION["flash_$type"] = $msg;
}
function get_flash(string $type): ?string {
    $k = "flash_$type";
    if (!empty($_SESSION[$k])) { $m = $_SESSION[$k]; unset($_SESSION[$k]); return $m; }
    return null;
}

// ---- Handle Add Flight POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_flight') {
    $flight_number   = strtoupper(trim($_POST['flight_number'] ?? ''));
    $origin_id       = (int)($_POST['origin_id'] ?? 0);
    $destination_id  = (int)($_POST['destination_id'] ?? 0);
    $departure_dt    = trim($_POST['departure_time'] ?? '');
    $arrival_dt      = trim($_POST['arrival_time'] ?? '');
    $aircraft_id     = (int)($_POST['aircraft_id'] ?? 0);
    $base_price_str  = trim($_POST['base_price'] ?? '');

    // Basic validations
    if ($flight_number === '' || !$origin_id || !$destination_id || $departure_dt === '' || $arrival_dt === '' || !$aircraft_id || $base_price_str === '') {
        set_flash('error', 'Please fill in all required fields.');
        header('Location: ' . BASE_URL . '/flightmanagementadmin.php');
        exit;
    }
    if ($origin_id === $destination_id) {
        set_flash('error', 'Departure and Arrival airports must be different.');
        header('Location: ' . BASE_URL . '/flightmanagementadmin.php');
        exit;
    }
    if (!is_numeric($base_price_str) || (float)$base_price_str <= 0) {
        set_flash('error', 'Base price must be a positive number.');
        header('Location: ' . BASE_URL . '/flightmanagementadmin.php');
        exit;
    }

    // Normalize datetime (HTML datetime-local uses "YYYY-MM-DDTHH:MM")
    // We accept it as-is; PostgreSQL can parse "YYYY-MM-DD HH:MM" or "YYYY-MM-DDTHH:MM"
    $departure_time = str_replace('T', ' ', $departure_dt) . ':00';
    $arrival_time   = str_replace('T', ' ', $arrival_dt) . ':00';

    // Ensure departure < arrival
    try {
        $dep = new DateTime($departure_time);
        $arr = new DateTime($arrival_time);
        if ($arr <= $dep) {
            set_flash('error', 'Arrival time must be after departure time.');
            header('Location: ' . BASE_URL . '/flightmanagementadmin.php');
            exit;
        }
    } catch (Throwable $e) {
        set_flash('error', 'Invalid date/time provided.');
        header('Location: ' . BASE_URL . '/flightmanagementadmin.php');
        exit;
    }

    // Insert flight
    try {
        $sql = "
            INSERT INTO flights
                (flight_number, origin_id, destination_id, departure_time, arrival_time, aircraft_id, status, base_price)
            VALUES
                (:flight_number, :origin_id, :destination_id, :departure_time, :arrival_time, :aircraft_id, 'Scheduled', :base_price)
            RETURNING flight_id
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':flight_number'  => $flight_number,
            ':origin_id'      => $origin_id,
            ':destination_id' => $destination_id,
            ':departure_time' => $departure_time,
            ':arrival_time'   => $arrival_time,
            ':aircraft_id'    => $aircraft_id,
            ':base_price'     => (float)$base_price_str,
        ]);
        $new_id = $stmt->fetchColumn();
        set_flash('ok', 'Flight added successfully (ID: ' . (int)$new_id . ').');
    } catch (Throwable $e) {
        error_log('Add flight error: ' . $e->getMessage());
        // Surface a friendly message; common failures: duplicate flight_number or bad FK ids
        set_flash('error', 'Could not add flight. Check that the flight number is unique and all selections are valid.');
    }

    header('Location: ' . BASE_URL . '/flightmanagementadmin.php');
    exit;
}

// ---- Fetch dropdown data ----
$airports  = [];
$aircrafts = [];
try {
    $airports  = $conn->query("SELECT airport_id, code, city, name FROM airports ORDER BY city, code")->fetchAll();
    $aircrafts = $conn->query("SELECT aircraft_id, manufacturer, model FROM aircraft ORDER BY manufacturer, model")->fetchAll();
} catch (Throwable $e) {
    error_log('Fetch dropdowns error: ' . $e->getMessage());
}

// ---- Fetch Active Flights (recent first) ----
$flights = [];
try {
    $sql = "
        SELECT f.flight_id, f.flight_number, f.departure_time, f.arrival_time, f.status, f.base_price,
               ao.code AS origin_code, ao.city AS origin_city,
               ad.code AS dest_code,  ad.city AS dest_city,
               ac.model AS aircraft_model
        FROM flights f
        JOIN airports ao ON ao.airport_id = f.origin_id
        JOIN airports ad ON ad.airport_id = f.destination_id
        JOIN aircraft ac ON ac.aircraft_id = f.aircraft_id
        WHERE f.status IN ('Scheduled','Delayed','Departed')
        ORDER BY f.departure_time DESC
        LIMIT 50
    ";
    $flights = $conn->query($sql)->fetchAll();
} catch (Throwable $e) {
    error_log('Fetch flights error: ' . $e->getMessage());
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sheron Airways - Flight Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .font-display { font-family: Arial, sans-serif; }
    .sidebar { background-color:#1e3a8a; }
    .navy-gradient { background:linear-gradient(135deg,#1e3a8a 0%,#1e40af 100%); }
    body { background-color:#1e3a8a; }
    .main-content-area { background-color:#f0f4f8; }
    .card-bg { background-color:#ffffff; }
  </style>
</head>
<body class="font-sans">
<div class="flex h-screen overflow-hidden">
  <!-- Sidebar -->
  <div id="sidebar" class="sidebar fixed inset-y-0 left-0 z-40 w-64 text-white transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
    <div class="flex items-center justify-center h-16 px-4 border-b border-blue-900 navy-gradient">
      <div class="flex items-center font-display">
        <i class="fas fa-plane text-blue-300 mr-2 rotate-45"></i>
        <span class="text-xl font-bold">SHERON AIRWAYS</span>
      </div>
    </div>
    <div class="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto">
      <div class="px-4 py-6 flex items-center space-x-4 border-b border-blue-900/60">
        <div class="relative">
          <img id="userAvatar" class="h-12 w-12 rounded-full object-cover cursor-pointer"
               src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['first_name'] ?? 'Admin'); ?>+<?php echo urlencode($_SESSION['last_name'] ?? 'User'); ?>&background=1e40af&color=fff"
               alt="Admin User">
        </div>
        <div>
          <h3 id="userFullName" class="text-sm font-medium text-white">
            <?php echo h(($_SESSION['first_name'] ?? 'Admin') . ' ' . ($_SESSION['last_name'] ?? 'User')); ?>
          </h3>
          <p id="userEmail" class="text-xs text-blue-200"><?php echo h($_SESSION['email'] ?? 'admin@sheronairways.com'); ?></p>
          <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-blue-600 text-white">
            <?php echo h($_SESSION['role'] ?? 'admin'); ?>
          </span>
        </div>
      </div>
      <nav class="flex-1 px-2 space-y-1 mt-4">
        <a href="#" class="flex items-center px-4 py-2 text-sm font-medium text-blue-100 bg-blue-900/40 rounded-md">
          <i class="fas fa-tachometer-alt mr-3 text-blue-300"></i> Dashboard
        </a>
        <a href="#" class="flex items-center px-4 py-2 text-sm font-medium text-blue-200 hover:text-white hover:bg-blue-900/40 rounded-md">
          <i class="fas fa-users mr-3 text-blue-300"></i> Passengers
        </a>
        <a href="#" class="flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-900/60 rounded-md">
          <i class="fas fa-plane-departure mr-3 text-blue-300"></i> Flights
        </a>
        <a href="#" class="flex items-center px-4 py-2 text-sm font-medium text-blue-200 hover:text-white hover:bg-blue-900/40 rounded-md">
          <i class="fas fa-route mr-3 text-blue-300"></i> Routes
        </a>
        <a href="#" class="flex items-center px-4 py-2 text-sm font-medium text-blue-200 hover:text-white hover:bg-blue-900/40 rounded-md">
          <i class="fas fa-chart-bar mr-3 text-blue-300"></i> Analytics
        </a>
        <a href="#" class="flex items-center px-4 py-2 text-sm font-medium text-blue-200 hover:text-white hover:bg-blue-900/40 rounded-md">
          <i class="fas fa-cog mr-3 text-blue-300"></i> Settings
        </a>
      </nav>
    </div>
    <div class="p-4 border-t border-blue-900/60">
      <a href="<?php echo BASE_URL; ?>/logout.php"
         class="w-full flex items-center justify-center px-4 py-2 rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700">
        <i class="fas fa-sign-out-alt mr-2"></i> Logout
      </a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="flex flex-col flex-1 overflow-hidden">
    <!-- Top Navigation -->
    <header class="flex items-center justify-between px-6 py-4 bg-white border-b border-gray-200">
      <div class="flex items-center">
        <button id="sidebarToggle" class="text-gray-500 focus:outline-none md:hidden"><i class="fas fa-bars"></i></button>
        <div class="flex items-center ml-4 font-display">
          <i class="fas fa-plane text-blue-500 mr-2 rotate-45"></i>
          <h1 class="text-xl font-bold text-gray-800">
            SHERON AIRWAYS <span class="font-normal text-gray-600">| Flight Management</span>
          </h1>
        </div>
      </div>
      <div class="flex items-center space-x-4">
        <div class="relative">
          <div class="flex items-center space-x-2 cursor-pointer" id="userMenuButton">
            <span class="hidden md:inline text-sm font-medium text-gray-700">
              <?php echo h($_SESSION['first_name'] ?? 'Admin'); ?>
            </span>
            <img class="h-8 w-8 rounded-full"
                 src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['first_name'] ?? 'Admin'); ?>&background=1e40af&color=fff"
                 alt="Admin User">
            <i class="fas fa-chevron-down text-gray-500 text-xs"></i>
          </div>
          <!-- Dropdown -->
          <div id="userDropdown" class="hidden absolute right-0 mt-2 w-56 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50">
            <div class="py-1">
              <div class="px-4 py-3 border-b border-gray-100">
                <p class="text-sm font-medium text-gray-900" id="dropdownUserName">
                  <?php echo h(($_SESSION['first_name'] ?? 'Admin') . ' ' . ($_SESSION['last_name'] ?? '')); ?>
                </p>
                <p class="text-xs text-gray-500 truncate" id="dropdownUserEmail"><?php echo h($_SESSION['email'] ?? ''); ?></p>
              </div>
              <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-user mr-2 text-gray-500"></i>Your Profile</a>
              <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-cog mr-2 text-gray-500"></i>Settings</a>
              <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-bell mr-2 text-gray-500"></i>Notifications</a>
              <div class="border-t border-gray-100"></div>
              <a href="<?php echo BASE_URL; ?>/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                <i class="fas fa-sign-out-alt mr-2"></i> Sign out
              </a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-x-hidden overflow-y-auto main-content-area p-6">
      <!-- Flash messages -->
      <?php if ($m = get_flash('ok')): ?>
        <div class="mb-4 p-3 rounded bg-green-50 text-green-700 border border-green-200"><?php echo h($m); ?></div>
      <?php endif; ?>
      <?php if ($m = get_flash('error')): ?>
        <div class="mb-4 p-3 rounded bg-red-50 text-red-700 border border-red-200"><?php echo h($m); ?></div>
      <?php endif; ?>

      <!-- Page Header -->
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Flight Operations</h2>
        <div class="flex space-x-3">
          <a href="<?php echo BASE_URL; ?>/flightmanagementadmin.php"
             class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
            <i class="fas fa-sync-alt mr-2"></i> Refresh
          </a>
        </div>
      </div>

      <!-- Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        <!-- Add Flight Card -->
        <div class="card-bg rounded-lg shadow overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
              <i class="fas fa-plus-circle text-blue-500 mr-2"></i> Add New Flight
            </h3>
          </div>
          <div class="p-6">
            <form method="post" action="<?php echo BASE_URL; ?>/flightmanagementadmin.php" autocomplete="off">
              <input type="hidden" name="action" value="add_flight">
              <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2" for="flight-number">Flight Number</label>
                <input class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                       id="flight-number" name="flight_number" type="text" placeholder="SHA123" required>
              </div>

              <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-gray-700 text-sm font-medium mb-2" for="departure">Departure</label>
                  <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          id="departure" name="origin_id" required>
                    <option value="">Select Airport</option>
                    <?php foreach ($airports as $a): ?>
                      <option value="<?php echo (int)$a['airport_id']; ?>">
                        <?php echo h($a['code'] . ' - ' . $a['city'] . ' (' . $a['name'] . ')'); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="block text-gray-700 text-sm font-medium mb-2" for="arrival">Arrival</label>
                  <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          id="arrival" name="destination_id" required>
                    <option value="">Select Airport</option>
                    <?php foreach ($airports as $a): ?>
                      <option value="<?php echo (int)$a['airport_id']; ?>">
                        <?php echo h($a['code'] . ' - ' . $a['city'] . ' (' . $a['name'] . ')'); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-gray-700 text-sm font-medium mb-2" for="departure-time">Departure Time</label>
                  <input class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                         id="departure-time" name="departure_time" type="datetime-local" required>
                </div>
                <div>
                  <label class="block text-gray-700 text-sm font-medium mb-2" for="arrival-time">Arrival Time</label>
                  <input class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                         id="arrival-time" name="arrival_time" type="datetime-local" required>
                </div>
              </div>

              <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2" for="aircraft">Aircraft</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        id="aircraft" name="aircraft_id" required>
                  <option value="">Select Aircraft</option>
                  <?php foreach ($aircrafts as $ac): ?>
                    <option value="<?php echo (int)$ac['aircraft_id']; ?>">
                      <?php echo h($ac['manufacturer'] . ' ' . $ac['model']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-6">
                <label class="block text-gray-700 text-sm font-medium mb-2" for="base-price">Base Price (USD)</label>
                <input class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                       id="base-price" name="base_price" type="number" step="0.01" min="1" placeholder="e.g. 499.00" required>
              </div>

              <button type="submit"
                      class="w-full flex justify-center py-2 px-4 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                Add Flight
              </button>
            </form>
          </div>
        </div>

        <!-- Update Flight (placeholder UI; hook up later if needed) -->
        <div class="card-bg rounded-lg shadow overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
              <i class="fas fa-edit text-green-500 mr-2"></i> Update Flight
            </h3>
          </div>
          <div class="p-6 text-gray-600">
            <p>Coming soon: update flight status & schedules.</p>
          </div>
        </div>

        <!-- Flight Routes (placeholder UI) -->
        <div class="card-bg rounded-lg shadow overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
              <i class="fas fa-route text-purple-500 mr-2"></i> Manage Flight Routes
            </h3>
          </div>
          <div class="p-6 text-gray-600">
            <p>Coming soon: define and reuse route templates.</p>
          </div>
        </div>
      </div>

      <!-- Active Flights Table -->
      <div class="card-bg shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">
            <i class="fas fa-plane-departure text-blue-500 mr-2"></i> Active Flights
          </h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flight #</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Departure</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Arrival</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aircraft</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Base Price</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php if (!$flights): ?>
                <tr><td colspan="7" class="px-6 py-4 text-sm text-gray-500">No active flights yet.</td></tr>
              <?php else: ?>
                <?php foreach ($flights as $f): ?>
                  <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo h($f['flight_number']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                      <?php echo h($f['origin_code'] . ' (' . $f['origin_city'] . ')'); ?> →
                      <?php echo h($f['dest_code']   . ' (' . $f['dest_city']   . ')'); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                      <?php echo h((new DateTime($f['departure_time']))->format('Y-m-d H:i')); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                      <?php echo h((new DateTime($f['arrival_time']))->format('Y-m-d H:i')); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <?php
                        $st = $f['status'];
                        $badge = ['Scheduled'=>'bg-gray-100 text-gray-800','Delayed'=>'bg-yellow-100 text-yellow-800','Departed'=>'bg-blue-100 text-blue-800'];
                        $cls = $badge[$st] ?? 'bg-gray-100 text-gray-800';
                      ?>
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $cls; ?>">
                        <?php echo h($st); ?>
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo h($f['aircraft_model']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">$<?php echo number_format((float)$f['base_price'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
  // Toggle sidebar on mobile
  document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('-translate-x-full');
  });

  // Toggle user dropdown
  const userMenuButton = document.getElementById('userMenuButton');
  const userDropdown = document.getElementById('userDropdown');
  userMenuButton?.addEventListener('click', function() {
    userDropdown?.classList.toggle('hidden');
  });
  document.addEventListener('click', function(event) {
    if (!userMenuButton?.contains(event.target) && !userDropdown?.contains(event.target)) {
      userDropdown?.classList.add('hidden');
    }
  });
</script>
</body>
</html>
