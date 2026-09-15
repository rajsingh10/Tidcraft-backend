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

        // Database creation is disabled because we are using Firebase only
        // self::logProgress($tenant, 'database', 'in_progress', 'Creating database ' . $tenant->provisionedDatabaseName());
        // try {
        //     TenantDatabaseManager::createDatabase($tenant);
        //     $tenant->load('database');
        //     self::logProgress($tenant, 'database', 'success', 'Database ' . $tenant->database->database_name . ' created');
        // } catch (\Exception $e) {
        //     self::logProgress($tenant, 'database', 'failed', 'Database provisioning failed', $e->getMessage());
        //     $tenant->update(['status' => 'failed']);
        //     throw $e;
        // }

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

            // Trigger Firestore Data Import if applicable
            $productFirebase = \App\Models\ProductFirebaseProject::where('product_id', $tenant->product_id)->first();
            if ($productFirebase && $productFirebase->firebase_db_collection) {
                self::logProgress($tenant, 'firebase_import', 'in_progress', 'Importing Firestore data from collection');
                try {
                    $jsonContent = \Illuminate\Support\Facades\Storage::disk('public')->get($productFirebase->firebase_db_collection);
                    if ($jsonContent) {
                        $data = json_decode($jsonContent, true);
                        if ($data) {
                            $serviceAccount = json_decode($productFirebase->service_account_json, true) ?? [];
                            $importer = new \App\Services\FirestoreImporter($serviceAccount, $tenant->firebaseProject->firebase_database_id);
                            $importer->import($data);
                            self::logProgress($tenant, 'firebase_import', 'success', 'Firestore data imported successfully');
                        }
                    }

                    // Auto-create Firebase Auth Admin User
                    self::logProgress($tenant, 'firebase_auth_admin', 'in_progress', 'Creating Firebase admin user for tenant');
                    $adminEmail = $tenant->primary_contact_email ?? ($tenant->client ? $tenant->client->email : 'admin@' . ($tenant->domains()->first()?->domain ?? 'tidcraft.com'));
                    $baseName = trim($tenant->name ?: $tenant->business_name);
                    $adminPassword = empty($baseName) ? 'tidcraft' : str_replace(' ', '', strtolower($baseName)) . '-tidcraft';

                    $firebaseAdmin = new \App\Services\FirebaseAdminClient();
                    $firebaseAdmin->createAuthUser($serviceAccount, $adminEmail, $adminPassword);
                    self::logProgress($tenant, 'firebase_auth_admin', 'success', "Admin user created: Email: {$adminEmail}, Password: {$adminPassword}");
                } catch (\Throwable $e) {
                    self::logProgress($tenant, 'firebase_import', 'failed', 'Firestore data import / user creation failed', $e->getMessage());
                    // We do not throw here to allow other provision steps to continue
                }
            }
        } catch (\Exception $e) {
            self::logProgress($tenant, 'firebase', 'failed', 'Firebase provisioning failed', $e->getMessage());
            $tenant->update(['status' => 'failed']);
            throw $e;
        }

        // Migrations disabled for Firebase
        // self::logProgress($tenant, 'migrations', 'in_progress', 'Running tenant migrations');
        // try {
        //     TenantDatabaseManager::migrate($tenant);
        //     self::logProgress($tenant, 'migrations', 'success', 'Tenant migrations completed');
        // } catch (\Exception $e) {
        //     self::logProgress($tenant, 'migrations', 'failed', 'Tenant migrations failed', $e->getMessage());
        //     $tenant->database?->update(['status' => 'failed']);
        //     $tenant->update(['status' => 'failed']);
        //     throw $e;
        // }

        // Seeding disabled for Firebase
        // self::logProgress($tenant, 'seed', 'in_progress', 'Seeding tenant database');
        // try {
        //     TenantDatabaseManager::seed($tenant);
        //     $tenant->database?->update(['status' => 'ready']);
        //     self::logProgress($tenant, 'seed', 'success', 'Tenant database seeded');
        // } catch (\Exception $e) {
        //     self::logProgress($tenant, 'seed', 'failed', 'Tenant seeding failed', $e->getMessage());
        //     $tenant->database?->update(['status' => 'failed']);
        //     $tenant->update(['status' => 'failed']);
        //     throw $e;
        // }

        self::logProgress($tenant, 'domain', 'in_progress', 'Activating domain');
        try {
            $domain = $tenant->domains()->first();
            if ($domain && $domain->status !== 'active') {
                $domain->update(['status' => 'active']);
            }

            // Create a symlink for the frontend Nginx routing
            $product = \App\Models\Product::find($tenant->product_id);
            if ($product && !empty($product->frontend_path) && $domain) {
                $tenantsDirectory = '/home/devtidcraftcomusr/tenants/';
                if (!file_exists($tenantsDirectory)) {
                    mkdir($tenantsDirectory, 0755, true);
                }
                
                $symlinkPath = rtrim($tenantsDirectory, '/') . '/' . $domain->domain;
                $targetPath = $product->frontend_path;

                if (!file_exists($symlinkPath) && file_exists($targetPath)) {
                    symlink($targetPath, $symlinkPath);
                }
            }

            self::logProgress($tenant, 'domain', 'success', 'Domain activated and symlink created');
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

            // Send email to client
            $adminEmail = $tenant->primary_contact_email ?? ($tenant->client ? $tenant->client->email : 'admin@' . ($tenant->domains()->first()?->domain ?? 'tidcraft.com'));
            $baseName = trim($tenant->name ?: $tenant->business_name);
            $adminPassword = empty($baseName) ? 'tidcraft' : str_replace(' ', '', strtolower($baseName)) . '-tidcraft';
            $domainObj = $tenant->domains()->first();
            $domainUrl = 'https://' . ($domainObj ? $domainObj->domain : 'tidcraft.com');

            try {
                \Illuminate\Support\Facades\Mail::to($adminEmail)->send(new \App\Mail\TenantProvisionedEmail($tenant, $adminEmail, $adminPassword, $domainUrl));
                self::logProgress($tenant, 'email', 'success', 'Provisioned email sent to ' . $adminEmail);
            } catch (\Exception $e) {
                Log::error('Failed to send provisioned email: ' . $e->getMessage());
                self::logProgress($tenant, 'email', 'failed', 'Failed to send provisioned email', $e->getMessage());
            }

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

    /**
     * Block a tenant by pointing their symlink to an expired page.
     */
    public static function blockTenant(Tenant $tenant)
    {
        $domain = $tenant->domains()->first();
        if (!$domain) return;

        $tenantsDirectory = '/home/devtidcraftcomusr/tenants/';
        $symlinkPath = rtrim($tenantsDirectory, '/') . '/' . $domain->domain;
        $expiredPath = '/home/devtidcraftcomusr/expired-page';

        // Ensure the expired page directory exists
        if (!file_exists($expiredPath)) {
            @mkdir($expiredPath, 0755, true);
            $html = '<html><head><title>Subscription Expired</title><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f3f4f6;text-align:center;} .card{background:#fff;padding:2rem;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);max-width:500px;} h1{color:#ef4444;}</style></head><body><div class="card"><h1>Subscription Expired</h1><p>Your access to this application has been temporarily disabled because your subscription is no longer active.</p><p>Please renew your plan to restore access.</p></div></body></html>';
            @file_put_contents($expiredPath . '/index.html', $html);
        }

        // Replace symlink
        if (is_link($symlinkPath) || file_exists($symlinkPath)) {
            unlink($symlinkPath);
        }
        symlink($expiredPath, $symlinkPath);
    }

    /**
     * Unblock a tenant by restoring their symlink to the product's frontend path.
     */
    public static function unblockTenant(Tenant $tenant)
    {
        $domain = $tenant->domains()->first();
        $product = \App\Models\Product::find($tenant->product_id);

        if (!$domain || !$product || empty($product->frontend_path)) return;

        $tenantsDirectory = '/home/devtidcraftcomusr/tenants/';
        $symlinkPath = rtrim($tenantsDirectory, '/') . '/' . $domain->domain;
        $targetPath = $product->frontend_path;

        // Replace symlink
        if (is_link($symlinkPath) || file_exists($symlinkPath)) {
            unlink($symlinkPath);
        }
        
        if (file_exists($targetPath)) {
            symlink($targetPath, $symlinkPath);
        }
    }
}
