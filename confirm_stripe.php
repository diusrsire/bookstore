// confirm.php
<?php
require_once "stripe_api.php";
require_once "logger.php";
require_once "debug_log.php";

debug_log("Starting confirm_stripe.php", ['GET' => $_GET, 'SESSION' => array_keys($_SESSION)]);

try {
    $logger = new PaymentLogger();
    $db = new DB();
    
    if (!isset($_GET['payment_intent'])) {
        debug_log("No payment intent ID provided");
        throw new Exception('No payment intent ID provided');
    }

    if (!isset($_SESSION['amount']) || !isset($_SESSION['pending_order'])) {
        debug_log("Invalid session state", [
            'amount_set' => isset($_SESSION['amount']),
            'pending_order_set' => isset($_SESSION['pending_order'])
        ]);
        throw new Exception('Invalid session state');
    }

    debug_log("Confirming payment intent", $_GET['payment_intent']);
    $response = $gateway->confirm([
        'paymentIntentReference' => $_GET['payment_intent'],
        'returnUrl' => RETURN_URL,
    ])->send();
 
    if ($response->isSuccessful()) {
        $db->beginTransaction();
        debug_log("Payment intent confirmation successful");
        
        try {
            // Capture the payment
            debug_log("Capturing payment", [
                'amount' => $_SESSION['amount'],
                'currency' => PAYMENT_CURRENCY,
                'payment_intent' => $_GET['payment_intent']
            ]);
            $capture_response = $gateway->capture([
                'amount' => $_SESSION['amount'],
                'currency' => PAYMENT_CURRENCY,
                'paymentIntentReference' => $_GET['payment_intent'],
            ])->send();

            if (!$capture_response->isSuccessful()) {
                debug_log("Payment capture failed", $capture_response->getMessage());
                throw new Exception('Payment capture failed: ' . $capture_response->getMessage());
            }

            $payment_data = $capture_response->getData();
            $pending_order = $_SESSION['pending_order'];
            debug_log("Payment captured successfully", ['payment_data' => $payment_data]);            // Insert payment details
            debug_log("Inserting payment details");
            $db->insert_payment_details([
                "payment_id" => $payment_data['id'],
                "order_id" => $pending_order['order_id'],
                "user_id" => $_SESSION['user_id'],
                "payer_name" => $pending_order['customer']['name'],
                "payer_email" => $pending_order['customer']['email'],
                "amount" => $_SESSION['amount'],
                'currency' => PAYMENT_CURRENCY,
                'payment_status' => 'completed',
                'created' => date('Y-m-d H:i:s')
            ]);
            
            // Insert record into orders table
            debug_log("Inserting order record");
            $user_id = $_SESSION['user_id'];
            $name = $pending_order['customer']['name'];
            $email = $pending_order['customer']['email'];
            $address = $pending_order['customer']['address'];
            $total_products = $pending_order['products'];
            $total_price = $_SESSION['amount'];
            $placed_on = date('Y-m-d');
            $method = 'stripe';
            $number = ''; // Phone number might not be available for Stripe payments
            
            // Check if this order already exists in the orders table
            $order_exists_query = "SELECT * FROM `orders` WHERE order_id = ? OR (user_id = ? AND total_products = ? AND total_price = ? AND DATE(placed_on) = ?)";
            $stmt = $conn->prepare($order_exists_query);
            $placed_on_date = date('Y-m-d');
            $stmt->bind_param("sisss", $pending_order['order_id'], $user_id, $total_products, $total_price, $placed_on_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                // Order doesn't exist, insert it
                $order_insert_query = "INSERT INTO `orders`(user_id, name, number, email, method, address, total_products, total_price, placed_on, payment_status) 
                                     VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($order_insert_query);
                $payment_status = 'completed';
                $stmt->bind_param("issssssdss", $user_id, $name, $number, $email, $method, $address, $total_products, $total_price, $placed_on, $payment_status);
                $stmt->execute();
                debug_log("Order record inserted successfully");
            } else {
                debug_log("Order already exists in the orders table");
            }

            // Update order status
            debug_log("Updating order status");
            $db->updateOrderStatus($pending_order['order_id'], 'completed');

            $db->commitTransaction();
            debug_log("Database transaction committed");
            
            // Store payment ID for success page
            $_SESSION['payment_id'] = $payment_data['id'];
              
            // Clear cart and payment session data
            unset($_SESSION['cart']);
            unset($_SESSION['pending_order']);
            unset($_SESSION['amount']);
            
            $logger->log("Payment completed successfully: " . $payment_data['id']);
            debug_log("Redirecting to success page", ['payment_id' => $payment_data['id']]);
            
            header('Location: success.php?payment_intent=' . urlencode($payment_data['id']));
            exit;
            
        } catch (Exception $e) {
            debug_log("Error in payment capture", $e->getMessage());
            $db->rollbackTransaction();
            throw $e;
        }
    } elseif ($response->isRedirect()) {
        // Handle any required redirect
        debug_log("Payment requires additional redirect");
        $response->redirect();
        exit;
    } else {
        debug_log("Payment confirmation failed", $response->getMessage());
        throw new Exception($response->getMessage());
    }
} catch (Exception $e) {
    debug_log("Error in confirm_stripe.php", $e->getMessage());
    $logger->logPaymentError($e);
    $_SESSION['payment_error'] = $e->getMessage();
    header('Location: stripe_form.php');
    exit;
}
?>