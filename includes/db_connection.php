<?php
// includes/db_connection.php

// --- Adjust only these if needed ---
$host = '127.0.0.1';   // prefer 127.0.0.1 on Windows
$port = '5432';
$db   = 'airline';
$user = 'postgres';
$pass = 'SH@eron?2004';
// -----------------------------------

$dsn = "pgsql:host={$host};port={$port};dbname={$db};";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,                  // use native prepares
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);

    // --- TEMP sanity check; comment out after confirming it works ---
    // $v = $conn->query('SELECT version()')->fetchColumn();
    // error_log("PG connected: $v");
} catch (PDOException $e) {
    // Log the detailed reason to Apache error log, show generic message to user
    error_log('DB connect error: ' . $e->getMessage());
    die('Database connection failed.');
}
