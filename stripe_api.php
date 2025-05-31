<?php
session_start();
 
require_once "vendor/autoload.php";
require_once "class-db.php";
 
use Omnipay\Omnipay;
 
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51RURKuFKhPQIqm6g8Mze7MUIyr8b7LoC828K6DyM4PCQ3ksj71JG0I6k2zaR9qZvfZr8MFEnWZbQi5QBycd1Ldm8001wJzG792');
define('STRIPE_SECRET_KEY', 'sk_test_51RURKuFKhPQIqm6g3BEA9RhRqvKqxwYqWfm9ckTo48tVtxDjS4qtiElxABioyInu0iTXH5VRZs6p3eziK51eFt2R00rvAGJklZ');
define('RETURN_URL', 'DOMAIN_URL/confirm.php');
define('PAYMENT_CURRENCY', 'USD');
 
$gateway = Omnipay::create('Stripe\PaymentIntents');
$gateway->setApiKey(STRIPE_SECRET_KEY);