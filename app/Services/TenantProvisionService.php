<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\ProvisioningLog;
use Illuminate\Support\Facades\Log;

class TenantProvisionService
{
    /**
     * Run the full provisioning flow for a given tenant.
     * 
     * @param Tenant $tenant
     * @return void
     */
    public static function provision(Tenant $tenant)
    {
        self::logProgress($tenant, 'database', 'in_progress', 'Starting database provisioning');
        try {
            TenantDatabaseManager::provisionDatabase($tenant);
            self::logProgress($tenant, 'database', 'success', 'Database provisioned and migrated successfully');
        } catch (\Exception $e) {
            self::logProgress($tenant, 'database', 'failed', 'Database provisioning failed', $e->getMessage());
            throw $e;
        }

        self::logProgress($tenant, 'firebase', 'in_progress', 'Starting Firebase provisioning');
        try {
            FirebaseProvisionService::provisionFirebase($tenant);
            self::logProgress($tenant, 'firebase', 'success', 'Firebase provisioned successfully');
        } catch (\Exception $e) {
            self::logProgress($tenant, 'firebase', 'failed', 'Firebase provisioning failed', $e->getMessage());
            throw $e;
        }

        self::logProgress($tenant, 'domain', 'in_progress', 'Starting Domain provisioning');
        try {
            $domain = $tenant->domains()->firstOrCreate([
                'tenant_id' => $tenant->id,
                'client_id' => $tenant->client_id,
                'product_id' => $tenant->product_id,
                'domain' => $tenant->tenant_key . '.' . env('APP_DOMAIN', 'yoursaas.com'),
            ], [
                'type' => 'subdomain',
                'status' => 'pending'
            ]);

            if ($domain->status !== 'active') {
                $domain->update(['status' => 'active']);
            }
            self::logProgress($tenant, 'domain', 'success', 'Domain provisioned successfully');
        } catch (\Exception $e) {
            self::logProgress($tenant, 'domain', 'failed', 'Domain provisioning failed', $e->getMessage());
            throw $e;
        }

        self::logProgress($tenant, 'activation', 'in_progress', 'Activating tenant');
        try {
            $tenant->update(['status' => 'active']);
            $subscription = $tenant->subscriptions()->first();
            if ($subscription) {
                $subscription->update(['status' => 'active']);
            }
            self::logProgress($tenant, 'activation', 'success', 'Tenant activated successfully');
        } catch (\Exception $e) {
            self::logProgress($tenant, 'activation', 'failed', 'Tenant activation failed', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper to log provisioning steps.
     */
    private static function logProgress(Tenant $tenant, $step, $status, $message, $error = null)
    {
        // If it's an end status, try to find the in_progress log to set completed_at properly,
        // or just create a new record for simplicity as an append-only log log.
        ProvisioningLog::create([
            'tenant_id' => $tenant->id,
            'step' => $step,
            'status' => $status,
            'message' => $message,
            'error' => $error,
            'started_at' => $status === 'in_progress' ? now() : null,
            'completed_at' => in_array($status, ['success', 'failed']) ? now() : null,
        ]);

        if ($status === 'failed') {
            Log::error("Provisioning failed for tenant {$tenant->id} at step {$step}: {$error}");
        }
    }
}
