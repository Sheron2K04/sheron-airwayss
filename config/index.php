<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sheron Airways</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-bg {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1436491865332-7a61a109cc05?ixlib=rb-4.0.3&auto=format&fit=crop&w=2074&q=80');
            background-size: cover;
            background-position: center;
            transition: background-image 1s ease-in-out;
        }
        .destination-card {
            transition: all 0.3s ease;
        }
        .destination-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="font-sans bg-gray-100">
    <!-- Header -->
    <header class="flex items-center justify-between px-8 py-4 bg-[#0A1A3F] sticky top-0 z-50 shadow-lg">
        <div class="text-2xl font-bold text-white flex items-center">
            <i class="fas fa-plane text-blue-400 mr-2"></i>
            Sheron Airways
        </div>

        <nav class="flex items-center space-x-8">
            <!-- Dropdown: Explore -->
            <div class="relative group">
                <button class="text-white hover:text-blue-300 flex items-center">
                    Explore <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div class="absolute hidden group-hover:block mt-3 left-0 w-64 bg-white rounded-lg shadow-xl p-4">
                    <div class="flex items-center mb-3 text-blue-600">
                        <i class="fas fa-map-marked-alt mr-2"></i>
                        <h3 class="font-bold">Discover</h3>
                    </div>
                    <a href="destination.html" class="block px-3 py-2 hover:bg-blue-50 rounded-lg flex items-center">
                        <i class="fas fa-location-dot text-blue-500 mr-2 w-5"></i>
                        <div>
                            <p class="font-medium">Destinations</p>
                            <p class="text-xs text-gray-500">Find your perfect getaway</p>
                        </div>
                    </a>
                    <a href="#" class="block px-3 py-2 hover:bg-blue-50 rounded-lg flex items-center">
                        <i class="fas fa-book-open text-blue-500 mr-2 w-5"></i>
                        <div>
                            <p class="font-medium">Travel Guide</p>
                            <p class="text-xs text-gray-500">Expert tips for your journey</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Dropdown: Book -->
            <div class="relative group">
                <button class="text-white hover:text-blue-300 flex items-center">
                    Book <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div class="absolute hidden group-hover:block mt-3 left-0 w-64 bg-white rounded-lg shadow-xl p-4">
                    <div class="flex items-center mb-3 text-blue-600">
                        <i class="fas fa-calendar-check mr-2"></i>
                        <h3 class="font-bold">Reservations</h3>
                    </div>
                    <a href="#" class="block px-3 py-2 hover:bg-blue-50 rounded-lg flex items-center">
                        <i class="fas fa-plane-departure text-blue-500 mr-2 w-5"></i>
                        <div>
                            <p class="font-medium">Flights</p>
                            <p class="text-xs text-gray-500">Book your next adventure</p>
                        </div>
                    </a>
                    <a href="#" class="block px-3 py-2 hover:bg-blue-50 rounded-lg flex items-center">
                        <i class="fas fa-hotel text-blue-500 mr-2 w-5"></i>
                        <div>
                            <p class="font-medium">Hotels</p>
                            <p class="text-xs text-gray-500">Find the perfect stay</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Dropdown: Experience -->
            <div class="relative group">
                <button class="text-white hover:text-blue-300 flex items-center">
                    Experience <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div class="absolute hidden group-hover:block mt-3 left-0 w-64 bg-white rounded-lg shadow-xl p-4">
                    <div class="flex items-center mb-3 text-blue-600">
                        <i class="fas fa-star mr-2"></i>
                        <h3 class="font-bold">Journey</h3>
                    </div>
                    <a href="#" class="block px-3 py-2 hover:bg-blue-50 rounded-lg flex items-center">
                        <i class="fas fa-utensils text-blue-500 mr-2 w-5"></i>
                        <div>
                            <p class="font-medium">Onboard</p>
                            <p class="text-xs text-gray-500">Gourmet dining & comfort</p>
                        </div>
                    </a>
                    <a href="#" class="block px-3 py-2 hover:bg-blue-50 rounded-lg flex items-center">
                        <i class="fas fa-luggage-cart text-blue-500 mr-2 w-5"></i>
                        <div>
                            <p class="font-medium">Airport</p>
                            <p class="text-xs text-gray-500">Premium lounge access</p>
                        </div>
                    </a>
                </div>
            </div>

            <a href="#about" class="text-white hover:text-blue-300">About Us</a>
            <a href="#" class="text-white hover:text-blue-300" id="loginBtn">Login</a>
            <a href="#" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg" id="signupBtn">Sign Up</a>
            <a href="#" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg hidden" id="logoutBtn">Logout</a>
        </nav>
    </header>

    <!-- Hero Section with Destination Gallery -->
    <section class="hero-bg h-[70vh] flex flex-col justify-center items-center text-center px-4">
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">5 must-visit destinations for your summer holiday</h1>
        <a href="destination.html" class="mt-6 bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg text-lg font-medium shadow-lg transition-all hover:scale-105">
            Explore Destinations <i class="fas fa-arrow-right ml-2"></i>
        </a>

        <!-- Gallery Controls -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 flex space-x-4">
            <button class="w-10 h-10 bg-black bg-opacity-50 flex items-center justify-center rounded-full hover:bg-opacity-70" onclick="prevDestination()">
                <i class="fas fa-chevron-left text-white"></i>
            </button>
            <button class="w-10 h-10 bg-black bg-opacity-50 flex items-center justify-center rounded-full hover:bg-opacity-70" onclick="nextDestination()">
                <i class="fas fa-chevron-right text-white"></i>
            </button>
        </div>
    </section>

    <!-- Booking Section -->
    <section class="bg-white text-black rounded-t-3xl px-8 py-8 relative z-10 shadow-xl -mt-10 mx-4">
        <form class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">From</label>
                <div class="relative">
                    <i class="fas fa-plane-departure absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Colombo CMB">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">To</label>
                <div class="relative">
                    <i class="fas fa-plane-arrival absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Destination">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Departure</label>
                <div class="relative">
                    <i class="far fa-calendar-alt absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="date" class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Return</label>
                <div class="relative">
                    <i class="far fa-calendar-alt absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="date" class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="md:col-span-4 flex justify-center">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg text-lg font-medium shadow-lg w-full md:w-auto">
                    <i class="fas fa-search mr-2"></i> Search Flights
                </button>
            </div>
        </form>
    </section>

    <!-- Destinations Section -->
    <section class="px-6 py-16 max-w-7xl mx-auto">
        <h2 class="text-3xl font-bold mb-12 text-center text-gray-800">Summer Holiday Destinations</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="destination-card rounded-xl shadow-lg relative overflow-hidden h-80 bg-cover bg-center"
                 style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1502602898657-3e91760cbb34?ixlib=rb-4.0.3&auto=format&fit=crop&w=2073&q=80')">
                <div class="absolute bottom-0 left-0 p-6 text-white">
                    <h3 class="text-2xl font-bold">Paris, France</h3>
                    <p class="text-blue-200 font-medium">From $499</p>
                    <p class="text-sm mt-2">The city of love and lights</p>
                    <button class="mt-3 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                        Book Now <i class="fas fa-arrow-right ml-1"></i>
                    </button>
                </div>
            </div>

            <div class="destination-card rounded-xl shadow-lg relative overflow-hidden h-80 bg-cover bg-center"
                 style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.pexels.com/photos/3408354/pexels-photo-3408354.jpeg')">
                <div class="absolute bottom-0 left-0 p-6 text-white">
                    <h3 class="text-2xl font-bold">Tokyo, Japan</h3>
                    <p class="text-blue-200 font-medium">From $799</p>
                    <p class="text-sm mt-2">Where tradition meets future</p>
                    <button class="mt-3 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                        Book Now <i class="fas fa-arrow-right ml-1"></i>
                    </button>
                </div>
            </div>

            <div class="destination-card rounded-xl shadow-lg relative overflow-hidden h-80 bg-cover bg-center"
                 style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1518391846015-55a9cc003b25?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80')">
                <div class="absolute bottom-0 left-0 p-6 text-white">
                    <h3 class="text-2xl font-bold">New York, USA</h3>
                    <p class="text-blue-200 font-medium">From $599</p>
                    <p class="text-sm mt-2">The city that never sleeps</p>
                    <button class="mt-3 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                        Book Now <i class="fas fa-arrow-right ml-1"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="text-center mt-10">
            <a href="destination.html" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-medium shadow-lg">
                View All Destinations <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </section>

    <!-- About Us Section -->
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
                        We started with a single route and now serve over 50 destinations worldwide, bringing
                        people together across continents with our award-winning service.
                    </p>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-bullseye text-blue-400 mr-3"></i> Our Mission
                    </h3>
                    <p class="text-gray-300 leading-relaxed">
                        To connect the world through exceptional air travel experiences. We're committed to
                        safety, comfort, and sustainability, ensuring every journey with us is memorable
                        from takeoff to landing.
                    </p>
                </div>
            </div>
            <div class="mt-16">
                <h3 class="text-xl font-semibold mb-8 text-center flex items-center justify-center">
                    <i class="fas fa-star text-blue-400 mr-3"></i> Why Fly With Us?
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-[#142857] p-6 rounded-lg">
                        <i class="fas fa-shield-alt text-blue-400 text-2xl mb-3"></i>
                        <h4 class="font-bold mb-2">Industry-leading Safety</h4>
                        <p class="text-gray-300 text-sm">Top-ranked safety record with modern aircraft</p>
                    </div>
                    <div class="bg-[#142857] p-6 rounded-lg">
                        <i class="fas fa-medal text-blue-400 text-2xl mb-3"></i>
                        <h4 class="font-bold mb-2">Award-winning Service</h4>
                        <p class="text-gray-300 text-sm">Consistently rated 5-stars by passengers</p>
                    </div>
                    <div class="bg-[#142857] p-6 rounded-lg">
                        <i class="fas fa-leaf text-blue-400 text-2xl mb-3"></i>
                        <h4 class="font-bold mb-2">Sustainable Travel</h4>
                        <p class="text-gray-300 text-sm">Carbon-neutral flights and eco-initiatives</p>
                    </div>
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
                    <p class="text-sm">Connecting you to the world with exceptional service and comfort.</p>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="index.html" class="hover:text-white">Home</a></li>
                        <li><a href="destination.html" class="hover:text-white">Destinations</a></li>
                        <li><a href="#" class="hover:text-white">Flights</a></li>
                        <li><a href="#about" class="hover:text-white">About Us</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-4">Support</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-white">Contact Us</a></li>
                        <li><a href="#" class="hover:text-white">FAQs</a></li>
                        <li><a href="#" class="hover:text-white">Baggage Policy</a></li>
                        <li><a href="#" class="hover:text-white">Terms & Conditions</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-4">Connect</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="hover:text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="hover:text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="hover:text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="hover:text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm">
                <p>© 2025 Sheron Airways. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Photo shuffle functionality for hero section
        const destinations = [
            'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?ixlib=rb-4.0.3&auto=format&fit=crop&w=2074&q=80',
            'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?ixlib=rb-4.0.3&auto=format&fit=crop&w=2073&q=80',
            'https://images.pexels.com/photos/1797161/pexels-photo-1797161.jpeg',
            'https://images.unsplash.com/photo-1518391846015-55a9cc003b25?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80',
            'https://images.unsplash.com/photo-1498307833015-e7b400441eb8?ixlib=rb-4.0.3&auto=format&fit=crop&w=2028&q=80'
        ];

        let currentDestination = 0;
        const heroSection = document.querySelector('.hero-bg');

        function changeBackground() {
            currentDestination = (currentDestination + 1) % destinations.length;
            heroSection.style.backgroundImage = `linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('${destinations[currentDestination]}')`;
        }

        function nextDestination() {
            changeBackground();
        }

        function prevDestination() {
            currentDestination = (currentDestination - 1 + destinations.length) % destinations.length;
            heroSection.style.backgroundImage = `linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('${destinations[currentDestination]}')`;
        }

        // Auto shuffle every 5 seconds
        setInterval(changeBackground, 5000);

        // Cookie and authentication functions
        function getCookie(name) {
            const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
            return match ? match[2] : null;
        }

        function isLoggedIn() {
            return getCookie("loggedIn") === "true";
        }

        function checkAuth(event) {
            if (!isLoggedIn()) {
                event.preventDefault();
                window.location.href = "signin.php";
                return false;
            }
            return true;
        }

        function protectButtonsAndLinks() {
            // Protect booking buttons
            document.querySelectorAll('.destination-card button').forEach(btn => {
                btn.addEventListener("click", function(e) {
                    if (!isLoggedIn()) {
                        e.preventDefault();
                        window.location.href = "signin.php";
                    }
                });
            });

            // Protect navigation links (excluding #about anchor)
            document.querySelectorAll('nav a:not([href="#about"])').forEach(link => {
                link.addEventListener("click", function(e) {
                    if (!isLoggedIn()) {
                        e.preventDefault();
                        window.location.href = "signin.php";
                    }
                });
            });

            // Update UI based on login status
            const loginBtn = document.getElementById('loginBtn');
            const signupBtn = document.getElementById('signupBtn');
            const logoutBtn = document.getElementById('logoutBtn');

            if (isLoggedIn()) {
                loginBtn.classList.add('hidden');
                signupBtn.classList.add('hidden');
                logoutBtn.classList.remove('hidden');

                // Add logout functionality
                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.cookie = "loggedIn=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                    window.location.href = "index.php";
                });
            } else {
                loginBtn.classList.remove('hidden');
                signupBtn.classList.remove('hidden');
                logoutBtn.classList.add('hidden');

                // Add login/signup functionality
                loginBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = "signin.php";
                });

                signupBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = "signup.php";
                });
            }
        }

        // Check user role
        function checkUserRole() {
            const role = getCookie('userRole');
            if (role === 'user') {
                console.log('Welcome back, user!');
            }
            else if (role === 'admin') {
                console.log('Welcome back, admin!');
            }
            else {
                console.log('Welcome back, guest!');
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            protectButtonsAndLinks();
            checkUserRole();
        });
    </script>
</body>
</html>
