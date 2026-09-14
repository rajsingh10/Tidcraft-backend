<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try { 
    $api = new \Razorpay\Api\Api('rzp_test_TZ4Ie5XEVhPic8', '6wXrqr6BuK8bWiEuLOFVhqq5'); 
    $response = $api->paymentLink->create(['amount' => 5000, 'currency' => 'INR', 'description' => 'Test']); 
    echo json_encode($response->toArray()); 
} catch (\Throwable $e) { 
    echo 'Error: ' . $e->getMessage(); 
}
