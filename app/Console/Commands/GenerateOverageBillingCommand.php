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
        } elseif (!$all) {
            $this->info("Please specify --tenant=<id|uuid> or --all to process all tenants.");
            return 0;
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
                $currentOrders = (int) ($tenant->current_orders_count ?? 0);
                
                $liveCount = $this->fetchLiveCloudCount($tenant, 'orders');
                if ($liveCount > $currentOrders) {
                    $currentOrders = $liveCount;
                    $tenant->update(['current_orders_count' => $currentOrders]);
                }

                if ($currentOrders <= 0) {
                    $lastBill = \App\Models\TenantOverageBill::where('tenant_id', $tenant->id)->where('bill_type', 'orders')->latest('id')->first();
                    if ($lastBill && $lastBill->total_usage > 0) {
                        $currentOrders = (int) $lastBill->total_usage;
                    } else {
                        $lastPayment = \App\Models\Payment::where('tenant_id', $tenant->id)->where('type', 'overage_orders')->latest('id')->first() 
                            ?? \App\Models\Payment::where('tenant_id', $tenant->id)->latest('id')->first();
                        if ($lastPayment && isset($lastPayment->metadata['total_orders'])) {
                            $currentOrders = (int) $lastPayment->metadata['total_orders'];
                        }
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
                $currentBookings = (int) ($tenant->current_bookings_count ?? 0);
                
                $liveCount = $this->fetchLiveCloudCount($tenant, 'booked_parking_order');
                if ($liveCount > $currentBookings) {
                    $currentBookings = $liveCount;
                    $tenant->update(['current_bookings_count' => $currentBookings]);
                }

                if ($currentBookings <= 0) {
                    $lastBill = \App\Models\TenantOverageBill::where('tenant_id', $tenant->id)->where('bill_type', 'bookings')->latest('id')->first();
                    if ($lastBill && $lastBill->total_usage > 0) {
                        $currentBookings = (int) $lastBill->total_usage;
                    } else {
                        $lastPayment = \App\Models\Payment::where('tenant_id', $tenant->id)->where('type', 'overage_bookings')->latest('id')->first()
                            ?? \App\Models\Payment::where('tenant_id', $tenant->id)->latest('id')->first();
                        if ($lastPayment && isset($lastPayment->metadata['total_bookings'])) {
                            $currentBookings = (int) $lastPayment->metadata['total_bookings'];
                        }
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
    private function fetchLiveCloudCount(\App\Models\Tenant $tenant, string $collectionName): int
    {
        try {
            $productFirebase = \App\Models\ProductFirebaseProject::where('product_id', $tenant->product_id)->first();
            if ($productFirebase && $productFirebase->service_account_json) {
                $serviceAccount = \App\Services\FirebaseProvisionService::decodeServiceAccount($productFirebase->service_account_json);
                if ($serviceAccount) {
                    $firebaseConfig = $tenant->firebaseProject;
                    $databaseId = $firebaseConfig ? $firebaseConfig->firebase_database_id : ($tenant->firestoreDatabaseId ?? $tenant->tenant_key);
                    
                    if ($databaseId) {
                        $adminClient = new \App\Services\FirebaseAdminClient();
                        return $adminClient->getCollectionCount($serviceAccount, $databaseId, $collectionName);
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to fetch live cloud count for tenant {$tenant->id}: " . $e->getMessage());
        }
        
        return 0;
    }
}
