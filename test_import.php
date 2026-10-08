<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$json = \Illuminate\Support\Facades\Storage::disk('public')->get('products/db_collections/FnkB0yiSC1N8NanDDUlQPMtpJ3vUNB8NEa9ULLOk.json'); 
$data = json_decode($json, true); 
echo "JSON Error: " . json_last_error_msg() . "\n";
echo 'Data is array? ' . (is_array($data) ? 'Yes' : 'No') . "\n";
if (is_array($data)) {
    echo 'Data keys: ' . implode(', ', array_keys($data)) . "\n";
}
