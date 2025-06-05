// charge.php
<?php
require_once "stripe_api.php";
require_once "logger.php";
require_once "PaymentService.php";
require_once "debug_log.php";

debug_log("Starting charge_stripe.php");

$paymentService = new PaymentService();
$logger = new PaymentLogger();

try {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        debug_log("CSRF token validation failed");
        throw new Exception('Invalid CSRF token');
    }

    // Validate session and required fields
    if (!isset($_SESSION['user_id'])) {
        debug_log("User not logged in");
        throw new Exception('User not logged in');
    }

    if (!isset($_POST['payment_method_id']) || empty($_POST['payment_method_id'])) {
        debug_log("No payment method received");
        throw new Exception('No payment method received');
    }

    if (!isset($_POST['amount']) || !is_numeric($_POST['amount'])) {
        debug_log("Invalid amount", $_POST['amount'] ?? 'not set');
        throw new Exception('Invalid amount');
    }

    if (!isset($_SESSION['pending_order'])) {
        debug_log("No pending order found");
        throw new Exception('No pending order found');
    }

    $pending_order = $_SESSION['pending_order'];
    debug_log("Pending order data", $pending_order);
    
    // Verify amount matches the order
    if (floatval($_POST['amount']) !== floatval($pending_order['amount'])) {
        debug_log("Amount mismatch", [
            'posted_amount' => floatval($_POST['amount']),
            'order_amount' => floatval($pending_order['amount'])
        ]);
        throw new Exception('Amount mismatch');
    }

    // Process the payment
    debug_log("About to process payment");
    $result = $paymentService->processPayment([
        'amount' => $_POST['amount'],
        'payment_method_id' => $_POST['payment_method_id'],
        'order_id' => $pending_order['order_id'],
        'name' => $pending_order['customer']['name'],
        'email' => $pending_order['customer']['email']
    ]);
    debug_log("Payment processing result", $result);

    if (isset($result['redirect'])) {
        debug_log("Redirecting for 3D Secure or other authentication");
        $_SESSION['amount'] = $_POST['amount'];
        $result['response']->redirect();
        exit;
    }    if ($result['success']) {
        $payment_id = $result['payment_id'];
        
        // Check if we have a valid payment_id
        if (empty($payment_id)) {
            debug_log("Warning: Payment successful but payment_id is empty. Redirecting to confirmation page.");
            // If payment_id is empty, we need to redirect to the confirmation page
            // which will handle 3D Secure authentication and properly retrieve the payment intent
            header('Location: confirm_stripe.php');
            exit;
        }
        
        $_SESSION['payment_id'] = $payment_id;
        debug_log("Payment successful, redirecting to success page", ['payment_id' => $payment_id]);
        
        // Store payment ID in session for backup retrieval
        $_SESSION['last_payment_intent'] = $payment_id;
        
        // Don't clear pending_order and amount yet, as we might need them in confirm_stripe.php
        unset($_SESSION['csrf_token']);
        
        // Make sure we're passing the payment_intent parameter correctly
        $redirect_url = 'success.php?payment_intent=' . urlencode($payment_id);
        debug_log("Redirecting to: " . $redirect_url);
        
        header('Location: ' . $redirect_url);
        exit;
    }

} catch (Exception $e) {
    debug_log("Payment error", $e->getMessage());
    $logger->logPaymentError($e);
    $_SESSION['payment_error'] = $e->getMessage();
    header('Location: stripe_form.php');
    exit;
}