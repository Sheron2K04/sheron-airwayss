<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sheron Airlines - Destinations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            900: '#0A1A3F',
                            800: '#142857',
                            700: '#1E3A8A'
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #0A1A3F;
        }
        .destination-card {
            transition: all 0.3s ease;
            background-color: #142857;
        }
        .destination-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="text-white">
    <!-- Navigation -->
    <header class="bg-navy-900 sticky top-0 z-50 shadow-lg">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-plane text-blue-400 text-2xl"></i>
                    <h1 class="text-2xl font-bold">Sheron Airlines</h1>
                </div>
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="index.html" class="hover:text-blue-300 flex items-center">
                        <i class="fas fa-home mr-2"></i> Home
                    </a>
                    <a href="#" class="text-blue-300 font-medium flex items-center">
                        <i class="fas fa-map-marked-alt mr-2"></i> Destinations
                    </a>
                    <a href="#" class="hover:text-blue-300 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i> About
                    </a>
                    <a href="signup.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                        Sign Up
                    </a>
                </nav>
                <button class="md:hidden text-xl">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="bg-navy-800 py-20">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">Discover Our Exclusive Destinations</h1>
            <p class="text-xl text-blue-200 max-w-2xl mx-auto">Explore 15 breathtaking locations with Sheron Airlines' premium service</p>
        </div>
    </section>

    <!-- Destinations Grid -->
    <section class="py-16 bg-navy-900">
        <div class="container mx-auto px-4">
            <div class="mb-12 text-center">
                <h2 class="text-3xl font-bold mb-4">Featured Locations</h2>
                <p class="text-blue-200 max-w-2xl mx-auto">Select your dream destination and experience world-class travel</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Destination Card 1 -->
                <div class="destination-card rounded-xl overflow-hidden shadow-md border border-navy-700">
                    <div class="relative h-48">
                        <img src="https://images.unsplash.com/photo-1503917988258-f87a78e3c995?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                         alt="Paris" class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Paris, France</h3>
                        <p class="text-blue-200 mb-4">Experience the romance of the City of Lights with iconic landmarks like the Eiffel Tower and Louvre Museum.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium">From $1,299</span>
                            <button class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-chevron-right text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Destination Card 2 -->
                <div class="destination-card rounded-xl overflow-hidden shadow-md border border-navy-700">
                    <div class="relative h-48">
                        <img src="https://images.unsplash.com/photo-1538970272646-f61fabb3be33?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                         alt="Tokyo" class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Tokyo, Japan</h3>
                        <p class="text-blue-200 mb-4">Discover where ancient tradition meets cutting-edge technology in this vibrant metropolis.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium">From $1,799</span>
                            <button class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-chevron-right text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Destination Card 3 -->
                <div class="destination-card rounded-xl overflow-hidden shadow-md border border-navy-700">
                    <div class="relative h-48">
                        <img src="https://images.unsplash.com/photo-1518391846015-55a9cc003b25?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                         alt="New York" class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">New York, USA</h3>
                        <p class="text-blue-200 mb-4">The city that never sleeps offers iconic attractions from Times Square to Central Park.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium">From $1,099</span>
                            <button class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-chevron-right text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Destination Card 4 -->
                <div class="destination-card rounded-xl overflow-hidden shadow-md border border-navy-700">
                    <div class="relative h-48">
                        <img src="https://images.unsplash.com/photo-1523482580672-f109ba8cb9be?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                         alt="Rome" class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Rome, Italy</h3>
                        <p class="text-blue-200 mb-4">Walk through history in the Eternal City with ancient ruins and Renaissance art at every turn.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium">From $1,199</span>
                            <button class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-chevron-right text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Destination Card 5 -->
                <div class="destination-card rounded-xl overflow-hidden shadow-md border border-navy-700">
                    <div class="relative h-48">
                        <img src="https://images.unsplash.com/photo-1527631746610-bca00a040d60?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                         alt="Bali" class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Bali, Indonesia</h3>
                        <p class="text-blue-200 mb-4">Tropical paradise with lush jungles, pristine beaches, and vibrant cultural traditions.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium">From $899</span>
                            <button class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-chevron-right text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Destination Card 6 -->
                <div class="destination-card rounded-xl overflow-hidden shadow-md border border-navy-700">
                    <div class="relative h-48">
                        <img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                         alt="Sydney" class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Sydney, Australia</h3>
                        <p class="text-blue-200 mb-4">Stunning harbor views, iconic Opera House, and beautiful beaches await down under.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium">From $1,599</span>
                            <button class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-chevron-right text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Destination Card 7 -->
                <div class="destination-card rounded-xl overflow-hidden shadow-md border border-navy-700">
                    <div class="relative h-48">
                        <img src="https://images.unsplash.com/photo-1464037866556-6812c9d1c72e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                         alt="Rio de Janeiro" class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Rio de Janeiro, Brazil</h3>
                        <p class="text-blue-200 mb-4">Vibrant culture, stunning beaches, and the iconic Christ the Redeemer statue.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium">From $1,099</span>
                            <button class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-chevron-right text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Destination Card 8 -->
                <div class="destination-card rounded-xl overflow-hidden shadow-md border border-navy-700">
                    <div class="relative h-48">
                        <img src="https://images.unsplash.com/photo-1506197603052-3cc9c3a201bd?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                         alt="Cape Town" class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Cape Town, South Africa</h3>
                        <p class="text-blue-200 mb-4">Breathtaking landscapes, Table Mountain, and incredible wildlife experiences.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium">From $1,299</span>
                            <button class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-chevron-right text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Destination Card 9 -->
                <div class="destination-card rounded-xl overflow-hidden shadow-md border border-navy-700">
                    <div class="relative h-48">
                        <img src="https://images.unsplash.com/photo-1533107862482-0e6974b06ec4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                         alt="Dubai" class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Dubai, UAE</h3>
                        <p class="text-blue-200 mb-4">Ultra-modern architecture, luxury shopping, and desert adventures.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium">From $1,499</span>
                            <button class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-chevron-right text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Destination Card 10 -->
                <div class="destination-card rounded-xl overflow-hidden shadow-md border border-navy-700">
                    <div class="relative h-48">
                        <img src="https://images.unsplash.com/photo-1503917988258-f87a78e3c995?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                         alt="Barcelona" class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Barcelona, Spain</h3>
                        <p class="text-blue-200 mb-4">Gaudi's architectural wonders, vibrant culture, and Mediterranean beaches.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium">From $1,099</span>
                            <button class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-chevron-right text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Destination Card 11 -->
                <div class="destination-card rounded-xl overflow-hidden shadow-md border border-navy-700">
                    <div class="relative h-48">
                        <img src="https://images.unsplash.com/photo-1523482580672-f109ba8cb9be?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                         alt="Venice" class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Venice, Italy</h3>
                        <p class="text-blue-200 mb-4">Romantic canals, historic architecture, and unparalleled charm.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium">From $1,399</span>
                            <button class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-chevron-right text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Destination Card 12 -->
                <div class="destination-card rounded-xl overflow-hidden shadow-md border border-navy-700">
                    <div class="relative h-48">
                        <img src="https://images.unsplash.com/photo-1538970272646-f61fabb3be33?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                         alt="Kyoto" class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Kyoto, Japan</h3>
                        <p class="text-blue-200 mb-4">Traditional temples, serene gardens, and authentic geisha culture.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium">From $1,699</span>
                            <button class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-chevron-right text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Destination Card 13 -->
                <div class="destination-card rounded-xl overflow-hidden shadow-md border border-navy-700">
                    <div class="relative h-48">
                        <img src="https://images.unsplash.com/photo-1518391846015-55a9cc003b25?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                         alt="Santorini" class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Santorini, Greece</h3>
                        <p class="text-blue-200 mb-4">White-washed buildings, blue domes, and stunning sunsets over the Aegean.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium">From $1,299</span>
                            <button class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-chevron-right text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Destination Card 14 -->
                <div class="destination-card rounded-xl overflow-hidden shadow-md border border-navy-700">
                    <div class="relative h-48">
                        <img src="https://images.unsplash.com/photo-1527631746610-bca00a040d60?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                         alt="Machu Picchu" class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Machu Picchu, Peru</h3>
                        <p class="text-blue-200 mb-4">Ancient Inca city nestled in the Andes mountains, a wonder of the world.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium">From $1,199</span>
                            <button class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-chevron-right text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Destination Card 15 -->
                <div class="destination-card rounded-xl overflow-hidden shadow-md border border-navy-700">
                    <div class="relative h-48">
                        <img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                         alt="Queenstown" class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Queenstown, New Zealand</h3>
                        <p class="text-blue-200 mb-4">Adventure capital with stunning landscapes and thrilling activities.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium">From $1,599</span>
                            <button class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-chevron-right text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-16 bg-navy-800">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold mb-6">Ready to Explore the World?</h2>
            <p class="text-xl text-blue-200 mb-8 max-w-2xl mx-auto">Join Sheron Airlines for an unforgettable travel experience</p>
            <a href="signup.html" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-lg text-lg font-medium">
                Sign Up Now <i class="fas fa-user-plus ml-2"></i>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-navy-900 py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4 flex items-center">
                        <i class="fas fa-plane text-blue-400 mr-3"></i> Sheron Airlines
                    </h3>
                    <p class="text-blue-200">Premium air travel experiences since 2010</p>
                </div>
                <div>
                    <h4 class="text-lg font-bold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="index.html" class="text-blue-200 hover:text-white"><i class="fas fa-home mr-2"></i> Home</a></li>
                        <li><a href="#" class="text-blue-200 hover:text-white"><i class="fas fa-map-marked-alt mr-2"></i> Destinations</a></li>
                        <li><a href="#" class="text-blue-200 hover:text-white"><i class="fas fa-info-circle mr-2"></i> About Us</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-bold mb-4">Connect With Us</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-blue-200 hover:text-white text-xl"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-blue-200 hover:text-white text-xl"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-blue-200 hover:text-white text-xl"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-navy-700 mt-12 pt-8 text-center text-blue-200">
                <p>&copy; 2025 Sheron Airlines. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
