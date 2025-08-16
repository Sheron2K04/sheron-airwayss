<?php
// C:\xampp\htdocs\projectweb\sheronair\includes\config.php
declare(strict_types=1);

// Application environment: 'dev' for development, 'production' for live server
define('APP_ENV', 'dev');

// Your base URL (adjust if needed)
define('BASE_URL', 'http://localhost/projectweb/sheronair');

// Error reporting based on environment
if (APP_ENV === 'dev') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
}
