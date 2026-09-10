<?php

namespace App\Services;

use App\Models\ProvisioningLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class TenantProvisionService
{
    /**
     * Run the full provisioning flow for a given tenant.
     */
    public static function provision(Tenant $tenant)
    {
        $tenant->loadMissing(['database', 'firebaseProject', 'domains', 'subscriptions']);

        self::logProgress($tenant, 'database', 'in_progress', 'Creating database ' . $tenant->provisionedDatabaseName());
        try {
            TenantDatabaseManager::createDatabase($tenant);
            $tenant->load('database');
            self::logProgress($tenant, 'database', 'success', 'Database ' . $tenant->database->database_name . ' created');
        } catch (\Exception $e) {
            self::logProgress($tenant, 'database', 'failed', 'Database provisioning failed', $e->getMessage());
            $tenant->update(['status' => 'failed']);
            throw $e;
        }

        self::logProgress($tenant, 'firebase', 'in_progress', 'Connecting product Firebase project');
        try {
            FirebaseProvisionService::provisionFirebase($tenant);
            $tenant->load('firebaseProject');
            self::logProgress(
                $tenant,
                'firebase',
                'success',
                'Created Firestore database ' . $tenant->firebaseProject->firebase_database_id . ' in project ' . $tenant->firebaseProject->firebase_project_id
            );
        } catch (\Exception $e) {
            self::logProgress($tenant, 'firebase', 'failed', 'Firebase provisioning failed', $e->getMessage());
            $tenant->update(['status' => 'failed']);
            throw $e;
        }

        self::logProgress($tenant, 'migrations', 'in_progress', 'Running tenant migrations');
        try {
            TenantDatabaseManager::migrate($tenant);
            self::logProgress($tenant, 'migrations', 'success', 'Tenant migrations completed');
        } catch (\Exception $e) {
            self::logProgress($tenant, 'migrations', 'failed', 'Tenant migrations failed', $e->getMessage());
            $tenant->database?->update(['status' => 'failed']);
            $tenant->update(['status' => 'failed']);
            throw $e;
        }

        self::logProgress($tenant, 'seed', 'in_progress', 'Seeding tenant database');
        try {
            TenantDatabaseManager::seed($tenant);
            $tenant->database?->update(['status' => 'ready']);
            self::logProgress($tenant, 'seed', 'success', 'Tenant database seeded');
        } catch (\Exception $e) {
            self::logProgress($tenant, 'seed', 'failed', 'Tenant seeding failed', $e->getMessage());
            $tenant->database?->update(['status' => 'failed']);
            $tenant->update(['status' => 'failed']);
            throw $e;
        }

        self::logProgress($tenant, 'domain', 'in_progress', 'Activating domain');
        try {
            $domain = $tenant->domains()->first();
            if ($domain && $domain->status !== 'active') {
                $domain->update(['status' => 'active']);
            }
            self::logProgress($tenant, 'domain', 'success', 'Domain activated');
        } catch (\Exception $e) {
            self::logProgress($tenant, 'domain', 'failed', 'Domain provisioning failed', $e->getMessage());
            $tenant->update(['status' => 'failed']);
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

    private static function logProgress(Tenant $tenant, $step, $status, $message, $error = null)
    {
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
