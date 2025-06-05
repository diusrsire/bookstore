<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/vendor/autoload.php';

// Set environment based on server name
define('ENVIRONMENT', isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost' ? 'development' : 'production');

// PayPal Configuration
define("CLIENT_ID", "AcB_kvPawBvCulJ-UGwHbxOZgQJAsBkDcYuOV26brDjhk3YcWS2BwI7IQKpbDyB0CJ2s--PL1Jh1mUik");
define("CLIENT_SECRET", "EG3lNp_BFdnnGXcRRQowkWTnM_opP6JqDfQ97uOPzGJXmyQbWqaNLBOvLSlGahwDyaCauPyUBoemYtdQ");
define("PAYPAL_RETURN_URL", "https://localhost/paypal/success.php");
define("PAYPAL_CANCEL_URL", "https://localhost/paypal/cancel.php");
define("PAYPAL_CURRENCY", "USD");

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'shop_db');

// Create database connection
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Enable error reporting
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Set charset to handle special characters correctly
    mysqli_set_charset($conn, 'utf8mb4');
    
} catch (Exception $e) {
    // Log the error
    error_log("Database connection failed: " . $e->getMessage());
    
    // In production, show a user-friendly message
    if (!headers_sent()) {
        http_response_code(500);
    }
    
    // Only show detailed error in development
    if (ENVIRONMENT === 'development') {
        die("Connection failed: " . $e->getMessage());
    } else {
        die("Internal server error. Please try again later.");
    }
}
?>