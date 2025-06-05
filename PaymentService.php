<?php
require_once 'stripe_api.php';
require_once 'logger.php';

class PaymentService {
    private $gateway;
    private $logger;
    private $db;
    private $paymentMethod;

    public function __construct($paymentMethod = 'stripe') {
        $this->gateway = $GLOBALS['gateway'];
        $this->logger = new PaymentLogger();
        $this->db = new DB();
        $this->paymentMethod = $paymentMethod;
    }

    public function processPayment($data) {
        try {
            $this->validatePaymentData($data);
            $this->db->beginTransaction();

            // Format amount to decimal
            $amount = number_format($data['amount'], 2, '.', '');            $paymentData = [
                'amount' => $amount,
                'currency' => PAYMENT_CURRENCY,
                'description' => 'Order: ' . $data['order_id'],
                'returnUrl' => RETURN_URL,
                'metadata' => [
                    'order_id' => $data['order_id'],
                    'customer_email' => $data['email'],
                    'user_id' => $_SESSION['user_id'] ?? 0,
                    'customer_name' => $data['name'] ?? ''
                ],
                'payment_method_options' => [
                    'card' => [
                        'request_three_d_secure' => 'automatic'
                    ]
                ]
            ];

            if ($this->paymentMethod === 'stripe') {
                if (isset($data['payment_method_id'])) {
                    $paymentData['paymentMethod'] = $data['payment_method_id'];
                } else if (isset($data['token'])) {
                    $paymentData['token'] = $data['token'];
                } else {
                    throw new Exception('No payment method or token provided');
                }
                $paymentData['confirm'] = true;
            } else {
                // PayPal specific configuration
                $paymentData['cancelUrl'] = RETURN_URL . '?cancel=1';
            }

            $response = $this->gateway->purchase($paymentData)->send();            if ($response->isSuccessful()) {
                $this->handleSuccessfulPayment($response, $data);
                $this->db->commitTransaction();
                
                // Get the payment ID - for Stripe, try to get from the response data directly as a fallback
                $payment_id = $response->getTransactionReference();
                if (empty($payment_id) && $this->paymentMethod === 'stripe') {
                    $responseData = $response->getData();
                    $payment_id = $responseData['id'] ?? null;
                    
                    // Log the entire response data if payment_id is still null
                    if (empty($payment_id)) {
                        $this->logger->log("Warning: Unable to get payment ID from response: " . json_encode($responseData));
                        // Last resort: try to get from the response object directly
                        if (method_exists($response, 'getPaymentIntentReference')) {
                            $payment_id = $response->getPaymentIntentReference();
                        }
                    }
                }
                
                return [
                    'success' => true, 
                    'payment_id' => $payment_id,
                    'message' => 'Payment processed successfully'
                ];
            } elseif ($response->isRedirect()) {
                // For PayPal redirect flow or Stripe 3D Secure
                $this->db->rollbackTransaction();
                return ['redirect' => true, 'response' => $response];
            } else {
                // Handle specific decline cases
                $errorData = $response->getData();
                $errorMessage = $this->getFormattedErrorMessage($errorData);
                
                // Log the detailed error
                $this->logger->logPaymentError(new Exception(json_encode([
                    'error' => $errorMessage,
                    'data' => $errorData
                ])));
                
                throw new Exception($errorMessage);
            }
        } catch (Exception $e) {
            $this->db->rollbackTransaction();
            $this->logger->logPaymentError($e);
            throw new Exception($this->getFormattedErrorMessage($e->getMessage()));
        }
    }

    private function getFormattedErrorMessage($error) {
        if (is_string($error)) {
            // Common decline messages
            if (stripos($error, 'card_declined') !== false) {
                return 'Your card was declined. Please try another card.';
            } elseif (stripos($error, 'insufficient_funds') !== false) {
                return 'Insufficient funds. Please try another card.';
            } elseif (stripos($error, 'expired_card') !== false) {
                return 'Your card has expired. Please try another card.';
            } elseif (stripos($error, 'incorrect_cvc') !== false) {
                return 'The security code (CVC) is incorrect. Please check and try again.';
            } elseif (stripos($error, 'processing_error') !== false) {
                return 'An error occurred while processing your card. Please try again.';
            }
            return 'Payment failed: ' . $error;
        }

        if (is_array($error) && isset($error['error'])) {
            $errorCode = $error['error']['code'] ?? '';
            $errorType = $error['error']['type'] ?? '';
            
            switch ($errorCode) {
                case 'card_declined':
                    $reason = $error['error']['decline_code'] ?? '';
                    switch ($reason) {
                        case 'insufficient_funds':
                            return 'Your card has insufficient funds. Please try another card.';
                        case 'lost_card':
                        case 'stolen_card':
                            return 'This card has been reported lost or stolen. Please use another card.';
                        case 'expired_card':
                            return 'Your card has expired. Please try another card.';
                        default:
                            return 'Your card was declined. Please try another payment method.';
                    }
                case 'incorrect_cvc':
                    return 'The security code (CVC) is incorrect. Please check and try again.';
                case 'processing_error':
                    return 'An error occurred while processing your payment. Please try again.';
                case 'rate_limit':
                    return 'Too many attempts. Please wait a moment and try again.';
                default:
                    return 'Payment failed. Please try again or use a different payment method.';
            }
        }

        return 'An error occurred while processing your payment. Please try again.';
    }

    private function validatePaymentData($data) {
        $requiredFields = ['amount', 'order_id', 'name', 'email'];
        if ($this->paymentMethod === 'stripe') {
            if (!isset($data['payment_method_id']) && !isset($data['token'])) {
                throw new Exception('Either payment_method_id or token is required');
            }
        }

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address");
        }

        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new Exception("Invalid amount");
        }
    }

    private function handleSuccessfulPayment($response, $data) {
        // Insert payment record
        $paymentData = [
            'payment_id' => $response->getTransactionReference(),
            'order_id' => $data['order_id'],
            'user_id' => $_SESSION['user_id'],
            'payer_name' => $data['name'],
            'payer_email' => $data['email'],
            'amount' => $data['amount'],
            'currency' => PAYMENT_CURRENCY,
            'payment_status' => 'completed',
            'created' => date('Y-m-d H:i:s')
        ];

        $this->db->insert_payment_details($paymentData);

        // Update order status
        $this->db->updateOrderStatus($data['order_id'], 'completed');
        
        $this->logger->log("Payment processed successfully: " . $paymentData['payment_id']);
    }

    public function handleWebhook($payload, $sigHeader) {
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, 
                $sigHeader, 
                STRIPE_WEBHOOK_SECRET
            );

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handleWebhookSuccess($event->data->object);
                    break;
                case 'payment_intent.payment_failed':
                    $this->handleWebhookFailure($event->data->object);
                    break;
                default:
                    $this->logger->log("Unhandled webhook event: " . $event->type);
                    break;
            }
            return true;
        } catch (\UnexpectedValueException $e) {
            $this->logger->logPaymentError(new Exception("Invalid payload: " . $e->getMessage(), 0, $e));
            return false;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $this->logger->logPaymentError(new Exception("Invalid signature: " . $e->getMessage(), 0, $e));
            return false;
        } catch (Exception $e) {
            $this->logger->logPaymentError($e);
            return false;
        }
    }

    private function handleWebhookSuccess($paymentIntent) {
        $this->db->updatePaymentStatus([
            'payment_id' => $paymentIntent->id,
            'payment_status' => 'completed',
            'created' => date('Y-m-d H:i:s')
        ]);

        if (!empty($paymentIntent->metadata->order_id)) {
            $this->db->updateOrderStatus($paymentIntent->metadata->order_id, 'completed');
        }
    }

    private function handleWebhookFailure($paymentIntent) {
        $this->db->updatePaymentStatus([
            'payment_id' => $paymentIntent->id,
            'payment_status' => 'failed',
            'created' => date('Y-m-d H:i:s')
        ]);

        if (!empty($paymentIntent->metadata->order_id)) {
            $this->db->updateOrderStatus($paymentIntent->metadata->order_id, 'failed');
        }
    }
}
