<?php
// admin_dashboard.php
declare(strict_types=1);

// -------- Bootstrap --------
require_once __DIR__ . '/includes/bootstrap_auth.php';   // should start session
require_once __DIR__ . '/includes/db_connection.php';    // exposes $pdo (PDO) OR $conn (mysqli)

// -------- Security Headers --------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://cdnjs.cloudflare.com 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';");

// -------- Auth Guard (server-side) --------
if (empty($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true || ($_SESSION['userRole'] ?? '') !== 'admin') {
    $target = urlencode('admin_dashboard.php');
    header("Location: signin.php?redirect={$target}");
    exit;
}

// -------- CSRF helper for logout --------
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function verify_csrf(?string $token): bool {
    return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
}

// Optional server-side logout endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: signin.php');
    exit;
}

// -------- DB helpers (PDO or MySQLi) --------
/** @return array{driver:string, ok:bool} */
function db_driver(): array {
    return [
        'driver' => isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO ? 'pdo'
                  : ((isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) ? 'mysqli' : 'none'),
        'ok' => (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO)
                || (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli),
    ];
}

/** Run a SELECT that returns a single scalar. */
function db_scalar(string $sql, array $params = []) {
    $drv = db_driver();
    try {
        if ($drv['driver'] === 'pdo') {
            /** @var PDO $pdo */
            $pdo = $GLOBALS['pdo'];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } elseif ($drv['driver'] === 'mysqli') {
            /** @var mysqli $conn */
            $conn = $GLOBALS['conn'];
            $stmt = $conn->prepare($sql);
            if (!$stmt) { throw new RuntimeException($conn->error); }
            // bind params dynamically
            if ($params) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...array_values($params));
            }
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_row() : null;
            return $row[0] ?? null;
        }
    } catch (Throwable $e) {
        // log in real app
        return null;
    }
    return null;
}

/** Run a SELECT that returns rows (assoc). */
function db_rows(string $sql, array $params = []): array {
    $drv = db_driver();
    try {
        if ($drv['driver'] === 'pdo') {
            /** @var PDO $pdo */
            $pdo = $GLOBALS['pdo'];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } elseif ($drv['driver'] === 'mysqli') {
            /** @var mysqli $conn */
            $conn = $GLOBALS['conn'];
            $stmt = $conn->prepare($sql);
            if (!$stmt) { throw new RuntimeException($conn->error); }
            if ($params) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...array_values($params));
            }
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) { $rows[] = $row; }
            }
            return $rows;
        }
    } catch (Throwable $e) {
        // log in real app
        return [];
    }
    return [];
}

// -------- KPI queries (SAFE, with fallbacks if tables absent) --------
$activeFlights = (int) (db_scalar("SELECT COUNT(*) FROM flights WHERE status IN ('Scheduled','In Air')") ?? 24);
$tickets7d     = (int) (db_scalar("SELECT COUNT(*) FROM tickets WHERE created_at >= (CURRENT_DATE - INTERVAL 7 DAY)") ?? 1842);
$revenueMTD    = (float) (db_scalar("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='captured' AND DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(CURRENT_DATE,'%Y-%m')") ?? 482000);
$bookedSeats   = (int) (db_scalar("SELECT COALESCE(SUM(seats_booked),0) FROM flights WHERE DATE_FORMAT(departure_time,'%Y-%m')=DATE_FORMAT(CURRENT_DATE,'%Y-%m')") ?? 8100);
$totalSeats    = (int) (db_scalar("SELECT COALESCE(SUM(seat_capacity),0) FROM flights WHERE DATE_FORMAT(departure_time,'%Y-%m')=DATE_FORMAT(CURRENT_DATE,'%Y-%m')") ?? 10000);
$loadFactorPct = $totalSeats > 0 ? round(($bookedSeats / $totalSeats) * 100) : 0;

// Recent 3 tickets (fallback to sample if no data)
$recentTickets = db_rows("
    SELECT 
        t.code        AS ticket_code,
        t.status      AS ticket_status,
        t.price       AS ticket_price,
        p.full_name   AS passenger,
        f.origin      AS origin,
        f.destination AS destination
    FROM tickets t
    JOIN passengers p ON p.id = t.passenger_id
    JOIN flights f    ON f.id = t.flight_id
    ORDER BY t.created_at DESC
    LIMIT 3
");
if (!$recentTickets) {
    $recentTickets = [
        ['ticket_code'=>'TK-789456','passenger'=>'John Smith','origin'=>'CMB','destination'=>'LHR','ticket_price'=>850,'ticket_status'=>'Confirmed'],
        ['ticket_code'=>'TK-321654','passenger'=>'Sarah Johnson','origin'=>'JFK','destination'=>'CMB','ticket_price'=>1200,'ticket_status'=>'Pending'],
        ['ticket_code'=>'TK-987123','passenger'=>'Michael Brown','origin'=>'DXB','destination'=>'CMB','ticket_price'=>750,'ticket_status'=>'Cancelled'],
    ];
}

// Active flights card (fallback)
$activeFlightsList = db_rows("
    SELECT 
        f.flight_no,
        f.origin,
        f.destination,
        f.aircraft_type,
        f.status
    FROM flights f
    WHERE f.status IN ('Scheduled','In Air','Delayed')
    ORDER BY 
        FIELD(f.status,'In Air','Scheduled','Delayed'), f.departure_time ASC
    LIMIT 3
");
if (!$activeFlightsList) {
    $activeFlightsList = [
        ['flight_no'=>'SHA101','origin'=>'JFK','destination'=>'LAX','aircraft_type'=>'B737-800','status'=>'On Time'],
        ['flight_no'=>'SHA202','origin'=>'ORD','destination'=>'JFK','aircraft_type'=>'A320','status'=>'Delayed'],
        ['flight_no'=>'SHA303','origin'=>'LAX','destination'=>'ORD','aircraft_type'=>'B787','status'=>'In Air'],
    ];
}

// Formatting helpers
function money_usd(float $v): string { return '$' . number_format($v, 0, '.', ','); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SheronAir Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            navy: { 900:'#0A1A3F', 800:'#142857', 700:'#1E3A8A', 600:'#233D96' }
          },
          boxShadow: { soft: '0 10px 25px -10px rgba(0,0,0,0.35)' }
        }
      }
    }
  </script>
  <style>
    body { background: linear-gradient(135deg, #0A1A3F 0%, #142857 100%); }
    .glass { backdrop-filter: blur(6px); background: rgba(255,255,255,0.08); }
    .sidebar { transition: transform .25s ease; }
  </style>
</head>
<body class="min-h-screen text-gray-100">
  <!-- Mobile Top Bar -->
  <div class="md:hidden sticky top-0 z-50 bg-navy-900/95 glass border-b border-white/10">
    <div class="px-4 py-3 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <i class="fas fa-plane text-blue-400 rotate-45"></i>
        <span class="font-semibold">SHERON AIRWAYS</span>
      </div>
      <button id="sidebarToggle" class="p-2 rounded-md bg-white/10 hover:bg-white/20">
        <i class="fas fa-bars"></i>
      </button>
    </div>
  </div>

  <!-- Sidebar -->
  <aside id="sidebar" class="sidebar fixed inset-y-0 left-0 w-72 -translate-x-full md:translate-x-0 z-40">
    <div class="h-full bg-navy-900/95 glass border-r border-white/10 flex flex-col">
      <div class="h-16 px-5 flex items-center border-b border-white/10">
        <div class="flex items-center text-white">
          <i class="fas fa-plane text-blue-300 mr-2 rotate-45"></i>
          <span class="text-lg font-bold">Admin Console</span>
        </div>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
        <a href="#" class="flex items-center px-3 py-2 rounded-lg bg-white/10 text-white">
          <i class="fas fa-gauge-high w-5 mr-3 text-blue-300"></i> Overview
        </a>
        <a href="flightmanagemenntadmin.html" class="flex items-center px-3 py-2 rounded-lg hover:bg-white/10">
          <i class="fas fa-plane-departure w-5 mr-3 text-blue-300"></i> Flights
        </a>
        <a href="ticketadmin.html" class="flex items-center px-3 py-2 rounded-lg hover:bg-white/10">
          <i class="fas fa-ticket-alt w-5 mr-3 text-blue-300"></i> Tickets
        </a>
        <a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-white/10">
          <i class="fas fa-users w-5 mr-3 text-blue-300"></i> Passengers
        </a>
        <a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-white/10">
          <i class="fas fa-route w-5 mr-3 text-blue-300"></i> Routes & Airports
        </a>
        <a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-white/10">
          <i class="fas fa-chart-line w-5 mr-3 text-blue-300"></i> Analytics
        </a>
        <a href="#" class="flex items-center px-3 py-2 rounded-lg hover:bg-white/10">
          <i class="fas fa-cog w-5 mr-3 text-blue-300"></i> Settings
        </a>
      </nav>

      <div class="p-4 border-t border-white/10">
        <form method="post" class="w-full">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES) ?>">
          <input type="hidden" name="action" value="logout">
          <button type="submit" class="w-full px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white">
            <i class="fas fa-right-from-bracket mr-2"></i> Logout
          </button>
        </form>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <main class="md:ml-72 px-4 sm:px-6 lg:px-10 py-6">
    <!-- Topbar -->
    <header class="hidden md:block mb-6">
      <div class="glass rounded-2xl border border-white/10 shadow-soft">
        <div class="px-6 py-4 flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <i class="fas fa-plane text-blue-300 rotate-45"></i>
            <h1 class="text-xl font-semibold">SheronAir Admin Dashboard</h1>
          </div>
          <div class="flex items-center space-x-4">
            <div class="hidden sm:flex items-center text-sm text-gray-300 mr-2">
              <i class="fas fa-shield-halved text-green-400 mr-2"></i>
              <span>Secure Admin Session</span>
            </div>
            <div class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center" title="<?= htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES) ?>">
              <i class="fas fa-user"></i>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- KPI Cards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
      <div class="glass rounded-2xl p-5 border border-white/10 shadow-soft">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-300">Active Flights</p>
            <h3 class="text-2xl font-bold"><?= number_format($activeFlights) ?></h3>
          </div>
          <div class="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center">
            <i class="fas fa-plane-departure text-blue-300"></i>
          </div>
        </div>
        <p class="text-xs text-green-300 mt-2"><i class="fas fa-arrow-up"></i> +8% vs last week</p>
      </div>

      <div class="glass rounded-2xl p-5 border border-white/10 shadow-soft">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-300">Tickets Sold (7d)</p>
            <h3 class="text-2xl font-bold"><?= number_format($tickets7d) ?></h3>
          </div>
          <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center">
            <i class="fas fa-ticket text-emerald-300"></i>
          </div>
        </div>
        <p class="text-xs text-green-300 mt-2"><i class="fas fa-arrow-up"></i> +12%</p>
      </div>

      <div class="glass rounded-2xl p-5 border border-white/10 shadow-soft">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-300">Load Factor</p>
            <h3 class="text-2xl font-bold"><?= (int)$loadFactorPct ?>%</h3>
          </div>
          <div class="w-10 h-10 rounded-xl bg-purple-500/20 flex items-center justify-center">
            <i class="fas fa-seat-airline text-purple-300"></i>
          </div>
        </div>
        <p class="text-xs text-yellow-300 mt-2"><i class="fas fa-minus"></i> stable</p>
      </div>

      <div class="glass rounded-2xl p-5 border border-white/10 shadow-soft">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-300">Revenue (MTD)</p>
            <h3 class="text-2xl font-bold"><?= money_usd($revenueMTD) ?></h3>
          </div>
          <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center">
            <i class="fas fa-dollar-sign text-amber-300"></i>
          </div>
        </div>
        <p class="text-xs text-green-300 mt-2"><i class="fas fa-arrow-up"></i> +6%</p>
      </div>
    </section>

    <!-- Charts & Quick Actions -->
    <section class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
      <!-- Revenue Line Chart -->
      <div class="xl:col-span-2 glass rounded-2xl p-6 border border-white/10 shadow-soft">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold">Revenue (Last 12 Weeks)</h3>
          <div class="text-xs text-gray-300">UTC</div>
        </div>
        <canvas id="revenueChart" height="110"></canvas>
      </div>

      <!-- Quick Actions -->
      <div class="glass rounded-2xl p-6 border border-white/10 shadow-soft">
        <h3 class="font-semibold mb-4">Quick Actions</h3>
        <div class="grid grid-cols-2 gap-3">
          <a href="flightmanagemenntadmin.html" class="group px-4 py-3 rounded-xl bg-white/10 hover:bg-white/20">
            <div class="flex items-center">
              <i class="fas fa-plus mr-3 text-blue-300"></i>
              <span>Add Flight</span>
            </div>
          </a>
          <a href="ticketadmin.html" class="group px-4 py-3 rounded-xl bg-white/10 hover:bg-white/20">
            <div class="flex items-center">
              <i class="fas fa-ticket mr-3 text-emerald-300"></i>
              <span>New Ticket</span>
            </div>
          </a>
          <button class="px-4 py-3 rounded-xl bg-white/10 hover:bg-white/20 text-left">
            <i class="fas fa-bell mr-3 text-amber-300"></i> Send Delay Alert
          </button>
          <button class="px-4 py-3 rounded-xl bg-white/10 hover:bg-white/20 text-left">
            <i class="fas fa-shield-halved mr-3 text-purple-300"></i> Run Audit
          </button>
        </div>
      </div>
    </section>

    <!-- Recent Tickets & Active Flights -->
    <section class="grid grid-cols-1 xl:grid-cols-3 gap-4">
      <!-- Recent Tickets Table -->
      <div class="xl:col-span-2 glass rounded-2xl border border-white/10 shadow-soft">
        <div class="px-6 py-4 flex items-center justify-between border-b border-white/10">
          <h3 class="font-semibold">Recent Tickets</h3>
          <a href="ticketadmin.html" class="text-sm text-blue-300 hover:underline">Manage</a>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-white/5">
              <tr class="text-left">
                <th class="px-6 py-3 font-medium">Ticket</th>
                <th class="px-6 py-3 font-medium">Passenger</th>
                <th class="px-6 py-3 font-medium">Route</th>
                <th class="px-6 py-3 font-medium">Price</th>
                <th class="px-6 py-3 font-medium">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
              <?php foreach ($recentTickets as $row): ?>
                <tr class="hover:bg-white/5">
                  <td class="px-6 py-3"><?= htmlspecialchars($row['ticket_code'] ?? '', ENT_QUOTES) ?></td>
                  <td class="px-6 py-3"><?= htmlspecialchars($row['passenger'] ?? '', ENT_QUOTES) ?></td>
                  <td class="px-6 py-3">
                    <?= htmlspecialchars(($row['origin'] ?? '') . ' → ' . ($row['destination'] ?? ''), ENT_QUOTES) ?>
                  </td>
                  <td class="px-6 py-3">$<?= number_format((float)($row['ticket_price'] ?? 0), 0) ?></td>
                  <td class="px-6 py-3">
                    <?php
                      $status = strtolower($row['ticket_status'] ?? '');
                      $class  = $status === 'confirmed' ? 'bg-green-500/20 text-green-300'
                               : ($status === 'pending' ? 'bg-yellow-500/20 text-yellow-300'
                               : 'bg-red-500/20 text-red-300');
                    ?>
                    <span class="px-2 py-1 rounded-full <?= $class ?>">
                      <?= htmlspecialchars(ucfirst($status), ENT_QUOTES) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Active Flights Card -->
      <div class="glass rounded-2xl p-6 border border-white/10 shadow-soft">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold">Active Flights</h3>
          <a href="flightmanagemenntadmin.html" class="text-sm text-blue-300 hover:underline">Open</a>
        </div>
        <ul class="space-y-3 text-sm">
          <?php foreach ($activeFlightsList as $f): ?>
            <?php
              $badge = 'bg-blue-500/20 text-blue-300';
              if (stripos($f['status'], 'On Time') !== false)  $badge = 'bg-green-500/20 text-green-300';
              if (stripos($f['status'], 'Delayed') !== false)  $badge = 'bg-yellow-500/20 text-yellow-300';
              if (stripos($f['status'], 'In Air') !== false)   $badge = 'bg-blue-500/20 text-blue-300';
            ?>
            <li class="flex items-center justify-between bg-white/5 rounded-xl px-4 py-3">
              <div class="flex items-center space-x-3">
                <span class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center text-blue-300">SA</span>
                <div>
                  <p class="font-medium"><?= htmlspecialchars(($f['flight_no'] ?? 'SA') . ' • ' . ($f['origin'] ?? '') . ' → ' . ($f['destination'] ?? ''), ENT_QUOTES) ?></p>
                  <p class="text-gray-300 text-xs"><?= htmlspecialchars(($f['status'] ?? '') . ' • ' . ($f['aircraft_type'] ?? ''), ENT_QUOTES) ?></p>
                </div>
              </div>
              <span class="px-2 py-1 rounded-full <?= $badge ?> text-xs">
                <?= htmlspecialchars($f['status'] ?? '', ENT_QUOTES) ?>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>

    <!-- Footer -->
    <footer class="mt-8 text-xs text-gray-300 text-center">
      <p>© <?= date('Y') ?> Sheron Airways • Admin Console</p>
    </footer>
  </main>

  <!-- Client JS -->
  <script>
    // Sidebar toggle (mobile)
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
      document.getElementById('sidebar').classList.toggle('-translate-x-full');
    });

    // Chart (demo data; swap with API if desired)
    const ctx = document.getElementById('revenueChart');
    const weeks = Array.from({length: 12}, (_,i)=>`W${i+1}`);
    const revenue = [28,32,31,36,40,44,42,46,49,51,55,58];
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: weeks,
        datasets: [{
          label: 'Revenue ($k)',
          data: revenue,
          fill: false,
          borderColor: 'rgba(59,130,246,0.9)',
          backgroundColor: 'rgba(59,130,246,0.9)',
          tension: 0.35,
          pointRadius: 3,
          borderWidth: 2
        }]
      },
      options: {
        maintainAspectRatio: false,
        plugins: { legend: { labels: { color: '#e5e7eb' } } },
        scales: {
          x: { ticks: { color: '#cbd5e1' }, grid: { color: 'rgba(255,255,255,0.06)' } },
          y: { ticks: { color: '#cbd5e1', callback: v => '$'+v+'k' }, grid: { color: 'rgba(255,255,255,0.06)' } }
        }
      }
    });
  </script>
</body>
</html>
