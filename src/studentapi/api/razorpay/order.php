<?php

require('config.php');
require('razorpay-php/Razorpay.php');
session_start();

// Create the Razorpay Order

use Razorpay\Api\Api;
$api = new Api($keyId, $keySecret);
if(isset($_POST) && isset($_POST['amount']))
{
//
// We create an razorpay order using orders api
// Docs: https://docs.razorpay.com/docs/orders
//
$orderData = [
    'receipt'         => $_POST['receipt'],
    'amount'          => $_POST['amount'], // 2000 rupees in paise
    'currency'        => $_POST['currency'],
    'payment_capture' => 1 // auto capture
];
$result = array();
$result['status']='success';
$razorpayOrder = $api->order->create($orderData);
$razorpayOrderId = $razorpayOrder['id'];
$result['orderId']=$razorpayOrderId;
header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
}
?>