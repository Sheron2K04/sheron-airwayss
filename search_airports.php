<?php
// C:\xampp\htdocs\projectweb\sheronair\search_airports.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db_connection.php'; // provides $db and $conn
if (!isset($db) && isset($conn)) { $db = $conn; }

/* -------- Obtain PDO ($pdo) no matter which include you use -------- */
$pdo = null;

$maybeConn = __DIR__ . '/includes/db_connection.php';   // usually defines $conn
$maybeCfg  = __DIR__ . '/config/database.php';          // some setups define $db

if (file_exists($maybeConn)) {
  require_once $maybeConn;
  if (isset($conn) && $conn instanceof PDO) $pdo = $conn;
}
if (!$pdo && file_exists($maybeCfg)) {
  require_once $maybeCfg;
  if (isset($db) && $db instanceof PDO) $pdo = $db;
}
if (!$pdo) {
  http_response_code(500);
  echo json_encode(['error' => 'DB connection not available']);
  exit;
}

/* Reasonable PDO attrs (safe if already set) */
try {
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (Throwable $e) {}

/* -------- Helpers -------- */
function out($data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

/* -------- Input -------- */
$term = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
if ($term === '' || mb_strlen($term) < 2) {
  out([]); // too short -> return nothing
}

/* -------- Query --------
   Rank by:
   0 = exact code match
   1 = code prefix match
   2 = city startswith
   3 = airport name contains
*/
try {
  $like = '%' . $term . '%';
  $pref = $term . '%';

  $sql = "
    SELECT
      a.airport_id,
      a.code,
      a.name,
      a.city,
      a.country,
      CASE
        WHEN lower(a.code) = lower(:exact) THEN 0
        WHEN lower(a.code) LIKE lower(:pref) THEN 1
        WHEN lower(a.city) LIKE lower(:pref) THEN 2
        WHEN lower(a.name) LIKE lower(:like) THEN 3
        ELSE 4
      END AS rank_score
    FROM airports a
    WHERE
         lower(a.code) = lower(:exact)
      OR lower(a.code) LIKE lower(:pref)
      OR lower(a.city) LIKE lower(:pref)
      OR lower(a.name) LIKE lower(:like)
    ORDER BY rank_score ASC, a.city ASC, a.name ASC
    LIMIT 10
  ";

  $st = $pdo->prepare($sql);
  $st->execute([
    ':exact' => $term,
    ':pref'  => $pref,
    ':like'  => $like,
  ]);

  $rows = $st->fetchAll();

  $suggestions = [];
  foreach ($rows as $r) {
    $code    = (string)$r['code'];
    $city    = (string)$r['city'];
    $name    = (string)$r['name'];
    $country = (string)$r['country'];

    $suggestions[] = [
      'value' => $code,                                      // e.g., "CMB"
      'label' => sprintf('%s – %s (%s), %s', $city, $name, $code, $country),
      'id'    => (int)$r['airport_id'],
    ];
  }

  out($suggestions);

} catch (PDOException $e) {
  error_log('[search_airports][PDO] ' . $e->getCode() . ' ' . $e->getMessage());
  out(['error' => 'Server error'], 500);
} catch (Throwable $e) {
  error_log('[search_airports] ' . $e->getMessage());
  out(['error' => 'Server error'], 500);
}
