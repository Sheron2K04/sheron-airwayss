<?php
// C:\xampp\htdocs\projectweb\sheronair\index.php
declare(strict_types=1);

// Base config + session bootstrap (enables remember-me auto-login if present)
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/bootstrap_auth.php';

// Database connection (for “Summer Holiday Destinations” cards)
require_once __DIR__ . '/includes/db_connection.php';

// Logged-in check (session-based)
$isLoggedIn = !empty($_SESSION['user_id']);

// Helper to wrap any intended URL with auth requirement
function auth_link(bool $isLoggedIn, string $intendedUrl): string {
  return $isLoggedIn ? $intendedUrl : (BASE_URL . '/auth/signin.php');
}

/**
 * Map destination strings to nice photography.
 */
function imageForCity(string $city): string {
  $c = strtolower($city);

  if (str_contains($c, 'paris') || str_contains($c, 'france')) {
    return 'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80';
  }
  if (str_contains($c, 'tokyo') || str_contains($c, 'japan')) {
    return 'https://images.unsplash.com/photo-1492571350019-22de08371fd3?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80';
  }
  if (str_contains($c, 'new york') || str_contains($c, 'nyc') || str_contains($c, 'usa')) {
    return 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80';
  }
  if (str_contains($c, 'london') || str_contains($c, 'uk')) {
    return 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80';
  }
  if (str_contains($c, 'dubai') || str_contains($c, 'uae')) {
    return 'https://images.unsplash.com/photo-1518684079-3c830dcef090?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80';
  }

  $fallbacks = [
    'https://images.unsplash.com/photo-1500835556837-99ac94a94552?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80',
    'https://images.unsplash.com/photo-1503917988258-f87a78e3c995?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80',
    'https://images.unsplash.com/photo-1506929562872-bb421503ef21?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80',
  ];
  return $fallbacks[array_rand($fallbacks)];
}

/** 20 curated airport options for selects */
$airportOptions = [
  ['code' => 'CMB', 'label' => 'Colombo – Bandaranaike (CMB), Sri Lanka'],
  ['code' => 'JFK', 'label' => 'New York – John F. Kennedy (JFK), USA'],
  ['code' => 'LHR', 'label' => 'London – Heathrow (LHR), UK'],
  ['code' => 'CDG', 'label' => 'Paris – Charles de Gaulle (CDG), France'],
  ['code' => 'DXB', 'label' => 'Dubai – DXB (DXB), UAE'],
  ['code' => 'DOH', 'label' => 'Doha – Hamad International (DOH), Qatar'],
  ['code' => 'FRA', 'label' => 'Frankfurt – FRA (FRA), Germany'],
  ['code' => 'AMS', 'label' => 'Amsterdam – Schiphol (AMS), Netherlands'],
  ['code' => 'HND', 'label' => 'Tokyo – Haneda (HND), Japan'],
  ['code' => 'NRT', 'label' => 'Tokyo – Narita (NRT), Japan'],
  ['code' => 'SIN', 'label' => 'Singapore – Changi (SIN), Singapore'],
  ['code' => 'HKG', 'label' => 'Hong Kong – HKG (HKG), China'],
  ['code' => 'ICN', 'label' => 'Seoul – Incheon (ICN), South Korea'],
  ['code' => 'BKK', 'label' => 'Bangkok – Suvarnabhumi (BKK), Thailand'],
  ['code' => 'KUL', 'label' => 'Kuala Lumpur – KUL (KUL), Malaysia'],
  ['code' => 'SYD', 'label' => 'Sydney – SYD (SYD), Australia'],
  ['code' => 'MEL', 'label' => 'Melbourne – MEL (MEL), Australia'],
  ['code' => 'BCN', 'label' => 'Barcelona – El Prat (BCN), Spain'],
  ['code' => 'FCO', 'label' => 'Rome – Fiumicino (FCO), Italy'],
  ['code' => 'IST', 'label' => 'Istanbul – IST (IST), Türkiye'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sheron Airways - Book Flights</title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

  <style>
    .hero-bg {
      background-image:
        linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
        url('https://images.unsplash.com/photo-1436491865332-7a61a109cc05?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');
      background-size: cover;
      background-position: center;
      transition: background-image 1s ease-in-out;
    }
  </style>
</head>
<body class="font-sans bg-gray-100">

  <!-- Header -->
  <header class="flex items-center justify-between px-8 py-4 bg-[#0A1A3F] sticky top-0 z-50 shadow-lg">
    <a href="<?php echo BASE_URL; ?>/index.php" class="text-2xl font-bold text-white flex items-center">
      <i class="fas fa-plane text-blue-400 mr-2"></i>
      Sheron Airways
    </a>
    <nav class="flex items-center space-x-8">
      <a href="<?php echo BASE_URL; ?>/#search-flight" class="text-white hover:text-blue-300">Book Flights</a>
      <a href="<?php echo BASE_URL; ?>/#about" class="text-white hover:text-blue-300">About Us</a>
      <?php if ($isLoggedIn): ?>
        <a href="<?php echo BASE_URL; ?>/logout.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Logout</a>
      <?php else: ?>
        <a href="<?php echo BASE_URL; ?>/auth/signin.php" class="text-white hover:text-blue-300">Login</a>
        <a href="<?php echo BASE_URL; ?>/auth/signup.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Sign Up</a>
      <?php endif; ?>
    </nav>
  </header>

  <!-- Hero -->
  <section id="hero-section" class="hero-bg h-[70vh] flex flex-col justify-center items-center text-center px-4">
    <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">Fly to amazing destinations with Sheron Airways</h1>
    <a href="<?php echo auth_link($isLoggedIn, BASE_URL . '/index.php#search-flight'); ?>"
       class="mt-6 bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg text-lg font-medium shadow-lg transition-all hover:scale-105">
      Search Flights <i class="fas fa-arrow-right ml-2"></i>
    </a>
  </section>

  <!-- Flight Search -->
  <section id="search-flight" class="bg-white text-black rounded-t-3xl px-8 py-8 relative z-10 shadow-xl -mt-10 mx-4">
    <form class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-4" id="flight-search-form" action="<?php echo BASE_URL; ?>ticket.php" method="GET">
      <!-- FROM -->
      <div>
        <label class="block text-sm font-medium mb-1">From</label>
        <div class="relative">
          <i class="fas fa-plane-departure absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
          <select id="from-airport" name="from" class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 appearance-none" required>
            <option value="" disabled selected>Select departure</option>
            <?php foreach ($airportOptions as $a): ?>
              <option value="<?php echo htmlspecialchars($a['code']); ?>">
                <?php echo htmlspecialchars($a['label']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- TO -->
      <div>
        <label class="block text-sm font-medium mb-1">To</label>
        <div class="relative">
          <i class="fas fa-plane-arrival absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
          <select id="to-airport" name="to" class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 appearance-none" required>
            <option value="" disabled selected>Select arrival</option>
            <?php foreach ($airportOptions as $a): ?>
              <option value="<?php echo htmlspecialchars($a['code']); ?>">
                <?php echo htmlspecialchars($a['label']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- DEPARTURE -->
      <div>
        <label class="block text-sm font-medium mb-1">Departure</label>
        <div class="relative">
          <i class="far fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
          <input type="date" name="date" id="departure-date" class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
        </div>
      </div>

      <!-- RETURN (Optional) -->
      <div>
        <label class="block text-sm font-medium mb-1">Return (Optional)</label>
        <div class="relative">
          <i class="far fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
          <input type="date" name="return_date" id="return-date" class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
      </div>

      <div class="md:col-span-4 flex justify-center">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg text-lg font-medium shadow-lg w-full md:w-auto">
          <i class="fas fa-search mr-2"></i> Search Flights
        </button>
      </div>
    </form>
  </section>

  <!-- Destinations -->
  <section class="px-6 py-16 max-w-7xl mx-auto">
    <h2 class="text-3xl md:text-4xl font-extrabold mb-12 text-center text-gray-800">Summer Holiday Destinations</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <?php
      try {
        $popularDestinations = [];
        if (isset($conn) && $conn instanceof PDO) {
          $stmt = $conn->query("SELECT name, city FROM airports ORDER BY RANDOM() LIMIT 3");
          $popularDestinations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        // Helper to tagline
        function taglineFor($city) {
          $lc = strtolower($city);
          if (strpos($lc, 'paris') !== false) return 'The city of love and lights';
          if (strpos($lc, 'tokyo') !== false) return 'Where tradition meets future';
          if (strpos($lc, 'new york') !== false || strpos($lc, 'nyc') !== false) return 'The city that never sleeps';
          if (strpos($lc, 'london') !== false) return 'Timeless charm, modern spirit';
          if (strpos($lc, 'dubai') !== false) return 'Luxury in the desert';
          return 'Your perfect gateway awaits';
        }

        for ($i = 0; $i < min(3, count($popularDestinations)); $i++):
          $destination = $popularDestinations[$i];
          $city = htmlspecialchars($destination['city']);
          $airportName = htmlspecialchars($destination['name']);
          $img = imageForCity($city);
          $price = [499, 799, 599][$i % 3];
          $tagline = taglineFor($city);
          $bookUrl = BASE_URL . '/booking.php?dest=' . urlencode($city);
      ?>
      <!-- Card -->
      <article class="relative overflow-hidden rounded-2xl shadow-xl bg-gray-900 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl">
        <img src="<?php echo $img; ?>" alt="<?php echo $city; ?> - <?php echo $airportName; ?>"
             class="w-full h-72 object-cover brightness-90 transition-transform duration-700 ease-out hover:scale-105">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0 p-6 md:p-7 text-white">
          <h3 class="text-2xl md:text-3xl font-extrabold drop-shadow-sm"><?php echo $city; ?></h3>
          <p class="text-blue-300 font-semibold mt-1">From $<?php echo $price; ?></p>
          <p class="text-white/90 mt-2 text-sm md:text-base"><?php echo $tagline; ?></p>
          <a href="<?php echo auth_link($isLoggedIn, $bookUrl); ?>"
             class="mt-4 inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-semibold shadow-md transition-colors">
            Book Now <i class="fa-solid fa-arrow-right-long"></i>
          </a>
        </div>
      </article>
      <?php endfor; ?>

      <?php
        // If DB returned fewer than 3, pad with curated placeholders
        for ($j = count($popularDestinations); $j < 3; $j++):
          $fallbacks = [
            ['label' => 'Paris, France',   'img' => 'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80', 'price' => 499],
            ['label' => 'Tokyo, Japan',    'img' => 'https://images.unsplash.com/photo-1492571350019-22de08371fd3?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80', 'price' => 799],
            ['label' => 'New York, USA',   'img' => 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80', 'price' => 599],
          ];
          $fallback = $fallbacks[$j];
          $fallbackCity = $fallback['label'];
          $fallbackImg  = $fallback['img'];
          $fallbackPrice= $fallback['price'];
          $bookUrl = BASE_URL . '/booking.php?dest=' . urlencode($fallbackCity);
          $fallbackTag  = taglineFor($fallbackCity);
      ?>
      <article class="relative overflow-hidden rounded-2xl shadow-xl bg-gray-900 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl">
        <img src="<?php echo $fallbackImg; ?>" alt="<?php echo htmlspecialchars($fallbackCity); ?>"
             class="w-full h-72 object-cover brightness-90 transition-transform duration-700 ease-out hover:scale-105">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0 p-6 md:p-7 text-white">
          <h3 class="text-2xl md:text-3xl font-extrabold drop-shadow-sm"><?php echo htmlspecialchars($fallbackCity); ?></h3>
          <p class="text-blue-300 font-semibold mt-1">From $<?php echo $fallbackPrice; ?></p>
          <p class="text-white/90 mt-2 text-sm md:text-base"><?php echo $fallbackTag; ?></p>
          <a href="<?php echo auth_link($isLoggedIn, $bookUrl); ?>"
             class="mt-4 inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-semibold shadow-md transition-colors">
            Book Now <i class="fa-solid fa-arrow-right-long"></i>
          </a>
        </div>
      </article>
      <?php endfor; ?>

      <?php
      } catch (Throwable $e) {
        echo "<!-- Error fetching destinations: " . htmlspecialchars($e->getMessage()) . " -->";
      }
      ?>
    </div>

    <div class="mt-10 flex justify-center">
      <a href="<?php echo auth_link($isLoggedIn, BASE_URL . '/destination.php'); ?>"
         class="inline-flex items-center gap-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-7 py-3 rounded-xl shadow-lg transition">
        View All Destinations <i class="fa-solid fa-arrow-right-long"></i>
      </a>
    </div>
  </section>

  <!-- About -->
  <section id="about" class="bg-[#0A1A3F] text-white px-6 py-16">
    <div class="max-w-6xl mx-auto">
      <h2 class="text-3xl font-bold mb-12 text-center">About Sheron Airways</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
        <div>
          <h3 class="text-xl font-semibold mb-4 flex items-center">
            <i class="fas fa-history text-blue-400 mr-3"></i> Our Story
          </h3>
          <p class="text-gray-300 leading-relaxed">
            Founded in 2023, Sheron Airways has quickly become a leader in international air travel.
            We started with a single route and now serve over 50 destinations worldwide.
          </p>
        </div>
        <div>
          <h3 class="text-xl font-semibold mb-4 flex items-center">
            <i class="fas fa-bullseye text-blue-400 mr-3"></i> Our Mission
          </h3>
          <p class="text-gray-300 leading-relaxed">
            To connect the world through exceptional air travel experiences. We're committed to
            safety, comfort, and sustainability.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-[#050E28] text-gray-400 py-8">
    <div class="max-w-6xl mx-auto px-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <div>
          <h3 class="text-white font-bold mb-4">Sheron Airways</h3>
          <p class="text-sm">Connecting you to the world with exceptional service.</p>
        </div>
        <div>
          <h4 class="text-white font-bold mb-4">Quick Links</h4>
          <ul class="space-y-2">
            <li><a href="<?php echo BASE_URL; ?>/index.php" class="hover:text-white">Home</a></li>
            <li><a href="<?php echo BASE_URL; ?>/searchflight.php" class="hover:text-white">Book Flights</a></li>
            <li><a href="<?php echo BASE_URL; ?>/#about" class="hover:text-white">About Us</a></li>
          </ul>
        </div>
        <div>
          <h4 class="text-white font-bold mb-4">Support</h4>
          <ul class="space-y-2">
            <li><a href="<?php echo BASE_URL; ?>/contact.php" class="hover:text-white">Contact Us</a></li>
            <li><a href="<?php echo BASE_URL; ?>/faqs.php" class="hover:text-white">FAQs</a></li>
          </ul>
        </div>
        <div>
          <h4 class="text-white font-bold mb-4">Connect</h4>
          <div class="flex space-x-4">
            <a href="#" class="hover:text-white"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="hover:text-white"><i class="fab fa-twitter"></i></a>
            <a href="#" class="hover:text-white"><i class="fab fa-instagram"></i></a>
          </div>
        </div>
      </div>
      <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm">
        <p>© <?php echo date('Y'); ?> Sheron Airways. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <script>
    // Expose BASE_URL to JS
    const BASE = <?php echo json_encode(BASE_URL); ?>;

    // Hero background shuffle (preload)
    document.addEventListener('DOMContentLoaded', () => {
      const hero = document.getElementById('hero-section');
      const heroImages = [
        'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80',
        'https://images.unsplash.com/photo-1506929562872-bb421503ef21?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80',
        'https://images.unsplash.com/photo-1500835556837-99ac94a94552?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80',
      ];
      heroImages.forEach(src => { const img = new Image(); img.src = src; });
      let index = 0;
      setInterval(() => {
        index = (index + 1) % heroImages.length;
        hero.style.backgroundImage =
          `linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('${heroImages[index]}')`;
      }, 5000);
    });

    // Date guards + prevent selecting the same airport
    document.addEventListener('DOMContentLoaded', () => {
      const today = new Date().toISOString().split('T')[0];
      const dep = document.getElementById('departure-date');
      const ret = document.getElementById('return-date');
      if (dep) dep.min = today;
      if (ret) ret.min = today;

      const fromSel = document.getElementById('from-airport');
      const toSel   = document.getElementById('to-airport');
      const form    = document.getElementById('flight-search-form');

      function syncDisable(a, b) {
        [...b.options].forEach(o => o.disabled = false);
        if (a.value) {
          [...b.options].forEach(o => { if (o.value === a.value) o.disabled = true; });
          if (b.value === a.value) b.value = '';
        }
      }
      if (fromSel && toSel) {
        syncDisable(fromSel, toSel);
        syncDisable(toSel, fromSel);
        fromSel.addEventListener('change', () => syncDisable(fromSel, toSel));
        toSel.addEventListener('change',   () => syncDisable(toSel, fromSel));
      }

      // Block submit if same (double safety)
      form.addEventListener('submit', (e) => {
        if (!fromSel.value || !toSel.value) return;
        if (fromSel.value === toSel.value) {
          e.preventDefault();
          alert('Departure and arrival airports must be different.');
        }
      });

      // If your site requires login before searching, gate here
      <?php if (!$isLoggedIn): ?>
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        window.location.href = BASE + '/auth/signin.php';
      });
      <?php endif; ?>
    });
  </script>
</body>
</html>
