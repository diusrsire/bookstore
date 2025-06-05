<?php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "vendor/autoload.php";
require_once "payments_config.php";

use Omnipay\Omnipay;
use Stripe\Stripe;

// Define your constants first!
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51RURKuFKhPQIqm6g8Mze7MUIyr8b7LoC828K6DyM4PCQ3ksj71JG0I6k2zaR9qZvfZr8MFEnWZbQi5QBycd1Ldm8001wJzG792');
define('STRIPE_SECRET_KEY', 'sk_test_51RURKuFKhPQIqm6g3BEA9RhRqvKqxwYqWfm9ckTo48tVtxDjS4qtiElxABioyInu0iTXH5VRZs6p3eziK51eFt2R00rvAGJklZ');
define('STRIPE_WEBHOOK_SECRET', 'whsec_IR4pqtWUTmN1yqwNtbwHYiqTRofTAd2I'); // Add your webhook secret here

// Ensure RETURN_URL is correctly set with protocol and full path
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('RETURN_URL', $protocol . $host . '/final/confirm_stripe.php');

define('PAYMENT_CURRENCY', 'USD');

// Initialize Stripe SDK for webhook handling
Stripe::setApiKey(STRIPE_SECRET_KEY);

// Initialize Omnipay Stripe Gateway with PaymentIntents
$gateway = Omnipay::create('Stripe\PaymentIntents');
$gateway->initialize([
    'apiKey' => STRIPE_SECRET_KEY
]);

// Make gateway available globally
$GLOBALS['gateway'] = $gateway;
?>