<?php
require_once 'config.php';
require_once 'stripe_api.php';

// Add debugging to track what parameters are coming in
$debug_message = "Session user_id: " . ($_SESSION['user_id'] ?? 'not set') . "\n";
$debug_message .= "GET params: " . json_encode($_GET) . "\n";
$debug_message .= "POST params: " . json_encode($_POST) . "\n";

// Log debug message
file_put_contents('logs/payment_debug.log', date('Y-m-d H:i:s') . ' - ' . $debug_message . "\n", FILE_APPEND);

// PayPal payment handling
if (array_key_exists('paymentId', $_GET) && array_key_exists('PayerID', $_GET)) {
    $transaction = $gateway->completePurchase(array(
        'payer_id'=> $_GET['PayerID'],
        'transactionReference'=> $_GET['paymentId'],
    ));

    $response = $transaction->send();

    if ($response->isSuccessful()) {
        //customer has paid successfully
        $arr_body = $response->getData();

        $payment_id = $arr_body['id'];
        $payer_Id = $arr_body['payer']['payer_info']['payer_id'];
        $payer_email = $arr_body['payer']['payer_info']['email'];
        $payer_name = $arr_body['payer']['payer_info']['first_name'] . ' ' . $arr_body['payer']['payer_info']['last_name'];
        $amount = $arr_body['transactions'][0]['amount']['total'];
        $currency = PAYPAL_CURRENCY;
        $payment_status = $arr_body['state'];
        $user_id = $_SESSION['user_id'] ?? 0;

        // Insert into database using the correct schema
        $sql = "INSERT INTO payments(payment_id, user_id, payer_name, payer_email, amount, currency, payment_status) 
                VALUES(?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissdss", $payment_id, $user_id, $payer_name, $payer_email, $amount, $currency, $payment_status);
        $stmt->execute();
        
        echo "Payment is successful. Your transaction id is: ".$payment_id;
    } else {
        echo $response->getMessage();
    }
} 
// Stripe payment handling
else if (isset($_GET['payment_intent']) || isset($_SESSION['last_payment_intent'])) {
    try {
        $stripe = new \Stripe\StripeClient(STRIPE_SECRET_KEY);
        
        // Get payment intent ID with proper validation
        $payment_intent_id = $_GET['payment_intent'] ?? $_SESSION['last_payment_intent'] ?? '';
        
        // Add debug log
        file_put_contents('logs/payment_debug.log', date('Y-m-d H:i:s') . ' - Using payment_intent: ' . $payment_intent_id . "\n", FILE_APPEND);
        
        // Validate the payment_intent_id is not empty before trying to retrieve it
        if (empty(trim($payment_intent_id))) {
            throw new Exception("Payment intent ID cannot be empty");
        }
        
        // Now retrieve the payment intent with a valid ID
        $payment_intent = $stripe->paymentIntents->retrieve($payment_intent_id);
        
        if ($payment_intent->status === 'succeeded') {
            // Payment succeeded
            $payment_id = $payment_intent->id;
            $payer_email = $payment_intent->charges->data[0]->billing_details->email ?? '';
            $payer_name = $payment_intent->charges->data[0]->billing_details->name ?? '';
            $amount = $payment_intent->amount / 100; // Convert from cents
            $currency = $payment_intent->currency;
            $payment_status = $payment_intent->status;
            $user_id = $_SESSION['user_id'] ?? 0;
            
            // Insert into database using the correct schema
            $sql = "INSERT INTO payments(payment_id, user_id, payer_name, payer_email, amount, currency, payment_status) 
                    VALUES(?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sissdss", $payment_id, $user_id, $payer_name, $payer_email, $amount, $currency, $payment_status);
            $stmt->execute();
            
            // Check if an order record exists for this payment
            $order_check_query = "SELECT * FROM orders WHERE user_id = ? AND total_price = ? AND DATE(placed_on) = CURDATE() 
                                 AND payment_status IN ('completed', 'pending')";
            $check_stmt = $conn->prepare($order_check_query);
            $check_stmt->bind_param("id", $user_id, $amount);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows === 0) {
                // Get cart products for this user
                $cart_query = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'");
                $total_products = '';
                
                if (mysqli_num_rows($cart_query) > 0) {
                    $product_names = [];
                    while ($cart_item = mysqli_fetch_assoc($cart_query)) {
                        $product_names[] = $cart_item['name'] . ' (' . $cart_item['quantity'] . ')';
                    }
                    $total_products = implode(', ', $product_names);
                }
                
                // If no products in cart but we have a successful payment, use a placeholder
                if (empty($total_products)) {
                    $total_products = "Order paid via Stripe";
                }
                
                // Insert order record
                $order_sql = "INSERT INTO orders (user_id, name, number, email, method, address, total_products, 
                             total_price, placed_on, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $order_stmt = $conn->prepare($order_sql);
                $method = 'stripe';
                $number = ''; // Phone number may not be available
                $address = 'Address provided during checkout'; 
                $placed_on = date('Y-m-d');
                $status = 'completed';
                
                $order_stmt->bind_param("issssssdss", $user_id, $payer_name, $number, $payer_email, 
                                      $method, $address, $total_products, $amount, $placed_on, $status);
                $order_stmt->execute();
                
                // Clear cart
                mysqli_query($conn, "DELETE FROM `cart` WHERE user_id = '$user_id'");
                
                // Log successful order creation
                file_put_contents('logs/payment_debug.log', date('Y-m-d H:i:s') . ' - Created order record for payment: ' . $payment_id . "\n", FILE_APPEND);
            }
            
            echo "Payment is successful. Your transaction id is: ".$payment_id;
        } else {
            echo "Payment is " . $payment_intent->status;
        }    } catch (\Stripe\Exception\InvalidArgumentException $e) {
        // Log the error
        file_put_contents('logs/payment_debug.log', date('Y-m-d H:i:s') . ' - Invalid Argument Error: ' . $e->getMessage() . "\n", FILE_APPEND);
        
        // Check for payment in recent orders
        $success_message = "";
        if (isset($_SESSION['user_id'])) {
            // Query the most recent payment for this user
            $recent_payment_query = "SELECT * FROM payments WHERE user_id = " . intval($_SESSION['user_id']) . " ORDER BY created DESC LIMIT 1";
            $recent_payment = $conn->query($recent_payment_query);
            
            if ($recent_payment && $recent_payment->num_rows > 0) {
                $payment_data = $recent_payment->fetch_assoc();
                $success_message = "<div class='message'>Your payment may have already been processed. Most recent payment ID: " . 
                    htmlspecialchars($payment_data['payment_id']) . "</div>";
            }
        }
        
        echo $success_message ?: "Error: The payment information was not found or was invalid. Please contact support if your payment was processed.";
    } catch (\Stripe\Exception\ApiErrorException $e) {
        // Log the error
        file_put_contents('logs/payment_debug.log', date('Y-m-d H:i:s') . ' - API Error: ' . $e->getMessage() . "\n", FILE_APPEND);
        echo "Error verifying payment with Stripe: " . $e->getMessage();
    } catch (\Exception $e) {
        // Log the error
        file_put_contents('logs/payment_debug.log', date('Y-m-d H:i:s') . ' - General Error: ' . $e->getMessage() . "\n", FILE_APPEND);
        echo "An error occurred while processing your payment: " . $e->getMessage();
    }
} else {
    // Add debugging info to the error message to help troubleshoot
    $message = 'No valid payment information received. ';
    
    // Log complete session and request data
    $debug_data = [
        'session' => $_SESSION,
        'get' => $_GET,
        'post' => $_POST,
        'server' => $_SERVER
    ];
    file_put_contents('logs/payment_debug.log', date('Y-m-d H:i:s') . ' - Debug data: ' . json_encode($debug_data) . "\n", FILE_APPEND);
    
    if (empty($_GET)) {
        $message .= 'No GET parameters were provided. ';
    } else {
        $message .= 'Available GET parameters: ' . implode(', ', array_keys($_GET)) . '. ';
    }
    
    if (isset($_SESSION['last_payment_intent'])) {
        $message .= 'However, there is a payment_intent in session: ' . $_SESSION['last_payment_intent'] . '. ';
        $message .= '<a href="success.php?payment_intent=' . urlencode($_SESSION['last_payment_intent']) . '">Click here to continue</a>';
    }
    
    echo $message;
}