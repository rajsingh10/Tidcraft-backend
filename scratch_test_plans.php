<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (App\Models\Plan::all() as $p) {
    echo "ID: {$p->id} | Name: {$p->name} | max_users: {$p->max_users} | max_orders: {$p->max_orders} | unlimited: {$p->has_unlimited_users_listings}\n";
}

echo "\n--- Tenants ---\n";
foreach (App\Models\Tenant::with('plan')->get() as $t) {
    echo "Tenant ID: {$t->id} | Name: {$t->name} | Key: {$t->tenant_key} | Plan ID: {$t->plan_id} | Plan Name: " . ($t->plan->name ?? 'None') . " | getMaxUsers(): {$t->getMaxUsers()} | getMaxOrders(): {$t->getMaxOrders()}\n";
}
