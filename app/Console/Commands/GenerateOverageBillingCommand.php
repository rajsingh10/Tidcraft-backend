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
                if ($currentOrders <= 0) {
                    $lastPayment = \App\Models\Payment::where('tenant_id', $tenant->id)->where('type', 'overage_orders')->latest('id')->first() 
                        ?? \App\Models\Payment::where('tenant_id', $tenant->id)->latest('id')->first();
                    if ($lastPayment && isset($lastPayment->metadata['total_orders'])) {
                        $currentOrders = (int) $lastPayment->metadata['total_orders'];
                    }
                }

                $overage = OverageBillingService::calculateOverage($tenant, $currentOrders);
                if ($overage['is_exceeded'] && $overage['overage_orders'] > 0) {
                    $payment = OverageBillingService::syncOverageInvoice($tenant, $currentOrders);
                    if ($payment) {
                        $invoiced++;
                        $this->info("  [✓] Tenant #{$tenant->id} ({$tenant->name}): Exceeded by {$overage['overage_orders']} orders. Invoiced ₹{$payment->amount}");
                    }
                } else {
                    $this->line("  [-] Tenant #{$tenant->id} ({$tenant->name}): Within order quota ({$currentOrders}/{$overage['included_orders']}).");
                }
            }

            // --- 2. Evaluate Bookings Overage ---
            $bookingRate = (float) ($plan->additional_booking_price ?? 0);
            if ($bookingRate > 0) {
                $currentBookings = (int) ($tenant->current_bookings_count ?? 0);
                if ($currentBookings <= 0) {
                    $lastPayment = \App\Models\Payment::where('tenant_id', $tenant->id)->where('type', 'overage_bookings')->latest('id')->first()
                        ?? \App\Models\Payment::where('tenant_id', $tenant->id)->latest('id')->first();
                    if ($lastPayment && isset($lastPayment->metadata['total_bookings'])) {
                        $currentBookings = (int) $lastPayment->metadata['total_bookings'];
                    }
                }

                $bookingOverage = OverageBillingService::calculateBookingOverage($tenant, $currentBookings);
                if ($bookingOverage['is_exceeded'] && $bookingOverage['overage_bookings'] > 0) {
                    $payment = OverageBillingService::syncBookingOverageInvoice($tenant, $currentBookings);
                    if ($payment) {
                        $invoiced++;
                        $this->info("  [✓] Tenant #{$tenant->id} ({$tenant->name}): Exceeded by {$bookingOverage['overage_bookings']} bookings. Invoiced ₹{$payment->amount}");
                    }
                } else {
                    $this->line("  [-] Tenant #{$tenant->id} ({$tenant->name}): Within booking quota ({$currentBookings}/{$bookingOverage['included_bookings']}).");
                }
            }
        }

        $this->info("Completed. Evaluated: {$evaluated}, Invoiced: {$invoiced}.");
        return 0;
    }
}
