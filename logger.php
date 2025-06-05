<?php
require_once 'vendor/autoload.php';

use Omnipay\Common\Exception\OmnipayException;
use Stripe\Exception\ApiErrorException;

class PaymentLogger {
    private $logFile;
    
    public function __construct($logFile = 'payment_logs.log') {
        $this->logFile = __DIR__ . '/logs/' . $logFile;
        
        // Create logs directory if it doesn't exist
        if (!is_dir(__DIR__ . '/logs')) {
            mkdir(__DIR__ . '/logs', 0777, true);
        }
    }
    
    public function log($message, $type = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $type: $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Log payment processing errors
     *
     * @param Throwable $error The error to log
     * @param string $type The type of error (default: ERROR)
     * @return void
     */
    public function logPaymentError(Throwable $error, string $type = 'ERROR'): void {
        $errorMessage = '';
        
        if ($error instanceof OmnipayException) {
            $errorMessage = 'Omnipay Error: ' . $error->getMessage();
        } elseif ($error instanceof ApiErrorException) {
            $errorMessage = 'Stripe Error: ' . $error->getMessage();
        } else {
            $errorMessage = 'Payment Error: ' . $error->getMessage();
        }

        $this->log($errorMessage, $type);
    }
}
