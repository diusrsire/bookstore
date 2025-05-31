<?php

require_once 'config.php';

if (isset($_POST['order now'])) {
    try{
        $response = $gateway->purchase(array(
            'grand_total'=> $_POST['grand_total'],
            'currency'=> PAYPAL_CURRENCY,
            'returnUrl'=> PAYPAL_RETURN_URL,
            'cancelUrl'=> PAYPAL_CANCEL_URL,
        ))->send();

        if ($response->isRedirect()) {
            //forwarding the customer to paypal
            $response->redirect();
        }else{
            //if not successful
            echo $response->getMessage();
        }
    }catch(Exception $e){
        echo $e->getMessage();
    }
}