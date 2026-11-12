<?php
// Database connection (mysqli). Change these values to match your XAMPP / hosting setup.
const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';              // XAMPP default is an empty password
const DB_NAME = 'expense_tracker';

// Make mysqli throw exceptions instead of silent warnings, so errors are never ignored.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // Never show raw DB errors to visitors; log them instead.
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Check config/db.php and that MySQL is running.');
}
