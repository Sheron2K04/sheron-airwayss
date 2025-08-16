<?php
// C:\xampp\htdocs\projectweb\sheronair\api\flights.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// ✅ Correct path: includes is inside sheronair
require_once __DIR__ . '/../includes/db_connection.php'; 

// normalize to $pdo (always PDO instance)
$pdo = null;
if (isset($db) && $db instanceof PDO) {
    $pdo = $db;
} elseif (isset($conn) && $conn instanceof PDO) {
    $pdo = $conn;
}
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection not available'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* PDO safety settings */
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (Throwable $e) {}

/* ---------------- Helpers ---------------- */
function bad(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------------- Inputs & validation ---------------- */
$from = isset($_GET['from']) ? strtoupper(trim((string)$_GET['from'])) : '';
$to   = isset($_GET['to'])   ? strtoupper(trim((string)$_GET['to']))   : '';
$date = isset($_GET['date']) ? (string)$_GET['date'] : '';

if ($from === '' || $to === '' || $date === '') {
    bad('Invalid parameters');
}
if (!preg_match('/^[A-Z]{3}$/', $from) || !preg_match('/^[A-Z]{3}$/', $to)) {
    bad('Airport codes must be 3 letters (IATA)');
}
$dt = DateTime::createFromFormat('Y-m-d', $date);
if (!$dt || DateTime::getLastErrors()['error_count']) {
    bad('Invalid date format, expected YYYY-MM-DD');
}

/* ---------------- Look up airport IDs ---------------- */
try {
    $getId = $pdo->prepare("SELECT airport_id FROM airports WHERE code = :c LIMIT 1");

    $getId->execute([':c' => $from]);
    $originId = (int)($getId->fetchColumn() ?: 0);

    $getId->execute([':c' => $to]);
    $destId = (int)($getId->fetchColumn() ?: 0);

    if ($originId <= 0 || $destId <= 0) {
        bad('Unknown airport code(s)');
    }

    /* ---------------- Query flights ---------------- */
    $sql = "
      SELECT
        f.flight_id,
        f.flight_number,
        f.departure_time,
        f.arrival_time,
        f.base_price,
        ao.code AS departure_code,
        ad.code AS arrival_code,
        ac.model AS aircraft_model,
        COALESCE(
          ARRAY_AGG(DISTINCT s.class) FILTER (WHERE s.is_available IS TRUE),
          ARRAY[]::varchar[]
        ) AS classes
      FROM flights f
      JOIN airports ao ON ao.airport_id = f.origin_id
      JOIN airports ad ON ad.airport_id = f.destination_id
      LEFT JOIN aircraft ac ON ac.aircraft_id = f.aircraft_id
      LEFT JOIN seats s ON s.aircraft_id = f.aircraft_id
      WHERE f.origin_id = :origin
        AND f.destination_id = :dest
        AND DATE(f.departure_time) = :date
      GROUP BY f.flight_id, ao.code, ad.code, ac.model
      ORDER BY f.departure_time ASC
    ";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':origin' => $originId,
        ':dest'   => $destId,
        ':date'   => $dt->format('Y-m-d'),
    ]);
    $rows = $st->fetchAll();

    /* ---------------- Format response ---------------- */
    $out = [];
    foreach ($rows as $r) {
        $dep = new DateTime($r['departure_time']);
        $arr = new DateTime($r['arrival_time']);
        $diff = $dep->diff($arr);
        $hours = $diff->h + ($diff->d * 24);
        $duration = $hours . 'h ' . $diff->i . 'm';

        // Convert Postgres array string → PHP array
        $classes = [];
        if (!empty($r['classes'])) {
            if (is_array($r['classes'])) {
                $classes = $r['classes'];
            } else {
                $trim = trim((string)$r['classes'], "{}");
                $classes = $trim === '' ? [] : array_map('trim', explode(',', $trim));
            }
        }

        $out[] = [
            'id'             => (string)$r['flight_id'],
            'flight_number'  => (string)$r['flight_number'],
            'departureTime'  => $dep->format('H:i'),
            'arrivalTime'    => $arr->format('H:i'),
            'departure_code' => (string)$r['departure_code'],
            'arrival_code'   => (string)$r['arrival_code'],
            'duration'       => $duration,
            'stops'          => 0,
            'price'          => (float)$r['base_price'],
            'classes'        => $classes,
            'segments'       => [[
                'departure_code' => (string)$r['departure_code'],
                'arrival_code'   => (string)$r['arrival_code'],
                'departure'      => $dep->format('H:i'),
                'arrival'        => $arr->format('H:i'),
                'airline_name'   => 'Sheron Airways',
                'flight_number'  => (string)$r['flight_number'],
                'duration'       => $duration,
            ]],
        ];
    }

    echo json_encode($out, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('[flights.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error'], JSON_UNESCAPED_UNICODE);
}
