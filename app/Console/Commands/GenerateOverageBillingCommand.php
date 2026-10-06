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

            $rate = (float) ($plan->additional_order_price ?? 0);
            if ($rate <= 0) {
                continue;
            }

            $evaluated++;

            // Check if tenant has order count reported in metadata or we check current_orders
            $currentOrders = (int) ($tenant->current_orders_count ?? 0);

            if ($currentOrders <= 0) {
                // Check if any recent payment metadata has total_orders recorded
                $lastPayment = \App\Models\Payment::where('tenant_id', $tenant->id)->latest('id')->first();
                if ($lastPayment && isset($lastPayment->metadata['total_orders'])) {
                    $currentOrders = (int) $lastPayment->metadata['total_orders'];
                }
            }

            $overage = OverageBillingService::calculateOverage($tenant, $currentOrders);

            if ($overage['is_exceeded'] && $overage['overage_orders'] > 0) {
                $payment = OverageBillingService::syncOverageInvoice($tenant, $currentOrders);
                if ($payment) {
                    $invoiced++;
                    $this->info("  [✓] Tenant #{$tenant->id} ({$tenant->name}): Exceeded by {$overage['overage_orders']} orders. Invoiced ₹{$payment->amount} (Invoice #{$payment->invoice_number})");
                }
            } else {
                $this->line("  [-] Tenant #{$tenant->id} ({$tenant->name}): Within quota ({$currentOrders}/{$overage['included_orders']}).");
            }
        }

        $this->info("Completed. Evaluated: {$evaluated}, Invoiced: {$invoiced}.");
        return 0;
    }
}
