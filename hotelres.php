<?php
// C:\xampp\htdocs\projectweb\sheronair\hotels.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/bootstrap_auth.php'; // optional if you want remember-me
require_once __DIR__ . '/includes/db_connection.php';

// --- read filters ---
$city   = trim($_GET['city']   ?? '');
$sort   = strtolower(trim($_GET['sort'] ?? 'price'));   // price | rating
$dir    = strtolower(trim($_GET['dir']  ?? 'asc'));     // asc | desc

// normalize
if (!in_array($sort, ['price','rating'], true)) $sort = 'price';
if (!in_array($dir, ['asc','desc'], true))     $dir  = 'asc';

// map to columns
$orderCol = $sort === 'rating' ? 'rating' : 'price_per_night';
$orderDir = $dir === 'desc' ? 'DESC' : 'ASC';

// build query
$params = [];
$sql = "
  SELECT hotel_id, name, city, country, rating, price_per_night, image_url
  FROM hotels
  /**where**/
  ORDER BY {$orderCol} {$orderDir}, name ASC
  LIMIT 200
";
$where = [];
if ($city !== '') {
  $where[] = 'LOWER(city) LIKE LOWER(:city)';
  $params[':city'] = "%{$city}%";
}
if ($where) {
  $sql = str_replace('/**where**/', 'WHERE '.implode(' AND ', $where), $sql);
} else {
  $sql = str_replace('/**where**/', '', $sql);
}

$hotels = [];
try {
  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  $hotels = $stmt->fetchAll();
} catch (Throwable $e) {
  error_log('[hotels] '.$e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Hotels | Sheron Airways</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <style>
    body { background: linear-gradient(135deg, #0A1A3F 0%, #142857 100%); }
    .card { transition: transform .25s ease, box-shadow .25s ease; }
    .card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,.15); }
    .star { color:#f59e0b; }
  </style>
</head>
<body class="text-gray-900">
  <!-- Header -->
  <header class="bg-white/95 backdrop-blur sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <a href="<?php echo BASE_URL; ?>/index.php" class="flex items-center text-lg font-semibold">
        <i class="fas fa-plane text-blue-600 mr-2 rotate-45"></i> Sheron Airways
      </a>
      <nav class="flex items-center gap-6 text-sm">
        <a class="hover:text-blue-600" href="<?php echo BASE_URL; ?>/index.php">Home</a>
        <a class="text-blue-600 font-medium" href="<?php echo BASE_URL; ?>/hotels.php">Hotels</a>
        <a class="hover:text-blue-600" href="<?php echo BASE_URL; ?>/ticketadmin.php">Tickets</a>
      </nav>
    </div>
  </header>

  <!-- Hero -->
  <section class="py-10 sm:py-14 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h1 class="text-3xl sm:text-4xl font-extrabold mb-2">Find your stay</h1>
      <p class="text-blue-200">Sort by the lowest/highest price or rating. Filter by city.</p>
    </div>
  </section>

  <!-- Filters -->
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6">
    <form method="get" class="bg-white rounded-2xl shadow p-4 sm:p-5">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">City (optional)</label>
          <input type="text" name="city" value="<?php echo htmlspecialchars($city); ?>"
                 class="w-full border rounded-lg px-3 py-2" placeholder="e.g., Paris, Tokyo, Bali">
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Sort by</label>
          <select name="sort" class="w-full border rounded-lg px-3 py-2">
            <option value="price"  <?php echo $sort==='price'?'selected':''; ?>>Price</option>
            <option value="rating" <?php echo $sort==='rating'?'selected':''; ?>>Rating</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Direction</label>
          <select name="dir" class="w-full border rounded-lg px-3 py-2">
            <option value="asc"  <?php echo $dir==='asc'?'selected':''; ?>>Lowest first</option>
            <option value="desc" <?php echo $dir==='desc'?'selected':''; ?>>Highest first</option>
          </select>
        </div>
      </div>

      <div class="mt-4 flex gap-3">
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
          <i class="fas fa-filter mr-2"></i> Apply
        </button>
        <a href="<?php echo BASE_URL; ?>/hotels.php" class="px-4 py-2 rounded-lg border hover:bg-gray-50">
          Clear
        </a>
      </div>
    </form>
  </section>

  <!-- Results (No pagination strip) -->
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 my-8">
    <?php if (!$hotels): ?>
      <div class="bg-white rounded-xl p-6 text-center text-gray-500 shadow">No hotels found.</div>
    <?php else: ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($hotels as $h): ?>
          <article class="card bg-white rounded-xl overflow-hidden shadow">
            <div class="h-44 w-full bg-gray-100 overflow-hidden">
              <img src="<?php echo htmlspecialchars($h['image_url'] ?: 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb'); ?>"
                   alt="<?php echo htmlspecialchars($h['name']); ?>"
                   class="w-full h-full object-cover">
            </div>
            <div class="p-4">
              <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($h['name']); ?></h3>
              <p class="text-sm text-gray-600 mb-2">
                <i class="fas fa-location-dot mr-1 text-blue-600"></i>
                <?php echo htmlspecialchars($h['city'] . ', ' . $h['country']); ?>
              </p>
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <?php
                    $rating = (float)$h['rating'];
                    $full = floor($rating);
                    $half = ($rating - $full) >= 0.5 ? 1 : 0;
                    for ($i=0; $i<$full; $i++) echo '<i class="fa-solid fa-star star"></i>';
                    if ($half) echo '<i class="fa-solid fa-star-half-stroke star ml-0.5"></i>';
                    $empty = 5 - $full - $half;
                    for ($i=0; $i<$empty; $i++) echo '<i class="fa-regular fa-star text-yellow-400"></i>';
                  ?>
                  <span class="ml-2 text-sm text-gray-700"><?php echo number_format($rating,1); ?></span>
                </div>
                <div class="text-right">
                  <div class="text-xs text-gray-500">from</div>
                  <div class="text-xl font-bold text-gray-900">$<?php echo number_format((float)$h['price_per_night'], 2); ?></div>
                </div>
              </div>
            </div>
            <div class="px-4 pb-4">
              <a href="#"
                 class="inline-flex items-center justify-center w-full mt-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                Select <i class="fa-solid fa-arrow-right-long ml-2"></i>
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- Footer -->
  <footer class="py-8 text-center text-sm text-blue-200">
    © <?php echo date('2025'); ?> Sheron Airways. All rights reserved.
  </footer>
</body>
</html>
