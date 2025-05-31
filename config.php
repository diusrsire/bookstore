<?php

require_once __DIR__ . '/vendor/autoload.php';


// require_once "vendor/autoload.php";

use Omnipay\Omnipay;

define("CLIENT_ID","AcB_kvPawBvCulJ-UGwHbxOZgQJAsBkDcYuOV26brDjhk3YcWS2BwI7IQKpbDyB0CJ2s--PL1Jh1mUik");
define("CLIENT_SECRET","EG3lNp_BFdnnGXcRRQowkWTnM_opP6JqDfQ97uOPzGJXmyQbWqaNLBOvLSlGahwDyaCauPyUBoemYtdQ");

define("PAYPAL_RETURN_URL","https://localhost/paypal/success.php");
define("PAYPAL_CANCEL_URL","https://localhost/paypal/cancel.php");
define("PAYPAL_CURRENCY","USD");

$conn = mysqli_connect('localhost',
'root',
'','shop_db') 
or die('connection failed');

$gateway = Omnipay::create('PayPal_Rest');
$gateway->setClientId(CLIENT_ID);
$gateway->setSecret(CLIENT_SECRET);
$gateway->setTestMode(false);//make sure to change this later




?>