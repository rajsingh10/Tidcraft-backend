<?php

namespace App\Services;

use App\Models\ProvisioningLog;
use App\Models\Tenant;
use App\Jobs\DeployTenantFirebaseFunctionJob;
use App\Jobs\DeployParkMeAppFirebaseFunctionJob;
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
            $serviceAccount = $productFirebase ? (json_decode($productFirebase->service_account_json, true) ?? []) : [];

            if ($productFirebase && $productFirebase->firebase_db_collection) {
                self::logProgress($tenant, 'firebase_import', 'in_progress', 'Importing Firestore data from collection');
                try {
                    $jsonContent = \Illuminate\Support\Facades\Storage::disk('public')->get($productFirebase->firebase_db_collection);
                    if ($jsonContent) {
                        $data = json_decode($jsonContent, true);
                        if ($data) {
                            $importer = new \App\Services\FirestoreImporter($serviceAccount, $tenant->firebaseProject->firebase_database_id);
                            $importer->import($data);
                            self::logProgress($tenant, 'firebase_import', 'success', 'Firestore data imported successfully');
                        }
                    }
                } catch (\Throwable $e) {
                    self::logProgress($tenant, 'firebase_import', 'failed', 'Firestore data import failed', $e->getMessage());
                }
            }

            // Auto-create Firebase Auth Admin User & Firestore admin document (Runs unconditionally for all tenants)
            if (!empty($serviceAccount) && $tenant->firebaseProject) {
                self::logProgress($tenant, 'firebase_auth_admin', 'in_progress', 'Creating Firebase admin user for tenant');
                try {
                    $adminEmail = $tenant->primary_contact_email ?? ($tenant->client ? $tenant->client->email : 'admin@' . ($tenant->domains()->first()?->domain ?? 'tidcraft.com'));
                    $baseName = trim($tenant->name ?: $tenant->business_name);
                    $adminPassword = empty($baseName) ? 'tidcraft' : str_replace(' ', '', strtolower($baseName)) . '-tidcraft';

                    $firebaseAdmin = new \App\Services\FirebaseAdminClient();
                    $firebaseAdmin->createAuthUser($serviceAccount, $adminEmail, $adminPassword);

                    // Create admin record in Firestore (admin_users & users collections)
                    try {
                        $adminId = (string) $tenant->id;
                        $importer = new \App\Services\FirestoreImporter($serviceAccount, $tenant->firebaseProject->firebase_database_id);
                        $importer->import([
                            '__collections__' => [
                                'admin_users' => [
                                    $adminId => [
                                        'id' => $adminId,
                                        'name' => $baseName ?: 'Super Admin',
                                        'email' => strtolower($adminEmail),
                                        'password' => \Illuminate\Support\Facades\Hash::make($adminPassword),
                                        'role' => 'admin',
                                        'role_id' => '1',
                                        'role_name' => 'Super Administrator',
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s'),
                                    ]
                                ],
                                'users' => [
                                    $adminId => [
                                        'id' => $adminId,
                                        'name' => $baseName ?: 'Super Admin',
                                        'email' => strtolower($adminEmail),
                                        'password' => \Illuminate\Support\Facades\Hash::make($adminPassword),
                                        'role' => 'admin',
                                        'role_id' => '1',
                                        'role_name' => 'Super Administrator',
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s'),
                                    ]
                                ]
                            ]
                        ]);
                    } catch (\Throwable $fEx) {
                        \Illuminate\Support\Facades\Log::warning("Could not auto-insert admin user into Firestore: " . $fEx->getMessage());
                    }

                    self::logProgress($tenant, 'firebase_auth_admin', 'success', "Admin user created in Auth & Firestore: Email: {$adminEmail}, Password: {$adminPassword}");
                } catch (\Throwable $e) {
                    self::logProgress($tenant, 'firebase_auth_admin', 'failed', 'Admin user creation failed', $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            self::logProgress($tenant, 'firebase', 'failed', 'Firebase provisioning failed', $e->getMessage());
            $tenant->update(['status' => 'failed']);
            throw $e;
        }

        // Register tenant Firestore database with FoodApp Order Tracking Dispatcher (FoodApp Only)
        $isFoodApp = false;
        $isParkMeApp = false;
        if ($tenant->relationLoaded('product') || $tenant->product) {
            $prodName = strtolower($tenant->product?->slug ?? $tenant->product?->name ?? '');
            $isFoodApp = str_contains($prodName, 'food') || str_contains($prodName, 'eats');
            $isParkMeApp = str_contains($prodName, 'park') || str_contains($prodName, 'parkme');
        }

        if ($isFoodApp && $tenant->firebaseProject && !empty($tenant->firebaseProject->firebase_database_id)) {
            self::logProgress($tenant, 'order_tracking', 'in_progress', 'Registering order tracking dispatcher');
            try {
                $dispatcherUrl = config('services.foodapp.dispatcher_url', env('ORDER_DISPATCHER_URL', 'http://127.0.0.1:5005'));
                $response = \Illuminate\Support\Facades\Http::timeout(5)->post(rtrim($dispatcherUrl, '/') . '/api/tenants/register', [
                    'database_id' => $tenant->firebaseProject->firebase_database_id,
                    'tenant_id'   => $tenant->id,
                ]);
 
                if ($response->successful()) {
                    self::logProgress($tenant, 'order_tracking', 'success', 'Order tracking dispatcher activated for database ' . $tenant->firebaseProject->firebase_database_id);
                } else {
                    self::logProgress($tenant, 'order_tracking', 'failed', 'Dispatcher returned non-200 status: ' . $response->status(), $response->body());
                }
            } catch (\Throwable $e) {
                // Non-blocking so provisioning continues even if dispatcher service is not currently running
                self::logProgress($tenant, 'order_tracking', 'failed', 'Could not contact order dispatcher service', $e->getMessage());
                Log::warning("Order dispatcher registration notice for tenant {$tenant->id}: " . $e->getMessage());
            }
 
            // Solution 2: Deploy dedicated Google Cloud Function if enabled in .env
            $deployCloudFunction = filter_var(
                config('services.foodapp.enable_cloudfunction_deploy', env('ENABLE_CLOUDFUNCTION_DEPLOY', true)),
                FILTER_VALIDATE_BOOLEAN
            );

            if ($deployCloudFunction) {
                self::logProgress($tenant, 'cloud_function', 'in_progress', 'Dispatching Firebase Cloud Function deployment for ' . $tenant->firebaseProject->firebase_database_id);
                try {
                    Log::info("Dispatching Cloud Function deploy job for tenant {$tenant->id}");
                    DeployTenantFirebaseFunctionJob::dispatch(
                        $tenant->firebaseProject->firebase_database_id,
                        $tenant->id
                    );
                    \App\Helpers\QueueRunner::runBackground();
                } catch (\Throwable $e) {
                    self::logProgress($tenant, 'cloud_function', 'failed', 'Could not dispatch Cloud Function deploy job', $e->getMessage());
                    Log::warning("Could not dispatch Cloud Function deploy job for tenant {$tenant->id}: " . $e->getMessage());
                }
            }
        } elseif ($isParkMeApp && $tenant->firebaseProject && !empty($tenant->firebaseProject->firebase_database_id)) {
            // Deploy ParkMeApp dedicated Google Cloud Function if enabled in .env
            $deployCloudFunction = filter_var(
                config('services.parkmeapp.enable_cloudfunction_deploy', env('ENABLE_CLOUDFUNCTION_DEPLOY', true)),
                FILTER_VALIDATE_BOOLEAN
            );

            if ($deployCloudFunction) {
                self::logProgress($tenant, 'cloud_function', 'in_progress', 'Dispatching ParkMeApp Firebase Cloud Function deployment for ' . $tenant->firebaseProject->firebase_database_id);
                try {
                    Log::info("Dispatching ParkMeApp Cloud Function deploy job for tenant {$tenant->id}");
                    DeployParkMeAppFirebaseFunctionJob::dispatch(
                        $tenant->firebaseProject->firebase_database_id,
                        $tenant->id
                    );
                    \App\Helpers\QueueRunner::runBackground();
                } catch (\Throwable $e) {
                    self::logProgress($tenant, 'cloud_function', 'failed', 'Could not dispatch ParkMeApp Cloud Function deploy job', $e->getMessage());
                    Log::warning("Could not dispatch ParkMeApp Cloud Function deploy job for tenant {$tenant->id}: " . $e->getMessage());
                }
            }
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
                $tenantsDirectory = env('TENANTS_DIRECTORY', '/home/devtidcraftcomusr/tenants/');
                if (!file_exists($tenantsDirectory)) {
                    mkdir($tenantsDirectory, 0755, true);
                }
                
                $symlinkPath = rtrim($tenantsDirectory, '/') . '/' . $domain->domain;
                $targetPath = $product->frontend_path;

                if (file_exists($targetPath)) {
                    if (is_link($symlinkPath)) {
                        $current = @readlink($symlinkPath);
                        if ($current !== $targetPath) {
                            @unlink($symlinkPath);
                            @symlink($targetPath, $symlinkPath);
                        }
                    } elseif (!file_exists($symlinkPath)) {
                        @symlink($targetPath, $symlinkPath);
                    }
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

        $tenantsDirectory = env('TENANTS_DIRECTORY', '/home/devtidcraftcomusr/tenants/');
        $symlinkPath = rtrim($tenantsDirectory, '/') . '/' . $domain->domain;
        $expiredPath = env('EXPIRED_PAGE_DIRECTORY', '/home/devtidcraftcomusr/expired-page');

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

        self::toggleFirebaseTenant($tenant, false);
    }

    /**
     * Unblock a tenant by restoring their symlink to the product's frontend path.
     */
    public static function unblockTenant(Tenant $tenant)
    {
        $domain = $tenant->domains()->first();
        $product = \App\Models\Product::find($tenant->product_id);

        if (!$domain || !$product || empty($product->frontend_path)) return;

        $tenantsDirectory = env('TENANTS_DIRECTORY', '/home/devtidcraftcomusr/tenants/');
        $symlinkPath = rtrim($tenantsDirectory, '/') . '/' . $domain->domain;
        $targetPath = $product->frontend_path;

        // Replace symlink
        if (is_link($symlinkPath) || file_exists($symlinkPath)) {
            unlink($symlinkPath);
        }
        
        if (file_exists($targetPath)) {
            symlink($targetPath, $symlinkPath);
        }

        self::toggleFirebaseTenant($tenant, true);
    }

    private static function toggleFirebaseTenant(Tenant $tenant, bool $isEnabled)
    {
        if ($tenant->firebaseProject && $tenant->firebaseProject->firebase_tenant_id) {
            $productFirebase = \App\Models\ProductFirebaseProject::where('product_id', $tenant->product_id)->first();
            if ($productFirebase && !empty($productFirebase->service_account_json)) {
                $serviceAccount = json_decode($productFirebase->service_account_json, true) ?? [];
                if (!empty($serviceAccount)) {
                    $adminClient = new \App\Services\FirebaseAdminClient();
                    $adminClient->toggleIdentityTenant($serviceAccount, $tenant->firebaseProject->firebase_tenant_id, $isEnabled);
                }
            }
        }
    }
}
