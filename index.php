<?php
// C:\xampp\htdocs\projectweb\sheronair\index.php

declare(strict_types=1);

// Base config + session bootstrap (enables remember‑me auto‑login if present)
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/bootstrap_auth.php';

// Database connection
require_once __DIR__ . '/includes/db_connection.php';

// Logged-in check (session-based)
$isLoggedIn = !empty($_SESSION['user_id']);

// Helper to wrap any intended URL with auth requirement
function auth_link(bool $isLoggedIn, string $intendedUrl): string {
    return $isLoggedIn ? $intendedUrl : (BASE_URL . '/auth/signin.php');
}

/**
 * Map destination strings to nice photography.
 * - Any French destination → Eiffel Tower
 * - Any Japanese destination → Mount Fuji
 * - Common others get curated images
 * - Otherwise: pleasant travel fallbacks
 */
function imageForCity(string $city): string {
    $c = strtolower($city);

    // France → Eiffel Tower
    if (str_contains($c, 'paris') || str_contains($c, 'france')) {
        return 'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1600&q=80';
    }

    // Japan → Mount Fuji (specific image)
    if (str_contains($c, 'tokyo') || str_contains($c, 'japan')) {
        return 'https://images.unsplash.com/photo-1492571350019-22de08371fd3?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1600&q=80';
    }

    // New York → Brooklyn Bridge (specific image)
    if (str_contains($c, 'new york') || str_contains($c, 'nyc') || str_contains($c, 'usa')) {
        return 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1600&q=80';
    }
    
    if (str_contains($c, 'london') || str_contains($c, 'uk')) {
        return 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1600&q=80';
    }
    
    if (str_contains($c, 'dubai') || str_contains($c, 'uae')) {
        return 'https://images.unsplash.com/photo-1518684079-3c830dcef090?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D3D&auto=format&fit=crop&w=1600&q=80';
    }

    // Generic travel fallbacks (high-quality images)
    $fallbacks = [
        'https://images.unsplash.com/photo-1500835556837-99ac94a94552?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1600&q=80', // Santorini
        'https://images.unsplash.com/photo-1503917988258-f87a78e3c995?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1600&q=80', // Maldives
        'https://images.unsplash.com/photo-1506929562872-bb421503ef21?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1600&q=80'  // African safari
    ];
    return $fallbacks[array_rand($fallbacks)];
}
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
              url('https://images.unsplash.com/photo-1436491865332-7a61a109cc05?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80'); /* initial */
            background-size: cover;
            background-position: center;
            transition: background-image 1s ease-in-out;
        }
        /* Autocomplete dropdown styling */
        [list] { position: relative; }
        datalist {
            position: absolute;
            background-color: white;
            border: 1px solid #d1d5db;
            border-radius: 0 0 0.375rem 0.375rem;
            border-top: none;
            width: calc(100% - 2px);
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            margin-top: -1px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }
        datalist option {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.875rem;
            line-height: 1.25rem;
        }
        datalist option:last-child { border-bottom: none; }
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
        <form class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-4" id="flight-search-form" action="<?php echo BASE_URL; ?>/search_results.php" method="GET">
            <div>
                <label class="block text-sm font-medium mb-1">From</label>
                <div class="relative">
                    <i class="fas fa-plane-departure absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="from" class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="City or Airport" id="departure-input" autocomplete="off" required>
                    <input type="hidden" name="from_code" id="departure-code">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">To</label>
                <div class="relative">
                    <i class="fas fa-plane-arrival absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="to" class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="City or Airport" id="destination-input" autocomplete="off" required>
                    <input type="hidden" name="to_code" id="destination-code">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Departure</label>
                <div class="relative">
                    <i class="far fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="date" name="departure_date" class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" id="departure-date" required>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Return (Optional)</label>
                <div class="relative">
                    <i class="far fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="date" name="return_date" class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" id="return-date">
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
                // Fetch up to 3 random airports
                $stmt = $conn->query("SELECT * FROM airports ORDER BY RANDOM() LIMIT 3");
                $popularDestinations = $stmt->fetchAll();

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

                // Render up to 3 cards
                for ($i = 0; $i < min(3, count($popularDestinations)); $i++):
                    $destination = $popularDestinations[$i];
                    $city = htmlspecialchars($destination['city']);
                    $airportName = htmlspecialchars($destination['name']);
                    $img = imageForCity($city);
                    $price = [499, 799, 599][$i % 3]; // demo prices
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
                    // Explicit placeholders with specific images
                    $fallbacks = [
                        [
                            'label' => 'Paris, France', 
                            'img' => 'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1600&q=80',
                            'price' => 499
                        ],
                        [
                            'label' => 'Tokyo, Japan', 
                            'img' => 'https://images.unsplash.com/photo-1492571350019-22de08371fd3?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1600&q=80',
                            'price' => 799
                        ],
                        [
                            'label' => 'New York, USA', 
                            'img' => 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1600&q=80',
                            'price' => 599
                        ]
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
            } catch(PDOException $e) {
                echo "<!-- Error fetching destinations: " . htmlspecialchars($e->getMessage()) . " -->";
            }
            ?>
        </div>

        <!-- View all button -->
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

        // Hero background shuffle with high-quality images
        document.addEventListener('DOMContentLoaded', () => {
            const hero = document.getElementById('hero-section');
            const heroImages = [
                'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80', // Airplane wing
                'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80', // Sunset beach
                'https://images.unsplash.com/photo-1506929562872-bb421503ef21?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1600&q=80'  // Mountain view
            ];
            heroImages.forEach(src => { const img = new Image(); img.src = src; });
            let index = 0;
            setInterval(() => {
                index = (index + 1) % heroImages.length;
                hero.style.backgroundImage =
                    `linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('${heroImages[index]}')`;
            }, 5000);
        });

        // Search form + autocomplete
        document.addEventListener('DOMContentLoaded', function() {
            initAutocomplete('departure-input', 'departure-code');
            initAutocomplete('destination-input', 'destination-code');

            const today = new Date().toISOString().split('T')[0];
            document.getElementById('departure-date').min = today;
            document.getElementById('return-date').min = today;

            // Gate submit for guests
            const flightForm = document.getElementById('flight-search-form');
            <?php if (!$isLoggedIn): ?>
            if (flightForm) {
                flightForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    window.location.href = BASE + '/auth/signin.php';
                });
            }
            <?php endif; ?>
        });

        function initAutocomplete(inputId, hiddenInputId) {
            const input = document.getElementById(inputId);
            if (!input) return;
            let timeout;
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    const term = this.value.trim();
                    if (term.length < 2) { clearDatalist(inputId); return; }
                    fetch(`${BASE}/search_airports.php?term=${encodeURIComponent(term)}`)
                        .then(res => {
                            if (!res.ok) throw new Error('Network error');
                            return res.json();
                        })
                        .then(data => updateDatalist(inputId, hiddenInputId, data))
                        .catch(() => clearDatalist(inputId));
                }, 300);
            });
            input.addEventListener('change', function() {
                const datalist = document.getElementById(`${inputId}-datalist`);
                if (!datalist) return;
                for (let opt of datalist.getElementsByTagName('option')) {
                    if (opt.value === this.value) {
                        const hidden = document.getElementById(hiddenInputId);
                        if (hidden) hidden.value = opt.dataset.code || '';
                        break;
                    }
                }
            });
        }

        function updateDatalist(inputId, hiddenInputId, data) {
            let datalist = document.getElementById(`${inputId}-datalist`);
            if (!datalist) {
                datalist = document.createElement('datalist');
                datalist.id = `${inputId}-datalist`;
                document.body.appendChild(datalist);
                document.getElementById(inputId).setAttribute('list', datalist.id);
            }
            datalist.innerHTML = '';
            if (!Array.isArray(data) || data.length === 0) return;
            data.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.label;        // e.g., "Paris (CDG)"
                opt.dataset.code = item.value; // e.g., "CDG"
                datalist.appendChild(opt);
            });
        }

        function clearDatalist(inputId) {
            const datalist = document.getElementById(`${inputId}-datalist`);
            if (datalist) datalist.innerHTML = '';
        }
    </script>
</body>
</html>