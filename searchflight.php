<?php
require_once 'config/database.php';
session_start();

/**
 * Format a date string safely.
 */
function formatDate($dateStr, $long = false) {
    try {
        $date = new DateTime($dateStr);
        return $date->format($long ? 'D, j M Y' : 'D, j M');
    } catch (Exception $e) {
        return date($long ? 'D, j M Y' : 'D, j M');
    }
}

/**
 * Map a destination name (e.g., "Paris") to an IATA code.
 * 1) Use curated map for featured destinations.
 * 2) If not found, try DB lookup by city or airport name.
 */
function mapDestinationToIata(PDO $db, string $destination) {
    $map = [
        'Paris' => 'CDG',
        'Tokyo' => 'NRT',                 // could also use HND
        'New York' => 'JFK',
        'Rome' => 'FCO',
        'Bali' => 'DPS',
        'Sydney' => 'SYD',
        'Rio de Janeiro' => 'GIG',
        'Cape Town' => 'CPT',
        'Dubai' => 'DXB',
        'Barcelona' => 'BCN',
        'Venice' => 'VCE',
        'Kyoto' => 'KIX',                 // nearest major airport (Osaka)
        'Santorini' => 'JTR',
        'Machu Picchu' => 'CUZ',          // Cusco
        'Queenstown' => 'ZQN',
    ];

    $key = trim($destination);
    if (isset($map[$key])) return $map[$key];

    try {
        $like = '%' . $destination . '%';
        $sql = "SELECT iata_code
                  FROM airports
                 WHERE city ILIKE :q OR name ILIKE :q
              ORDER BY (CASE WHEN city = :exact OR name = :exact THEN 0 ELSE 1 END),
                       LENGTH(city), LENGTH(name)
                 LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([':q' => $like, ':exact' => $destination]);
        $code = $stmt->fetchColumn();
        if ($code) return $code;
    } catch (Exception $e) {}

    return null;
}

/** Get airport name by IATA, fallback to code itself */
function airportNameByCode(PDO $db, string $code) {
    $stmt = $db->prepare("SELECT name FROM airports WHERE iata_code = ?");
    $stmt->execute([$code]);
    return $stmt->fetchColumn() ?: $code;
}

/* -------------------- Inputs -------------------- */
$destination = isset($_GET['destination']) ? trim($_GET['destination']) : null;
$auto = isset($_GET['auto']) && $_GET['auto'] == '1';

/* If a destination link was clicked, default origin to CMB unless user passed ?from= */
$from = $_GET['from'] ?? ($destination ? 'CMB' : 'MXP');
$to   = $_GET['to']   ?? 'CMB';
$date = $_GET['date'] ?? date('Y-m-d');
$passengers = (int)($_GET['passengers'] ?? 1);
if ($passengers < 1) $passengers = 1;

/* Validate date format */
if (!DateTime::createFromFormat('Y-m-d', $date)) {
    $date = date('Y-m-d');
}

/* Map destination name to IATA when provided and no explicit ?to= */
if ($destination && (!isset($_GET['to']) || empty($_GET['to']))) {
    $mapped = mapDestinationToIata($db, $destination);
    if ($mapped) $to = $mapped;
}

/* Airport names for header */
$fromAirport = airportNameByCode($db, $from);
$toAirport   = airportNameByCode($db, $to);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Sheron Airways - Flight Search Results</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .flight-card { transition: all .3s ease; border-left: 4px solid transparent; }
        .flight-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,.1); border-left-color: #3b82f6; }
        .flight-card.selected { border-left-color: #3b82f6; background-color: #f8fafc; }
        .ticket-preview { max-height: 0; overflow: hidden; transition: max-height .3s ease-out; }
        .ticket-preview.active { max-height: 500px; transition: max-height .5s ease-in; }
        .date-tab { transition: all .2s ease; }
        .date-tab.active { background-color: #eff6ff; border-color: #3b82f6; }
    </style>
</head>
<body class="font-sans bg-gray-50">
    <!-- Header -->
    <header class="flex items-center justify-between px-8 py-4 bg-[#0A1A3F] sticky top-0 z-50 shadow-lg">
        <div class="text-2xl font-bold text-white flex items-center">
            <i class="fas fa-plane text-blue-400 mr-2"></i>
            Sheron Airways
        </div>
        <nav class="flex items-center space-x-8">
            <a href="index.php" class="text-white hover:text-blue-300">Home</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Logout</a>
            <?php else: ?>
                <a href="login.php" class="text-white hover:text-blue-300">Login</a>
                <a href="signup.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Sign Up</a>
            <?php endif; ?>
        </nav>
    </header>

    <!-- Search Summary -->
    <section class="bg-white shadow-md py-6 px-8 mb-6">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Select your departure flight</h1>

            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div class="flex items-center">
                    <div class="text-center">
                        <div class="text-lg font-semibold"><?= htmlspecialchars($fromAirport) ?></div>
                        <div class="text-sm text-gray-500"><?= htmlspecialchars($from) ?></div>
                    </div>
                    <div class="mx-4"><i class="fas fa-plane text-blue-500"></i></div>
                    <div class="text-center">
                        <div class="text-lg font-semibold"><?= htmlspecialchars($toAirport) ?></div>
                        <div class="text-sm text-gray-500"><?= htmlspecialchars($to) ?></div>
                    </div>
                </div>

                <div class="flex items-center">
                    <div class="text-center mr-6">
                        <div class="text-sm text-gray-500">Departure</div>
                        <div class="font-medium"><?= formatDate($date, true) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-sm text-gray-500">Passengers</div>
                        <div class="font-medium">
                            <?= (int)$passengers ?> <?= ((int)$passengers) > 1 ? 'Adults' : 'Adult' ?>
                        </div>
                    </div>
                </div>

                <a href="index.php#search-flight" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                    <i class="fas fa-pencil-alt mr-2"></i> Modify Search
                </a>
            </div>

            <div class="flex items-center justify-between border-t border-b border-gray-200 py-3">
                <div class="text-gray-700"><span id="results-count">0</span> results</div>
                <div class="text-sm text-gray-500">Free cancellation for all passengers</div>
            </div>
        </div>
    </section>

    <!-- Results -->
    <section class="max-w-6xl mx-auto px-4 pb-12">
        <div class="flex overflow-x-auto gap-2 mb-6 pb-2" id="date-selector"></div>
        <div id="flight-results-container"></div>
    </section>

    <script>
        // Params to JS
        const searchParams = {
            from: '<?= htmlspecialchars($from, ENT_QUOTES) ?>',
            to: '<?= htmlspecialchars($to, ENT_QUOTES) ?>',
            date: '<?= htmlspecialchars($date, ENT_QUOTES) ?>',
            passengers: <?= (int)$passengers ?>
        };

        async function fetchFlights(date) {
            try {
                const qs = new URLSearchParams({
                    from: searchParams.from,
                    to: searchParams.to,
                    date,
                    passengers: String(searchParams.passengers),
                });
                const res = await fetch(`api/flights.php?${qs.toString()}`);
                if (!res.ok) throw new Error('Network error');
                return await res.json();
            } catch (e) {
                console.error(e);
                return [];
            }
        }

        function calculateLayover(arrival, departure) {
            const [ah, am] = (arrival || '00:00').split(':').map(Number);
            const [dh, dm] = (departure || '00:00').split(':').map(Number);
            let total = (dh * 60 + dm) - (ah * 60 + am);
            if (total < 0) total += 24 * 60;
            const h = Math.floor(total / 60);
            const m = total % 60;
            return `${h}h ${m}m`;
        }

        async function showFlightsForDate(date) {
            const flights = await fetchFlights(date);
            const container = document.getElementById('flight-results-container');
            container.innerHTML = '';

            if (Array.isArray(flights) && flights.length) {
                document.getElementById('results-count').textContent = flights.length;

                flights.forEach(flight => {
                    const flightCard = document.createElement('div');
                    flightCard.className = 'flight-card bg-white rounded-lg shadow-sm p-6 cursor-pointer mb-4';
                    flightCard.setAttribute('onclick', `toggleFlightDetails('${flight.id}')`);

                    const classesText = (flight.classes && flight.classes.length)
                        ? `Available Classes: ${flight.classes.join(', ')}`
                        : 'Economy Class';

                    flightCard.innerHTML = `
                        <div class="flex flex-col md:flex-row md:items-center justify-between">
                            <div class="flex items-center mb-4 md:mb-0">
                                <div class="text-center mr-8">
                                    <div class="text-xl font-bold">${flight.departureTime}</div>
                                    <div class="text-sm text-gray-500">${flight.departure_code}</div>
                                </div>
                                <div class="text-center mx-4">
                                    <div class="text-sm text-gray-500">${Number(flight.stops) || 0} Stop${(Number(flight.stops)||0) !== 1 ? 's' : ''}, ${flight.duration}</div>
                                    <div class="w-32 h-px bg-gray-300 my-2"></div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-bold">${flight.arrivalTime}</div>
                                    <div class="text-sm text-gray-500">${flight.arrival_code}</div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="mr-8">
                                    <div class="text-lg font-bold text-right">€${Number(flight.price).toFixed(2)}</div>
                                    <div class="text-sm text-gray-500 text-right">Economy</div>
                                </div>
                                <i class="fas fa-chevron-down text-gray-400" id="${flight.id}-arrow"></i>
                            </div>
                        </div>

                        <div class="ticket-preview mt-4" id="${flight.id}-details">
                            <div class="border-t border-gray-200 pt-4">
                                <h3 class="font-bold mb-3">Flight Details</h3>
                                <div class="grid grid-cols-1 md:grid-cols-${(flight.segments && flight.segments.length === 1) ? '1' : '3'} gap-4">
                                    ${(flight.segments || []).map((segment, i, arr) => `
                                        <div>
                                            <h4 class="text-sm font-semibold text-gray-500 mb-2">${segment.departure_code} to ${segment.arrival_code}</h4>
                                            <div class="flex items-center justify-between py-2">
                                                <div>
                                                    <div class="font-medium">${segment.departure} - ${segment.arrival}</div>
                                                    <div class="text-sm text-gray-500">${segment.airline_name} · ${segment.flight_number}</div>
                                                </div>
                                                <div class="text-sm">${segment.duration}</div>
                                            </div>
                                        </div>
                                        ${i < arr.length - 1 ? `
                                            <div class="text-center">
                                                <div class="text-sm text-gray-500 py-4">Layover: ${calculateLayover(arr[i].arrival, arr[i+1].departure)}</div>
                                            </div>` : '' }
                                    `).join('')}
                                </div>

                                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="font-semibold mb-2">${classesText}</h4>
                                        <ul class="text-sm space-y-2">
                                            <li class="flex items-center"><i class="fas fa-suitcase-rolling text-blue-500 mr-2"></i> 30kg checked baggage</li>
                                            <li class="flex items-center"><i class="fas fa-utensils text-blue-500 mr-2"></i> Meals included</li>
                                            <li class="flex items-center"><i class="fas fa-tv text-blue-500 mr-2"></i> In-flight entertainment</li>
                                        </ul>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold mb-2">Pricing</h4>
                                        <div class="text-sm space-y-2">
                                            <div class="flex justify-between">
                                                <span>${searchParams.passengers} Adult${searchParams.passengers !== 1 ? 's' : ''}</span>
                                                <span>€${(Number(flight.price) * searchParams.passengers).toFixed(2)}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span>Taxes & Fees</span>
                                                <span>€${(Number(flight.price) * searchParams.passengers * 0.15).toFixed(2)}</span>
                                            </div>
                                            <div class="flex justify-between font-bold border-t border-gray-200 pt-2 mt-2">
                                                <span>Total</span>
                                                <span>€${(Number(flight.price) * searchParams.passengers * 1.15).toFixed(2)}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 text-right">
                                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium" onclick="selectFlight('${flight.id}', event)">
                                        Select Flight
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    container.appendChild(flightCard);
                });
            } else {
                container.innerHTML = `
                    <div class="text-center py-12 bg-white rounded-lg shadow-sm">
                        <i class="fas fa-plane-slash text-4xl text-gray-400 mb-4"></i>
                        <h3 class="text-xl font-medium text-gray-700 mb-2">No flights available</h3>
                        <p class="text-gray-500 mb-4">We couldn't find any flights for the selected date.</p>
                        <a href="index.php#search-flight" class="text-blue-600 hover:text-blue-800 font-medium">
                            <i class="fas fa-search mr-2"></i> Try a different search
                        </a>
                    </div>
                `;
                document.getElementById('results-count').textContent = '0';
            }
        }

        // Build ±3 day tabs and let user re-run the search on this page
        async function populateDateSelector() {
            const dateSelector = document.getElementById('date-selector');
            dateSelector.innerHTML = '';

            const base = new Date(searchParams.date);
            const days = [];
            for (let i = -3; i <= 3; i++) {
                const d = new Date(base);
                d.setDate(d.getDate() + i);
                days.push(d.toISOString().split('T')[0]);
            }

            for (const d of days) {
                const flights = await fetchFlights(d);
                const minPrice = flights.length ? Math.min(...flights.map(f => Number(f.price))) : 0;
                const dateObj = new Date(d);
                const label = dateObj.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
                const isActive = d === searchParams.date;

                const tab = document.createElement('div');
                tab.className = `date-tab flex-none w-32 text-center border rounded-lg py-3 cursor-pointer ${isActive ? 'active bg-blue-50 border-blue-200' : 'hover:bg-gray-50'}`;
                tab.innerHTML = `
                    <div class="text-sm font-medium">${label}</div>
                    <div class="text-lg font-bold ${isActive ? 'text-blue-600' : ''}">${minPrice ? '€' + minPrice.toFixed(2) : 'N/A'}</div>
                `;
                tab.addEventListener('click', () => {
                    const url = new URL(window.location.href);
                    url.pathname = 'searchflight.php';
                    url.searchParams.set('from', searchParams.from);
                    url.searchParams.set('to', searchParams.to);
                    url.searchParams.set('date', d);
                    url.searchParams.set('passengers', String(searchParams.passengers));
                    window.location.href = url.toString();
                });

                dateSelector.appendChild(tab);
            }
        }

        function toggleFlightDetails(flightId) {
            const details = document.getElementById(`${flightId}-details`);
            const arrow = document.getElementById(`${flightId}-arrow`);
            if (!details || !arrow) return;
            details.classList.toggle('active');
            arrow.classList.toggle('transform');
            arrow.classList.toggle('rotate-180');

            document.querySelectorAll('.ticket-preview').forEach(el => {
                if (el.id !== `${flightId}-details` && el.classList.contains('active')) {
                    el.classList.remove('active');
                    const other = document.getElementById(el.id.replace('-details', '-arrow'));
                    if (other) other.classList.remove('transform', 'rotate-180');
                }
            });
        }

        function selectFlight(flightId, event) {
            event.stopPropagation();
            <?php if (isset($_SESSION['user_id'])): ?>
                window.location.href = `booking.php?flightId=${encodeURIComponent(flightId)}&passengers=${encodeURIComponent(searchParams.passengers)}`;
            <?php else: ?>
                window.location.href = `login.php?redirect=${encodeURIComponent('booking.php?flightId=' + flightId + '&passengers=' + searchParams.passengers)}`;
            <?php endif; ?>
        }

        document.addEventListener('DOMContentLoaded', () => {
            populateDateSelector();
            showFlightsForDate(searchParams.date);
        });
    </script>
</body>
</html>
