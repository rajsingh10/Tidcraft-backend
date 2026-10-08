<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Services\TenantProvisionService;

class SyncTenantPlanSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:sync-plan-settings {--tenant= : Specific Tenant ID or UUID} {--all : Sync for all active tenants with Firestore projects}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize tenant plan entitlements and feature flags to Firestore settings/plan_settings document for mobile apps.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantArg = $this->option('tenant');
        $all = $this->option('all');

        $query = Tenant::with(['plan', 'firebaseProject', 'product']);

        if ($tenantArg) {
            $query->where(function($q) use ($tenantArg) {
                $q->where('id', $tenantArg)->orWhere('uuid', $tenantArg)->orWhere('tenant_key', $tenantArg);
            });
        } elseif (!$all) {
            $this->info("Please specify --tenant=<id|uuid> or --all to sync all tenants.");
            return 0;
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn("No matching tenants found.");
            return 0;
        }

        $this->info("Found {$tenants->count()} tenant(s). Starting sync to Firestore...");

        $successCount = 0;
        $failCount = 0;

        foreach ($tenants as $tenant) {
            if (!$tenant->firebaseProject) {
                $this->line("  [-] Tenant #{$tenant->id} ({$tenant->name}): Skipped (No Firebase project linked)");
                continue;
            }

            $synced = TenantProvisionService::syncPlanSettingsToFirestore($tenant);

            if ($synced) {
                $successCount++;
                $this->info("  [✓] Tenant #{$tenant->id} ({$tenant->name}): Plan [{$tenant->plan?->name}] settings synced successfully.");
            } else {
                $failCount++;
                $this->error("  [✗] Tenant #{$tenant->id} ({$tenant->name}): Failed to sync plan settings.");
            }
        }

        $this->info("Done! Synced: {$successCount}, Failed: {$failCount}.");
        return 0;
    }
}
