<?php
session_start();

// Validate session data exists
if (!isset($_SESSION['booking_details'])) {
    header('Location: index.php');
    exit;
}

$booking = $_SESSION['booking_details'];
$bookingType = $booking['type']; // 'flight' or 'hotel'
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment | Sheron Airways</title>
    <meta name="description" content="Complete your booking with secure payment on Sheron Airways. We accept all major credit cards with 256-bit encryption.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0A1A3F;
            --secondary: #1E4AE9;
            --accent: #FF5A5F;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }

        .card-input:focus + .card-icon {
            color: var(--secondary);
        }

        .card-image {
            transition: all 0.3s ease;
        }

        .card-image.selected {
            border: 2px solid var(--secondary);
            box-shadow: 0 0 0 3px rgba(30, 74, 233, 0.15);
        }

        .loading-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: #334155;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 10;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.75rem;
            line-height: 1.4;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        .progress-step {
            transition: all 0.3s ease;
        }

        .progress-step.active {
            background-color: var(--secondary);
            color: white;
        }

        .progress-step.completed {
            background-color: #10b981;
            color: white;
        }

        .payment-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .payment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .input-focus {
            transition: all 0.3s ease;
        }

        .input-focus:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(30, 74, 233, 0.15);
        }

        .btn-primary {
            background-color: var(--secondary);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #1a3fd1;
            transform: translateY(-1px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .sticky-summary {
            position: sticky;
            top: 1rem;
        }

        @media (max-width: 1023px) {
            .sticky-summary {
                position: static;
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-delay-1 { animation-delay: 0.1s; }
        .animate-delay-2 { animation-delay: 0.2s; }
        .animate-delay-3 { animation-delay: 0.3s; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header with progress indicator -->
    <header class="bg-[var(--primary)] shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center text-white font-bold text-xl">
                        <i class="fas fa-plane text-blue-300 mr-2"></i>
                        <span class="hidden sm:inline">Sheron Airways</span>
                    </a>
                </div>

                <div class="hidden md:flex items-center space-x-6">
                    <div class="flex items-center text-white">
                        <div class="w-6 h-6 rounded-full bg-blue-500 text-white flex items-center justify-center text-xs mr-2">3</div>
                        <span>Payment</span>
                    </div>
                    <div class="text-blue-200 flex items-center">
                        <i class="fas fa-lock mr-1"></i>
                        <span>Secure Connection</span>
                    </div>
                </div>

                <div class="md:hidden flex items-center">
                    <button class="text-white focus:outline-none" id="mobile-menu-button">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile menu -->
    <div class="hidden md:hidden bg-[var(--primary)] text-white py-4 px-6 shadow-lg" id="mobile-menu">
        <div class="flex items-center justify-between mb-4">
            <span class="font-medium">Booking Progress</span>
            <span class="text-sm text-blue-200">Step 3 of 4</span>
        </div>
        <div class="w-full bg-gray-700 rounded-full h-2 mb-2">
            <div class="bg-blue-500 h-2 rounded-full" style="width: 75%"></div>
        </div>
        <div class="text-sm text-blue-200 mt-2">
            <i class="fas fa-lock mr-1"></i> Secure Payment
        </div>
    </div>

    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Enhanced Progress Steps -->
        <div class="mb-8 animate-fade-in">
            <div class="hidden md:flex justify-between items-center relative">
                <div class="absolute top-1/2 left-0 right-0 h-1 bg-gray-200 -z-10"></div>
                <div class="absolute top-1/2 left-0 h-1 bg-blue-500 -z-10" style="width: 75%"></div>

                <?php for($i=1; $i<=4; $i++): ?>
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full progress-step <?= $i<3 ? 'completed' : ($i==3 ? 'active' : 'bg-gray-200 text-gray-600') ?> flex items-center justify-center mb-2">
                        <?= $i<3 ? '<i class="fas fa-check text-xs"></i>' : $i ?>
                    </div>
                    <span class="text-sm font-medium <?= $i==3 ? 'text-blue-600' : 'text-gray-600' ?>">
                        <?= ['Booking', 'Details', 'Payment', 'Confirmation'][$i-1] ?>
                    </span>
                </div>
                <?php endfor; ?>
            </div>

            <!-- Mobile progress -->
            <div class="md:hidden bg-white rounded-lg shadow-sm p-4 mb-6 animate-fade-in animate-delay-1">
                <h2 class="text-xl font-bold text-gray-800 mb-1">Payment Information</h2>
                <p class="text-sm text-gray-600 mb-3">Complete your booking with secure payment</p>
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-gray-600">Step 3 of 4</span>
                    <span class="font-medium text-blue-600">75% complete</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: 75%"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Enhanced Payment Form -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden animate-fade-in animate-delay-1">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-[var(--primary)] to-[#1a2a5a]">
                        <h2 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-credit-card mr-3"></i>
                            <span>Payment Method</span>
                        </h2>
                    </div>

                    <div class="p-6">
                        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                            <?php
                            // Form processing logic here
                            ?>
                        <?php endif; ?>

                        <form method="POST" id="payment-form" class="space-y-6">
                            <!-- Card Type Selection with better visual feedback -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Card Type
                                    <span class="text-gray-500 text-xs ml-1">(select one)</span>
                                </label>
                                <div class="grid grid-cols-3 gap-3">
                                    <?php
                                    $cardTypes = [
                                        'visa' => [
                                            'name' => 'Visa',
                                            'img' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/2560px-Visa_Inc._logo.svg.png',
                                            'color' => '#1a1a71'
                                        ],
                                        'mastercard' => [
                                            'name' => 'Mastercard',
                                            'img' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/1280px-Mastercard-logo.svg.png',
                                            'color' => '#eb001b'
                                        ],
                                        'amex' => [
                                            'name' => 'Amex',
                                            'img' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/30/American_Express_logo.svg/1200px-American_Express_logo.svg.png',
                                            'color' => '#016fd0'
                                        ]
                                    ];

                                    foreach ($cardTypes as $value => $card): ?>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="card_type" value="<?= $value ?>" class="hidden peer" <?= $value === 'visa' ? 'checked' : '' ?>>
                                        <div class="card-image p-3 border-2 border-gray-200 rounded-lg peer-checked:selected hover:border-blue-300 transition-all h-full flex flex-col items-center justify-center" style="min-height: 80px;">
                                            <img src="<?= $card['img'] ?>" alt="<?= $card['name'] ?>" class="h-6 mx-auto">
                                            <p class="text-xs text-center mt-2 text-gray-600"><?= $card['name'] ?></p>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Card Number with dynamic card type detection -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                    Card Number
                                    <span class="tooltip ml-1">
                                        <i class="fas fa-info-circle text-gray-400 hover:text-blue-500"></i>
                                        <span class="tooltip-text">16 digits for Visa/Mastercard, 15 for American Express</span>
                                    </span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="far fa-credit-card text-gray-400 card-icon"></i>
                                    </div>
                                    <input type="text" name="card_number" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 card-input input-focus"
                                           placeholder="1234 5678 9012 3456" maxlength="19"
                                           oninput="formatCardNumber(this)" onkeypress="return isNumberKey(event)">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <i id="card-type-icon" class="far fa-credit-card text-gray-300"></i>
                                    </div>
                                </div>
                                <p id="card-number-error" class="mt-1 text-sm text-red-600 hidden">Please enter a valid card number</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <!-- Expiry Date with month/year dropdowns -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="far fa-calendar-alt text-gray-400"></i>
                                        </div>
                                        <input type="text" name="card_expiry" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 input-focus"
                                               placeholder="MM/YY" maxlength="5" oninput="formatExpiryDate(this)">
                                    </div>
                                </div>

                                <!-- CVC with help tooltip -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                        CVC
                                        <span class="tooltip ml-1">
                                            <i class="fas fa-info-circle text-gray-400 hover:text-blue-500"></i>
                                            <span class="tooltip-text" id="cvc-tooltip">3-digit security code on back of card</span>
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-lock text-gray-400"></i>
                                        </div>
                                        <input type="text" name="card_cvc" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 input-focus"
                                               placeholder="123" maxlength="4" onkeypress="return isNumberKey(event)">
                                    </div>
                                </div>

                                <!-- Card PIN with show/hide toggle -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Card PIN</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-key text-gray-400"></i>
                                        </div>
                                        <input type="password" name="card_pin" id="card-pin" class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 input-focus"
                                               placeholder="••••" maxlength="4" onkeypress="return isNumberKey(event)">
                                        <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-500" onclick="togglePinVisibility()">
                                            <i id="pin-eye" class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Cardholder Name -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cardholder Name</label>
                                <input type="text" name="card_name" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 input-focus"
                                       placeholder="Name as shown on card">
                            </div>

                            <!-- Save card option (hidden by default, shown if user is logged in) -->
                            <div class="flex items-center mb-6 hidden" id="save-card-container">
                                <input id="save-card" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="save-card" class="ml-2 block text-sm text-gray-700">
                                    Save this card for future payments
                                </label>
                            </div>

                            <!-- Enhanced Submit Button -->
                            <div class="border-t border-gray-100 pt-6">
                                <button type="submit" id="submit-button" class="w-full btn-primary text-white py-3 px-4 rounded-lg font-medium shadow-sm transition-all flex items-center justify-center">
                                    <i class="fas fa-lock mr-3"></i>
                                    <span>Pay $<?= number_format($booking['total'], 2) ?></span>
                                </button>
                                <div class="flex items-center justify-center mt-4 text-xs text-gray-500">
                                    <i class="fas fa-shield-alt text-blue-500 mr-2"></i>
                                    <span>256-bit SSL encrypted payment</span>
                                </div>
                                <div class="flex items-center justify-center mt-3 space-x-4">
                                    <img src="https://www.vectorlogo.zone/logos/visa/visa-ar21.svg" class="h-6 opacity-70" alt="Visa">
                                    <img src="https://www.vectorlogo.zone/logos/mastercard/mastercard-ar21.svg" class="h-6 opacity-70" alt="Mastercard">
                                    <img src="https://www.vectorlogo.zone/logos/americanexpress/americanexpress-ar21.svg" class="h-6 opacity-70" alt="American Express">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Security Badges -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 animate-fade-in animate-delay-2">
                    <div class="bg-white p-4 rounded-lg border border-gray-200 flex items-center justify-center payment-card">
                        <i class="fas fa-lock text-green-500 mr-2"></i>
                        <span class="text-sm font-medium">SSL Secure</span>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-gray-200 flex items-center justify-center payment-card">
                        <i class="fas fa-user-shield text-blue-500 mr-2"></i>
                        <span class="text-sm font-medium">PCI DSS</span>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-gray-200 flex items-center justify-center payment-card">
                        <i class="fas fa-shield-alt text-purple-500 mr-2"></i>
                        <span class="text-sm font-medium">3D Secure</span>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-gray-200 flex items-center justify-center payment-card">
                        <i class="fas fa-check-circle text-teal-500 mr-2"></i>
                        <span class="text-sm font-medium">Verified</span>
                    </div>
                </div>
            </div>

            <!-- Enhanced Booking Summary -->
            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden sticky-summary animate-fade-in animate-delay-2">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-[var(--primary)] to-[#1a2a5a]">
                        <h2 class="text-xl font-bold text-white flex items-center">
                            <i class="fas <?= $bookingType === 'flight' ? 'fa-plane' : 'fa-hotel' ?> mr-3"></i>
                            <span><?= $bookingType === 'flight' ? 'Flight Details' : 'Hotel Details' ?></span>
                        </h2>
                    </div>

                    <div class="p-6">
                        <?php if ($bookingType === 'flight'): ?>
                            <!-- Enhanced Flight Summary -->
                            <div class="mb-6">
                                <div class="flex justify-between mb-4 items-start">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-800"><?= $booking['from'] ?></p>
                                        <p class="text-sm text-gray-500"><?= date('D, M j, Y', strtotime($booking['departure_date'])) ?></p>
                                    </div>
                                    <div class="px-4 flex flex-col items-center">
                                        <div class="w-16 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-plane text-blue-500 text-xs transform rotate-45"></i>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1"><?= $booking['duration'] ?></p>
                                    </div>
                                    <div class="flex-1 text-right">
                                        <p class="font-medium text-gray-800"><?= $booking['to'] ?></p>
                                        <p class="text-sm text-gray-500"><?= date('D, M j, Y', strtotime($booking['arrival_date'])) ?></p>
                                    </div>
                                </div>

                                <?php if (isset($booking['return_flight'])): ?>
                                <div class="pt-4 border-t border-gray-100">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-800"><?= $booking['to'] ?></p>
                                            <p class="text-sm text-gray-500"><?= date('D, M j, Y', strtotime($booking['return_departure_date'])) ?></p>
                                        </div>
                                        <div class="px-4 flex flex-col items-center">
                                            <div class="w-16 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-plane text-blue-500 text-xs transform rotate-45"></i>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1"><?= $booking['return_duration'] ?></p>
                                        </div>
                                        <div class="flex-1 text-right">
                                            <p class="font-medium text-gray-800"><?= $booking['from'] ?></p>
                                            <p class="text-sm text-gray-500"><?= date('D, M j, Y', strtotime($booking['return_arrival_date'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <h3 class="font-bold text-lg mb-3 flex items-center text-gray-800">
                                        <i class="fas fa-users text-blue-500 mr-2"></i> Passengers
                                    </h3>
                                    <div class="space-y-3">
                                        <?php foreach ($booking['passengers'] as $index => $passenger): ?>
                                            <div class="flex justify-between items-center">
                                                <div>
                                                    <p class="font-medium text-gray-800"><?= $passenger['name'] ?></p>
                                                    <p class="text-sm text-gray-500"><?= $passenger['type'] ?> • <?= $booking['class'] ?></p>
                                                </div>
                                                <?php if ($index === 0): ?>
                                                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">Primary</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                        <?php else: ?>
                            <!-- Enhanced Hotel Summary -->
                            <div class="flex items-start mb-4">
                                <img src="<?= $booking['hotel_image'] ?>" alt="<?= $booking['hotel_name'] ?>" class="w-16 h-16 object-cover rounded-lg mr-3 shadow-sm">
                                <div>
                                    <h4 class="font-medium text-gray-800"><?= $booking['hotel_name'] ?></h4>
                                    <div class="flex items-center mt-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $booking['rating'] ? 'text-yellow-400' : 'text-gray-300' ?> text-sm"></i>
                                        <?php endfor; ?>
                                        <span class="text-xs text-gray-500 ml-1">(<?= $booking['rating'] ?>.0)</span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1"><?= $booking['room_type'] ?></p>
                                    <p class="text-sm text-gray-600"><?= $booking['nights'] ?> nights • <?= count($booking['guests']) ?> guests</p>
                                </div>
                            </div>

                            <div class="mb-4 bg-blue-50 p-3 rounded-lg border border-blue-100">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600 font-medium">Check-in</span>
                                    <span class="font-medium text-gray-800"><?= date('D, M j, Y', strtotime($booking['check_in'])) ?></span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 font-medium">Check-out</span>
                                    <span class="font-medium text-gray-800"><?= date('D, M j, Y', strtotime($booking['check_out'])) ?></span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h4 class="font-medium text-sm mb-2 text-gray-700">Guests</h4>
                                <div class="space-y-2">
                                    <?php foreach ($booking['guests'] as $guest): ?>
                                        <div class="flex items-center">
                                            <i class="fas fa-user text-gray-400 mr-2 text-sm"></i>
                                            <span class="text-sm text-gray-600"><?= $guest['name'] ?> (<?= $guest['type'] ?>)</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Enhanced Price Breakdown -->
                        <div class="border-t border-gray-100 pt-4">
                            <h3 class="font-bold text-lg mb-3 flex items-center text-gray-800">
                                <i class="fas fa-receipt text-blue-500 mr-2"></i> Price Breakdown
                            </h3>

                            <div class="space-y-3 mb-4">
                                <?php if ($bookingType === 'flight'): ?>
                                    <div class="flex justify-between">
                                        <div>
                                            <span class="text-gray-600">Base Fare</span>
                                            <span class="block text-xs text-gray-500"><?= count($booking['passengers']) ?> passengers</span>
                                        </div>
                                        <span class="text-gray-800">$<?= number_format($booking['base_price'], 2) ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="flex justify-between">
                                        <div>
                                            <span class="text-gray-600">Room Rate</span>
                                            <span class="block text-xs text-gray-500"><?= $booking['nights'] ?> nights × $<?= number_format($booking['base_price']/$booking['nights'], 2) ?></span>
                                        </div>
                                        <span class="text-gray-800">$<?= number_format($booking['base_price'], 2) ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="flex justify-between">
                                    <span class="text-gray-600">Taxes & Fees</span>
                                    <span class="text-gray-800">$<?= number_format($booking['taxes'], 2) ?></span>
                                </div>

                                <?php foreach ($booking['extras'] as $extra): ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600"><?= $extra['name'] ?></span>
                                        <span class="text-gray-800">$<?= number_format($extra['price'], 2) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="border-t border-gray-100 pt-3">
                                <div class="flex justify-between font-bold text-lg text-gray-800">
                                    <span>Total</span>
                                    <span>$<?= number_format($booking['total'], 2) ?></span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Includes all taxes and fees</p>

                                <?php if ($bookingType === 'hotel'): ?>
                                    <div class="mt-3 bg-yellow-50 border border-yellow-100 rounded-lg p-3">
                                        <div class="flex items-start">
                                            <i class="fas fa-info-circle text-yellow-500 mt-1 mr-2"></i>
                                            <p class="text-xs text-yellow-800">Free cancellation until <?= date('M j, Y', strtotime($booking['check_in'] . ' - 3 days')) ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Support Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden animate-fade-in animate-delay-3">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-[var(--primary)] to-[#1a2a5a]">
                        <h3 class="text-white font-medium flex items-center">
                            <i class="fas fa-headset mr-3"></i> Need Help?
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center mb-4 p-3 hover:bg-gray-50 rounded-lg transition-all cursor-pointer">
                            <div class="bg-blue-100 p-2 rounded-full mr-3">
                                <i class="fas fa-phone-alt text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Call us 24/7</p>
                                <p class="font-medium text-gray-800">+1 (800) 123-4567</p>
                            </div>
                        </div>
                        <div class="flex items-center p-3 hover:bg-gray-50 rounded-lg transition-all cursor-pointer">
                            <div class="bg-blue-100 p-2 rounded-full mr-3">
                                <i class="fas fa-comment-alt text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Live chat with us</p>
                                <p class="font-medium text-gray-800">Available now</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Enhanced Footer -->
    <footer class="bg-[var(--primary)] text-gray-300 py-12 mt-12">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-white font-bold text-lg mb-4">Sheron Airways</h3>
                    <p class="text-sm mb-4">Making travel simple, comfortable and accessible for everyone.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-300 hover:text-white transition-colors"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-300 hover:text-white transition-colors"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-300 hover:text-white transition-colors"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-300 hover:text-white transition-colors"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div>
                    <h4 class="text-white font-bold text-lg mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="hover:text-white transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 w-4 text-center"></i> Home</a></li>
                        <li><a href="destination.php" class="hover:text-white transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 w-4 text-center"></i> Destinations</a></li>
                        <li><a href="#" class="hover:text-white transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 w-4 text-center"></i> Flights</a></li>
                        <li><a href="#" class="hover:text-white transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 w-4 text-center"></i> Hotels</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold text-lg mb-4">Support</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-white transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 w-4 text-center"></i> Contact Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 w-4 text-center"></i> FAQs</a></li>
                        <li><a href="#" class="hover:text-white transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 w-4 text-center"></i> Payment Options</a></li>
                        <li><a href="#" class="hover:text-white transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 w-4 text-center"></i> Terms & Conditions</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold text-lg mb-4">Newsletter</h4>
                    <p class="text-sm mb-3">Subscribe for exclusive deals and travel updates</p>
                    <form class="flex">
                        <input type="email" placeholder="Your email" class="px-3 py-2 text-sm rounded-l-lg border border-gray-600 bg-[#1a2a5a] focus:outline-none focus:border-blue-500 text-white placeholder-gray-400">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 text-sm rounded-r-lg transition-colors">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                    <p class="text-xs text-gray-400 mt-2">We respect your privacy. Unsubscribe anytime.</p>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm">
                <p>© 2025 Sheron Airways. All rights reserved.</p>
                <div class="flex justify-center space-x-4 mt-2">
                    <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
                    <a href="#" class="hover:text-white transition-colors">Accessibility</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });

        // Enhanced Card Number Formatting
        function formatCardNumber(input) {
            // Remove all non-digit characters
            let value = input.value.replace(/\D/g, '');

            // Add spaces every 4 digits
            if (value.length > 0) {
                value = value.match(new RegExp('.{1,4}', 'g')).join(' ');
            }

            // Update the input value
            input.value = value;

            // Detect card type and update icon
            detectCardType(value.replace(/\s/g, ''));

            // Validate length
            const cardType = document.querySelector('input[name="card_type"]:checked').value;
            const isValidLength = (
                (cardType === 'amex' && value.replace(/\s/g, '').length === 15) ||
                (cardType !== 'amex' && value.replace(/\s/g, '').length === 16)
            );

            // Update error message
            const errorElement = document.getElementById('card-number-error');
            if (!isValidLength && value.replace(/\s/g, '').length > 0) {
                errorElement.classList.remove('hidden');
                input.classList.add('border-red-500');
                input.classList.remove('border-gray-300');
            } else {
                errorElement.classList.add('hidden');
                input.classList.remove('border-red-500');
                input.classList.add('border-gray-300');
            }
        }

        // Detect card type based on number
        function detectCardType(cardNumber) {
            const icon = document.getElementById('card-type-icon');

            // Visa starts with 4
            if (/^4/.test(cardNumber)) {
                icon.className = 'fab fa-cc-visa text-blue-800';
            }
            // Mastercard starts with 5
            else if (/^5/.test(cardNumber)) {
                icon.className = 'fab fa-cc-mastercard text-red-600';
            }
            // Amex starts with 3
            else if (/^3/.test(cardNumber)) {
                icon.className = 'fab fa-cc-amex text-blue-500';
            }
            // Default
            else {
                icon.className = 'far fa-credit-card text-gray-300';
            }
        }

        // Format expiry date as MM/YY
        function formatExpiryDate(input) {
            let value = input.value.replace(/\D/g, '');

            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }

            input.value = value;
        }

        // Toggle PIN visibility
        function togglePinVisibility() {
            const pinInput = document.getElementById('card-pin');
            const eyeIcon = document.getElementById('pin-eye');

            if (pinInput.type === 'password') {
                pinInput.type = 'text';
                eyeIcon.className = 'fas fa-eye-slash';
            } else {
                pinInput.type = 'password';
                eyeIcon.className = 'fas fa-eye';
            }
        }

        // Only allow numbers in numeric fields
        function isNumberKey(evt) {
            const charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
            return true;
        }

        // Update CVC placeholder based on card type
        document.querySelectorAll('input[name="card_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const cvcInput = document.querySelector('input[name="card_cvc"]');
                const tooltip = document.getElementById('cvc-tooltip');

                if (this.value === 'amex') {
                    cvcInput.placeholder = '4 digits';
                    cvcInput.maxLength = 4;
                    tooltip.textContent = '4-digit security code on front of card';
                } else {
                    cvcInput.placeholder = '3 digits';
                    cvcInput.maxLength = 3;
                    tooltip.textContent = '3-digit security code on back of card';
                }
            });
        });

        // Form submission with loading state
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submit-button');
            const originalText = submitBtn.innerHTML;

            // Validate form
            const cardNumber = document.querySelector('input[name="card_number"]').value.replace(/\s/g, '');
            const cardType = document.querySelector('input[name="card_type"]:checked').value;
            const cardExpiry = document.querySelector('input[name="card_expiry"]').value;
            const cardCvc = document.querySelector('input[name="card_cvc"]').value;
            const cardName = document.querySelector('input[name="card_name"]').value;

            // Check card number length
            if ((cardType === 'amex' && cardNumber.length !== 15) ||
                (cardType !== 'amex' && cardNumber.length !== 16)) {
                document.getElementById('card-number-error').classList.remove('hidden');
                document.querySelector('input[name="card_number"]').classList.add('border-red-500');
                return;
            }

            // Check expiry date
            if (!/^\d{2}\/\d{2}$/.test(cardExpiry)) {
                alert('Please enter a valid expiry date in MM/YY format');
                return;
            }

            // Check CVC
            const cvcLength = cardType === 'amex' ? 4 : 3;
            if (cardCvc.length !== cvcLength) {
                alert(Please enter a valid ${cvcLength}-digit CVC code);
                return;
            }

            // Check cardholder name
            if (cardName.trim() === '') {
                alert('Please enter the cardholder name');
                return;
            }

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <i class="fas fa-circle-notch loading-spinner mr-2"></i>
                <span>Processing Payment...</span>
            `;

            // Simulate API call
            setTimeout(() => {
                // In a real app, you would submit to server here
                // For demo, we'll show a success message
                submitBtn.innerHTML = `
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>Payment Successful!</span>
                `;

                // Redirect to confirmation page after 1.5 seconds
                setTimeout(() => {
                    window.location.href = 'confirmation.php';
                }, 1500);
            }, 2000);
        });

        // Show save card option if user is logged in
        // In a real app, you would check if user is authenticated
        if (false) { // Replace with actual auth check
            document.getElementById('save-card-container').classList.remove('hidden');
        }
    </script>
</body>
</html>
