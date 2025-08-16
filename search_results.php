<?php
// api/flights.php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php'; // must set $db = new PDO(...)

/* Helpers */
function qstr(string $key, string $default = ''): string {
    return isset($_GET[$key]) ? trim((string)$_GET[$key]) : $default;
}
function fmt_duration(string $startTs, string $endTs): string {
    try {
        $s = new DateTime($startTs);
        $e = new DateTime($endTs);
        $secs = max(0, $e->getTimestamp() - $s->getTimestamp());
        $h = intdiv($secs, 3600);
        $m = intdiv($secs % 3600, 60);
        return "{$h}h {$m}m";
    } catch (Exception $e) {
        return '';
    }
}

$from = strtoupper(qstr('from'));
$to   = strtoupper(qstr('to'));
$date = qstr('date'); // Y-m-d
$passengers = (int)($_GET['passengers'] ?? 1);
if ($passengers < 1) $passengers = 1;

if (!$from || !$to || !$date || !DateTime::createFromFormat('Y-m-d', $date)) {
    echo json_encode([]);
    exit;
}

/**
 * Schema assumptions (adjust names if yours differ):
 * flights(
 *   flight_id PK, flight_number, airline_name,
 *   departure_time TIMESTAMP, arrival_time TIMESTAMP,
 *   departure_iata VARCHAR(3), arrival_iata VARCHAR(3),
 *   price NUMERIC, stops INT
 * )
 * tickets(
 *   ticket_id PK, flight_id FK -> flights, ticket_class VARCHAR CHECK (...),
 *   ... (other fields not used here)
 * )
 *
 * We group flights and aggregate ticket classes present in tickets for each flight.
 */
$sql = "
SELECT
    f.flight_id,
    COALESCE(f.airline_name, 'Sheron Airways') AS airline_name,
    f.flight_number,
    f.departure_time,
    f.arrival_time,
    f.departure_iata   AS departure_code,
    f.arrival_iata     AS arrival_code,
    COALESCE(f.price, f.base_price, 0)         AS price,
    COALESCE(f.stops, 0)                       AS stops,
    ARRAY_AGG(DISTINCT t.ticket_class) FILTER (WHERE t.ticket_class IS NOT NULL) AS classes
FROM flights f
LEFT JOIN tickets t  ON t.flight_id = f.flight_id
WHERE f.departure_iata = :from
  AND f.arrival_iata   = :to
  AND DATE(f.departure_time) = :depdate
GROUP BY
    f.flight_id, f.airline_name, f.flight_number, f.departure_time, f.arrival_time,
    f.departure_iata, f.arrival_iata, f.price, f.base_price, f.stops
ORDER BY f.departure_time ASC
";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':from'    => $from,
        ':to'      => $to,
        ':depdate' => $date,
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo json_encode([]);
    exit;
}

$out = [];
foreach ($rows as $r) {
    $depTime = (new DateTime($r['departure_time']))->format('H:i');
    $arrTime = (new DateTime($r['arrival_time']))->format('H:i');
    $duration = fmt_duration($r['departure_time'], $r['arrival_time']);

    // Normalize Postgres array_agg result into PHP array
    $classes = [];
    if (isset($r['classes'])) {
        if (is_array($r['classes'])) {
            $classes = array_values(array_filter($r['classes']));
        } else {
            // Fallback if driver returns a string like "{Economy,Business}"
            $cls = trim((string)$r['classes'], "{}");
            $classes = $cls !== '' ? array_map('trim', explode(',', $cls)) : [];
        }
    }

    $out[] = [
        'id'              => $r['flight_id'],
        'airline_name'    => $r['airline_name'],
        'flight_number'   => $r['flight_number'],
        'departureTime'   => $depTime,
        'arrivalTime'     => $arrTime,
        'departure_code'  => $r['departure_code'],
        'arrival_code'    => $r['arrival_code'],
        'duration'        => $duration,
        'stops'           => (int)$r['stops'],
        'price'           => (float)$r['price'],
        // Simple single-segment; extend if you store multi-leg segments elsewhere.
        'segments'        => [[
            'departure_code' => $r['departure_code'],
            'arrival_code'   => $r['arrival_code'],
            'departure'      => $depTime,
            'arrival'        => $arrTime,
            'airline_name'   => $r['airline_name'],
            'flight_number'  => $r['flight_number'],
            'duration'       => $duration,
        ]],
        'classes'         => $classes, // e.g. ["Economy","Business"]
    ];
}

echo json_encode($out);
