<?php
require_once 'stripe_api.php';
require_once 'logger.php';
require_once 'vendor/autoload.php';
require_once 'config.php';
require_once 'db.php';  // Make sure this exists and creates $mysqli connection

use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;

$logger = new PaymentLogger();

// Get webhook payload
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $stripe = new \Stripe\StripeClient(STRIPE_SECRET_KEY);
    $event = Webhook::constructEvent(
        $payload, $sig_header, STRIPE_WEBHOOK_SECRET
    );
    
    // Start transaction
    $mysqli->begin_transaction();
    
    try {
        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                handleSuccessfulPayment($paymentIntent);
                $logger->log("Payment succeeded: " . $paymentIntent->id);
                break;
            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                handleFailedPayment($paymentIntent);
                $logger->log("Payment failed: " . $paymentIntent->id);
                break;
            case 'payment_intent.canceled':
                $paymentIntent = $event->data->object;
                handleCanceledPayment($paymentIntent);
                $logger->log("Payment canceled: " . $paymentIntent->id);
                break;
            default:
                $logger->log("Unhandled webhook event type: " . $event->type);
                break;
        }
        
        // Commit transaction if everything succeeded
        $mysqli->commit();
        http_response_code(200);
    } catch (Exception $e) {
        // Rollback transaction on error
        $mysqli->rollback();
        throw $e;
    }
} catch(\UnexpectedValueException $e) {
    $logger->logPaymentError($e);
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    $logger->logPaymentError($e);
    http_response_code(400);
    exit();
} catch(Exception $e) {
    $logger->logPaymentError($e);
    http_response_code(500);
    exit();
}

function handleSuccessfulPayment($paymentIntent) {
    global $mysqli, $logger, $conn;
    
    $payment_id = $mysqli->real_escape_string($paymentIntent->id);
    $amount = $paymentIntent->amount / 100;
    $currency = $mysqli->real_escape_string($paymentIntent->currency);
    $payment_status = $mysqli->real_escape_string($paymentIntent->status);
    $payer_email = $mysqli->real_escape_string($paymentIntent->charges->data[0]->billing_details->email ?? '');
    $payer_name = $mysqli->real_escape_string($paymentIntent->charges->data[0]->billing_details->name ?? '');
    
    // Get user ID from metadata if available
    $user_id = 0;
    if (isset($paymentIntent->metadata->user_id)) {
        $user_id = intval($paymentIntent->metadata->user_id);
    }
    
    // Check if payment already exists to avoid duplicates
    $result = $mysqli->query("SELECT payment_id FROM payments WHERE payment_id = '".$payment_id."'");
    if ($result->num_rows === 0) {
        // Insert into payments table with complete details
        $mysqli->query("INSERT INTO payments(payment_id, user_id, payer_name, payer_email, amount, currency, payment_status) 
                   VALUES('".$payment_id."', '".$user_id."', '".$payer_name."', '".$payer_email."', '".$amount."', '".$currency."', '".$payment_status."')");
        $logger->log("Payment recorded in database: " . $payment_id);
        
        // Check if we need to create an order record
        if ($user_id > 0) {
            $order_check = $mysqli->query("SELECT id FROM orders WHERE user_id = '".$user_id."' AND total_price = '".$amount."' AND DATE(placed_on) = CURDATE()");
            
            if ($order_check->num_rows === 0) {
                // No order exists, create one
                $method = 'stripe';
                $number = '';
                $address = 'Address provided during payment';
                $placed_on = date('Y-m-d');
                $total_products = 'Order processed via webhook';
                
                $mysqli->query("INSERT INTO orders (user_id, name, number, email, method, address, total_products, 
                             total_price, placed_on, payment_status) 
                             VALUES ('".$user_id."', '".$payer_name."', '".$number."', '".$payer_email."', 
                             '".$method."', '".$address."', '".$total_products."', '".$amount."', '".$placed_on."', 'completed')");
                
                $logger->log("Order created from webhook for payment: " . $payment_id);
            }
        }
    } else {
        $logger->log("Payment already exists in database: " . $payment_id);
    }
}

function handleFailedPayment($paymentIntent) {
    global $mysqli, $logger;
    
    $payment_id = $mysqli->real_escape_string($paymentIntent->id);
    $amount = $paymentIntent->amount / 100;
    $currency = $mysqli->real_escape_string($paymentIntent->currency);
    $payment_status = 'failed';
    $payer_email = $mysqli->real_escape_string($paymentIntent->charges->data[0]->billing_details->email ?? '');
    
    // Update or insert the failed payment
    $result = $mysqli->query("SELECT payment_id FROM payments WHERE payment_id = '".$payment_id."'");
    if ($result->num_rows === 0) {
        $mysqli->query("INSERT INTO payments(payment_id, payer_email, amount, currency, payment_status) 
                   VALUES('".$payment_id."', '".$payer_email."', '".$amount."', '".$currency."', '".$payment_status."')");
    } else {
        $mysqli->query("UPDATE payments SET payment_status = 'failed' WHERE payment_id = '".$payment_id."'");
    }
    $logger->log("Failed payment recorded: " . $payment_id);
}

function handleCanceledPayment($paymentIntent) {
    global $mysqli, $logger;
    
    $payment_id = $mysqli->real_escape_string($paymentIntent->id);
    $amount = $paymentIntent->amount / 100;
    $currency = $mysqli->real_escape_string($paymentIntent->currency);
    $payment_status = 'canceled';
    $payer_email = $mysqli->real_escape_string($paymentIntent->charges->data[0]->billing_details->email ?? '');
    
    // Update or insert the canceled payment
    $result = $mysqli->query("SELECT payment_id FROM payments WHERE payment_id = '".$payment_id."'");
    if ($result->num_rows === 0) {
        $mysqli->query("INSERT INTO payments(payment_id, payer_email, amount, currency, payment_status) 
                   VALUES('".$payment_id."', '".$payer_email."', '".$amount."', '".$currency."', '".$payment_status."')");
    } else {
        $mysqli->query("UPDATE payments SET payment_status = 'canceled' WHERE payment_id = '".$payment_id."'");
    }
    $logger->log("Canceled payment recorded: " . $payment_id);
}
