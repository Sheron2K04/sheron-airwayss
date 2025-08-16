<?php
// Start the session to access stored data
session_start();

// Check if signup data exists in session
if (!isset($_SESSION['signup_data'])) {
    // Redirect to signup page if accessed directly
    header("Location: signup.php");
    exit();
}

// Get user data from session
$user_data = $_SESSION['signup_data'];

// Clear the session data
unset($_SESSION['signup_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup Success | Sheron Airways</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0A1A3F 0%, #142857 100%);
        }
    </style>
</head>
<body class="min-h-screen font-sans text-gray-800">
    <!-- Navigation Bar (same as signup.php) -->
    <nav class="bg-navy-900 text-white shadow-lg sticky top-0 z-50">
        <!-- ... same navigation code as in signup.php ... -->
    </nav>

    <!-- Success Content -->
    <main class="flex items-center justify-center min-h-[calc(100vh-4rem)] p-4">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-xl shadow-2xl overflow-hidden transition-all duration-300">
                <!-- Success Header -->
                <div class="bg-navy-900 py-8 px-8 text-center">
                    <div class="flex justify-center mb-2">
                        <div class="w-16 h-16 rounded-full bg-green-500 flex items-center justify-center shadow-inner">
                            <i class="fas fa-check text-white text-2xl"></i>
                        </div>
                    </div>
                    <h1 class="text-3xl font-bold text-white font-display">Welcome, <?php echo htmlspecialchars($user_data['first_name']); ?>!</h1>
                    <p class="text-blue-200 mt-2">Your account has been created successfully</p>
                </div>

                <!-- Success Content -->
                <div class="p-8 text-center">
                    <div class="mb-6">
                        <i class="fas fa-envelope-open-text text-5xl text-blue-500 mb-4"></i>
                        <h2 class="text-xl font-semibold text-gray-800 mb-2">Verify Your Email</h2>
                        <p class="text-gray-600">We've sent a confirmation email to <span class="font-medium"><?php echo htmlspecialchars($user_data['email']); ?></span></p>
                    </div>
                    
                    <div class="bg-blue-50 p-4 rounded-lg mb-6">
                        <h3 class="font-medium text-blue-800 mb-2">What's next?</h3>
                        <ul class="text-left text-sm text-gray-700 space-y-2">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span>Check your email for the verification link</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span>Click the link to verify your account</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span>Sign in to access your account</span>
                            </li>
                        </ul>
                    </div>

                    <a href="signin.php" class="inline-block w-full px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition duration-200">
                        Go to Sign In
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>