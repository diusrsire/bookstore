<?php
// Simple utility function to help debug the payment flow
function debug_log($message, $data = null) {
    $log_file = 'logs/payment_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    
    $log_message = "[$timestamp] $message";
    
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $log_message .= " - " . json_encode($data, JSON_PRETTY_PRINT);
        } else {
            $log_message .= " - $data";
        }
    }
    
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}
?>
