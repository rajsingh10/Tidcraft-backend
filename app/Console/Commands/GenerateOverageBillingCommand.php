<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Services\OverageBillingService;

class GenerateOverageBillingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:generate-overage-billing {--tenant= : Specific Tenant ID or UUID} {--all : Process all active tenants with plans}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate tenant orders against included quota and generate/update post-paid overage invoices for additional orders.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantArg = $this->option('tenant');
        $all = $this->option('all');

        $query = Tenant::with(['plan', 'subscriptions', 'product']);

        if ($tenantArg) {
            $query->where(function($q) use ($tenantArg) {
                $q->where('id', $tenantArg)->orWhere('uuid', $tenantArg)->orWhere('tenant_key', $tenantArg);
            });
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn("No matching tenants found.");
            return 0;
        }

        $this->info("Found {$tenants->count()} tenant(s). Processing overage billing evaluation...");

        $evaluated = 0;
        $invoiced = 0;

        foreach ($tenants as $tenant) {
            $plan = $tenant->plan;
            if (!$plan) {
                continue;
            }

            $evaluated++;

            // --- 1. Evaluate Orders Overage ---
            $orderRate = (float) ($plan->additional_order_price ?? 0);
            if ($orderRate > 0) {
                $liveCount = $this->fetchLiveCloudCount($tenant, 'orders');
                if ($liveCount > 0) {
                    $this->line("    Live cloud orders for Tenant #{$tenant->id}: {$liveCount} orders detected.");
                    $currentOrders = $liveCount;
                    $tenant->update(['current_orders_count' => $currentOrders]);
                } else {
                    $currentOrders = (int) ($tenant->current_orders_count ?? 0);
                    $lastPaidBill = \App\Models\TenantOverageBill::where('tenant_id', $tenant->id)
                        ->where('bill_type', 'orders')
                        ->whereIn('status', ['paid', 'success'])
                        ->latest('id')
                        ->first();
                    $lastPaidUsage = $lastPaidBill ? (int) $lastPaidBill->total_usage : 0;
                    if ($lastPaidUsage > $currentOrders) {
                        $currentOrders = $lastPaidUsage;
                    }
                }

                $overage = OverageBillingService::calculateOverage($tenant, $currentOrders);
                if ($overage['is_exceeded'] && $overage['overage_orders'] > 0) {
                    $bill = OverageBillingService::syncOverageInvoice($tenant, $currentOrders);
                    if ($bill) {
                        $invoiced++;
                        $this->info("  [✓] Tenant #{$tenant->id} ({$tenant->name}): Exceeded by {$overage['overage_orders']} orders. Invoiced ₹{$bill->total_amount}");
                    }
                } else {
                    $this->line("  [-] Tenant #{$tenant->id} ({$tenant->name}): Within order quota ({$currentOrders}/{$overage['included_orders']}).");
                }
            }

            // --- 2. Evaluate Bookings Overage ---
            $bookingRate = (float) ($plan->additional_booking_price ?? 0);
            if ($bookingRate > 0) {
                $liveCount = $this->fetchLiveCloudCount($tenant, 'booked_parking_order');
                if ($liveCount > 0) {
                    $this->line("    Live cloud bookings for Tenant #{$tenant->id}: {$liveCount} bookings detected.");
                    $currentBookings = $liveCount;
                    $tenant->update(['current_bookings_count' => $currentBookings]);
                } else {
                    $currentBookings = (int) ($tenant->current_bookings_count ?? 0);
                    $lastPaidBill = \App\Models\TenantOverageBill::where('tenant_id', $tenant->id)
                        ->where('bill_type', 'bookings')
                        ->whereIn('status', ['paid', 'success'])
                        ->latest('id')
                        ->first();
                    $lastPaidUsage = $lastPaidBill ? (int) $lastPaidBill->total_usage : 0;
                    if ($lastPaidUsage > $currentBookings) {
                        $currentBookings = $lastPaidUsage;
                    }
                }

                $bookingOverage = OverageBillingService::calculateBookingOverage($tenant, $currentBookings);
                if ($bookingOverage['is_exceeded'] && $bookingOverage['overage_bookings'] > 0) {
                    $bill = OverageBillingService::syncBookingOverageInvoice($tenant, $currentBookings);
                    if ($bill) {
                        $invoiced++;
                        $this->info("  [✓] Tenant #{$tenant->id} ({$tenant->name}): Exceeded by {$bookingOverage['overage_bookings']} bookings. Invoiced ₹{$bill->total_amount}");
                    }
                } else {
                    $this->line("  [-] Tenant #{$tenant->id} ({$tenant->name}): Within booking quota ({$currentBookings}/{$bookingOverage['included_bookings']}).");
                }
            }
        }

        $this->info("Completed. Evaluated: {$evaluated}, Invoiced: {$invoiced}.");
        return 0;
    }

    /**
     * Fetch live count directly from Firebase REST API to ensure billing accuracy.
     */
    private function fetchLiveCloudCount(\App\Models\Tenant $tenant, string $metricType): int
    {
        try {
            $productFirebase = \App\Models\ProductFirebaseProject::where('product_id', $tenant->product_id)->first();
            if (!$productFirebase || !$productFirebase->service_account_json) {
                return 0;
            }

            $serviceAccount = $this->decodeServiceAccount($productFirebase->service_account_json);
            if (!$serviceAccount) {
                return 0;
            }

            // Determine candidate collections based on metric and product
            $collections = [];
            if ($metricType === 'orders' || $metricType === 'restaurant_orders' || $tenant->product_id == 1) {
                $collections = ['restaurant_orders', 'orders', 'restaurant_order'];
            } elseif ($metricType === 'booked_parking_order' || $metricType === 'bookings' || $tenant->product_id == 2) {
                $collections = ['booked_parking_order', 'bookings', 'orders', 'booking'];
            } else {
                $collections = [$metricType];
            }

            // Collect candidate database IDs to check in order of priority
            $databaseCandidates = [];
            $firebaseConfig = $tenant->firebaseProject;
            if ($firebaseConfig && !empty($firebaseConfig->firebase_database_id)) {
                $databaseCandidates[] = $firebaseConfig->firebase_database_id;
            }
            if (method_exists($tenant, 'firestoreDatabaseId')) {
                try {
                    $databaseCandidates[] = $tenant->firestoreDatabaseId();
                } catch (\Throwable $e) {}
            }

            $sub = method_exists($tenant, 'subdomainPrefix') ? $tenant->subdomainPrefix() : ($tenant->tenant_key ?? '');
            $cleanSub = preg_replace('/[^a-z0-9-]/', '-', strtolower((string) $sub));
            $cleanSubNoP = preg_replace('/-p\d+$/', '', $cleanSub);

            $rawKey = preg_replace('/[^a-z0-9-]/', '-', strtolower((string) ($tenant->tenant_key ?? '')));
            $rawKeyNoP = preg_replace('/-p\d+$/', '', $rawKey);

            if ($tenant->product_id == 1) {
                $databaseCandidates[] = 'tidcraft-tideats-' . $cleanSubNoP;
                $databaseCandidates[] = 'tidcraft-tideats-' . $cleanSub;
                $databaseCandidates[] = 'tidcraft-' . $cleanSubNoP;
                $databaseCandidates[] = 'tidcraft-' . $cleanSub;
                $databaseCandidates[] = 'tidcraft-tideats-' . $rawKeyNoP;
                $databaseCandidates[] = 'tidcraft-tideats-' . $rawKey;
                $databaseCandidates[] = 'tidcraft-' . $rawKeyNoP;
                $databaseCandidates[] = 'tidcraft-' . $rawKey;
            } elseif ($tenant->product_id == 2) {
                $databaseCandidates[] = 'tidcraft-tidpark-' . $cleanSubNoP;
                $databaseCandidates[] = 'tidcraft-tidpark-' . $cleanSub;
                $databaseCandidates[] = 'tidcraft-' . $cleanSubNoP;
                $databaseCandidates[] = 'tidcraft-' . $cleanSub;
                $databaseCandidates[] = 'tidcraft-tidpark-' . $rawKeyNoP;
                $databaseCandidates[] = 'tidcraft-tidpark-' . $rawKey;
                $databaseCandidates[] = 'tidcraft-' . $rawKeyNoP;
                $databaseCandidates[] = 'tidcraft-' . $rawKey;
            } else {
                $databaseCandidates[] = 'tidcraft-' . $cleanSubNoP;
                $databaseCandidates[] = 'tidcraft-' . $cleanSub;
            }

            $databaseCandidates[] = '(default)';
            $databaseCandidates = array_values(array_unique(array_filter($databaseCandidates)));

            if (!class_exists(\App\Services\FirebaseAdminClient::class)) {
                \Illuminate\Support\Facades\Log::warning("FirebaseAdminClient class not found on system. Skipping live cloud count for Tenant #{$tenant->id}.");
                return 0;
            }

            $adminClient = new \App\Services\FirebaseAdminClient();

            $maxFound = 0;
            foreach ($databaseCandidates as $databaseId) {
                foreach ($collections as $collectionName) {
                    $filter = null;
                    if ($collectionName === 'restaurant_orders' || ($tenant->product_id == 1 && in_array($collectionName, ['orders', 'restaurant_orders', 'restaurant_order']))) {
                        $filter = [
                            'fieldFilter' => [
                                'field' => ['fieldPath' => 'isPosOrder'],
                                'op' => 'EQUAL',
                                'value' => ['booleanValue' => false]
                            ]
                        ];
                    }
                    $count = $adminClient->getCollectionCount($serviceAccount, $databaseId, $collectionName, $filter);
                    if ($count > $maxFound) {
                        $maxFound = $count;
                        \Illuminate\Support\Facades\Log::info("Found live cloud count for Tenant #{$tenant->id} [DB: {$databaseId}, Collection: {$collectionName}]: {$count}");
                    }
                }
                if ($maxFound > 0) {
                    return $maxFound;
                }
            }

            return $maxFound;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to fetch live cloud count for tenant {$tenant->id}: " . $e->getMessage());
        }

        return 0;
    }

    /**
     * Decode and validate Firebase service account JSON without external service dependencies.
     */
    private function decodeServiceAccount($json): ?array
    {
        if (empty($json)) {
            return null;
        }

        if (is_array($json)) {
            return (!empty($json['private_key']) && !empty($json['client_email'])) ? $json : null;
        }

        if (is_string($json)) {
            $decoded = json_decode($json, true);
            if (is_array($decoded) && !empty($decoded['private_key']) && !empty($decoded['client_email'])) {
                return $decoded;
            }
        }

        return null;
    }
}
