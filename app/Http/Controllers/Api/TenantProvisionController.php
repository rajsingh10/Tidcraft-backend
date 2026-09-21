<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Tenant;
use App\Models\Domain;
use App\Models\FirebaseProject;
use App\Services\AuditLogger;

class TenantProvisionController extends Controller
{
    public function store(Request $request)
    {
        // Auto-construct full domain from subdomain_prefix
        if ($request->domain_type === 'subdomain' && $request->has('subdomain_prefix')) {
            $prefix = trim($request->subdomain_prefix, " .");
            $request->merge(['domain' => $prefix . '.tidcraft.com']);
        }

        $validator = Validator::make($request->all(), [
            // Step 1: Client Info
            'client_id' => 'nullable|exists:users,id',
            'client_name' => 'nullable|string|max:255',
            'client_logo' => 'nullable',
            'business_name' => 'required|string|max:255',
            'primary_contact_email' => 'required|email|max:255',
            'phone_number' => 'nullable|string|max:20',
            'industry' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            
            // Step 2 & 3: Product and Plan
            'product_id' => 'required|exists:products,id',
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'nullable|in:monthly,yearly',

            // Step 4: Domain Setup
            'domain_type' => 'nullable|in:subdomain,shared,custom',
            'domain' => 'nullable|string|unique:domains,domain',

            // Add-ons
            'add_ons' => 'nullable|array',
            'add_ons.*' => 'exists:add_ons,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $productFirebase = \App\Models\ProductFirebaseProject::where('product_id', $request->product_id)->first();
        if (!$productFirebase || empty($productFirebase->firebase_project_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'This product has no Firebase project ID. Save it via POST /api/products/{id}/firebase first.',
            ], 422);
        }
        if (empty($productFirebase->service_account_json)) {
            return response()->json([
                'status' => 'error',
                'message' => 'This product has no Firebase service account. Upload service_account_json via POST /api/products/{id}/firebase first.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $clientId = $request->client_id ?? auth()->id();
            $this->syncClientProfile($request, $clientId);

            $tenantKey = Str::slug($request->business_name) . '-p' . $request->product_id;

            // 1. Create Tenant
            $tenant = Tenant::create([
                'uuid' => Str::uuid()->toString(),
                'client_id' => $clientId,
                'name' => $request->business_name,
                'tenant_key' => $tenantKey,
                'business_name' => $request->business_name,
                'primary_contact_email' => $request->primary_contact_email,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'industry' => $request->industry,
                'product_id' => $request->product_id,
                'plan_id' => $request->plan_id,
                'status' => 'provisioning',
            ]);

            // Attach Add-ons if any
            if ($request->has('add_ons') && is_array($request->add_ons)) {
                $tenant->addOns()->attach($request->add_ons);
            }

                // Create Subscription
            $plan = \App\Models\Plan::find($request->plan_id);
            $billingCycle = $request->billing_cycle ?? 'monthly';

            $endDate = now()->addMonth();
            if ($billingCycle === 'yearly') {
                $endDate = now()->addYear();
            } else if ($plan && $plan->duration_days) {
                $endDate = now()->addDays($plan->duration_days);
            }

            $tenant->subscriptions()->create([
                'plan_id' => $request->plan_id,
                'status' => 'active',
                'start_date' => now(),
                'end_date' => $endDate,
            ]);

            // Calculate Amount
            $paymentAmount = 0;
            if ($plan) {
                $paymentAmount = $billingCycle === 'yearly' ? (float) $plan->annual_price : (float) $plan->monthly_price;
            }
            if ($request->has('add_ons') && is_array($request->add_ons)) {
                $paymentAmount += (float) \App\Models\AddOn::whereIn('id', $request->add_ons)->sum('price');
            }

            // Create Default Successful Payment
            $tenant->payments()->create([
                'transaction_id' => 'txn_' . Str::random(12),
                'amount' => $paymentAmount,
                'currency' => 'INR',
                'payment_method' => 'manual',
                'status' => 'success',
            ]);



            // Create Domain Configuration
            $domainType = $request->domain_type ?? 'subdomain';
            $domainStr = $request->domain ?? Str::uuid()->toString() . '.tidcraft.app';

            Domain::create([
                'tenant_id' => $tenant->id,
                'client_id' => $clientId,
                'product_id' => $request->product_id,
                'type' => $domainType,
                'domain' => $domainStr,
                'status' => 'pending',
            ]);

            DB::commit();

            // Dispatch the background provisioning job
            \App\Jobs\ProvisionTenantJob::dispatch($tenant);
            \App\Helpers\QueueRunner::runBackground();

            // Optionally log the provisioning action
            AuditLogger::log('Tenant Provisioned', 'New Tenant Created', "Tenant {$tenant->business_name} was provisioned and added to the queue.");

            return response()->json([
                'status' => 'success',
                'message' => 'Tenant created and queued for background provisioning.',
                'data' => [
                    'tenant_id' => $tenant->uuid,
                    'tenant_status' => $tenant->status,
                    'domain' => $domainStr
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to provision tenant.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a subdomain prefix is available.
     */
    public function checkSubdomain(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subdomain_prefix' => 'required|string|max:255',
            'tenant_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $prefix = trim($request->subdomain_prefix, " .");
        $fullDomain = $prefix . '.tidcraft.com';

        $query = \App\Models\Domain::where('domain', $fullDomain);
        
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', '!=', $request->tenant_id);
        }

        $existsInDomains = $query->exists();

        $available = !$existsInDomains;

        return response()->json([
            'status' => 'success',
            'available' => $available,
            'domain' => $prefix,
            'message' => $available ? 'Subdomain is available' : 'Subdomain is already taken'
        ]);
    }

    /**
     * Check if tenant is 5 days or less from expiring and mark past_due.
     */
    private function checkAndMarkPastDue($tenant)
    {
        if ($tenant->status === 'active') {
            $subscription = $tenant->subscriptions()->whereIn('status', ['active'])->first();
            if ($subscription && $subscription->end_date) {
                $endDate = \Carbon\Carbon::parse($subscription->end_date)->startOfDay();
                $daysRemaining = now()->startOfDay()->diffInDays($endDate, false);
                
                if ($daysRemaining <= 5) {
                    $tenant->status = 'past_due';
                    $tenant->save();
                    \App\Services\TenantProvisionService::blockTenant($tenant);
                }
            }
        }
    }

    /**
     * Display a listing of tenants.
     */
    public function index()
    {
        $tenants = \App\Models\Tenant::with(['client', 'product', 'plan', 'domains', 'firebaseProject', 'database', 'addOns', 'subscriptions', 'payments'])->get();
        
        $tenantList = collect();

        foreach ($tenants as $tenant) {
            $this->checkAndMarkPastDue($tenant);
            $tenantList->push($tenant);
        }

        // Get all clients (users) who DO NOT have any tenants
        $usersWithoutTenants = \App\Models\User::doesntHave('tenants')
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'SuperAdmin');
            })->get();

        foreach ($usersWithoutTenants as $user) {
            if ($user->profile_image && !str_starts_with($user->profile_image, 'http')) {
                $user->profile_image = asset($user->profile_image);
            }
            
            // Create a mock tenant structure for users without tenants
            $mockTenant = [
                'id' => null,
                'uuid' => null,
                'client_id' => $user->id,
                'client' => $user,
                'plan' => null,
                'product' => null,
                'domains' => [],
                'firebaseProject' => null,
                'database' => null,
                'addOns' => [],
                'subscriptions' => [],
                'payments' => [],
                'status' => 'no_tenant',
                'created_at' => clone $user->created_at,
            ];
            
            $tenantList->push($mockTenant);
        }

        // Sort by created_at descending (optional but usually good)
        $tenantList = $tenantList->sortByDesc('created_at')->values();

        return response()->json([
            'status' => 'success',
            'data' => $tenantList
        ]);
    }

    /**
     * Display the specified tenant by UUID.
     */
    public function show($uuid)
    {
        $tenant = Tenant::with(['client', 'product', 'plan', 'domains', 'firebaseProject', 'database', 'provisioningLogs', 'addOns', 'subscriptions', 'payments'])->where('uuid', $uuid)->first();

        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $this->checkAndMarkPastDue($tenant);

        return response()->json([
            'status' => 'success',
            'data' => $tenant
        ]);
    }

    /**
     * Display the provisioning status steps for a specific tenant.
     */
    public function provisioningStatus($uuid)
    {
        $query = Tenant::where('uuid', $uuid)
            ->orWhere('tenant_key', $uuid);
            
        if (is_numeric($uuid)) {
            $query->orWhere('id', $uuid);
        }
        
        $tenant = $query->first();

        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $logs = \App\Models\ProvisioningLog::where('tenant_id', $tenant->id)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'tenant_status' => $tenant->status,
            'data' => $logs
        ]);
    }

    /**
     * Download a JSON backup of the Firebase Firestore DB for this tenant.
     */
    public function backupFirebase($uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();

        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $productFirebase = \App\Models\ProductFirebaseProject::where('product_id', $tenant->product_id)->first();
        
        if (!$productFirebase || empty($productFirebase->service_account_json)) {
            return response()->json(['status' => 'error', 'message' => 'Firebase is not configured for this product.'], 400);
        }

        try {
            $serviceAccount = json_decode($productFirebase->service_account_json, true);
            $databaseId = $tenant->firestoreDatabaseId();
            
            $exporter = new \App\Services\FirestoreExporter($serviceAccount, $databaseId);
            $data = $exporter->export();
            
            $fileName = $tenant->tenant_key . '_firebase_backup_' . date('Y-m-d_H-i-s') . '.json';
            $jsonContent = json_encode($data, JSON_PRETTY_PRINT);
            
            // Save to Storage
            $path = 'backups/' . $fileName;
            \Illuminate\Support\Facades\Storage::disk('local')->put($path, $jsonContent);
            
            // Log in DB
            $tenant->tenantBackups()->create([
                'file_name' => $fileName,
                'file_path' => $path,
                'file_size' => strlen($jsonContent)
            ]);
            
            return response()->streamDownload(function () use ($jsonContent) {
                echo $jsonContent;
            }, $fileName, [
                'Content-Type' => 'application/json',
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Backup failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * List all Firebase backups for this tenant.
     */
    public function listAllBackups()
    {
        $backups = \App\Models\TenantBackup::with(['tenant.client', 'tenant.product'])->latest()->paginate(20);
        
        return response()->json([
            'status' => 'success',
            'data' => $backups
        ]);
    }

    public function listBackups($uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();

        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $backups = $tenant->tenantBackups()->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $backups
        ]);
    }

    /**
     * Restore a specific backup.
     */
    public function restoreBackup($uuid, $backupId)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();

        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $backup = $tenant->tenantBackups()->find($backupId);

        if (!$backup) {
            return response()->json([
                'status' => 'error', 
                'message' => "Backup with ID '{$backupId}' not found. Please ensure you are passing the actual database ID of the backup, not a timestamp."
            ], 404);
        }

        $productFirebase = \App\Models\ProductFirebaseProject::where('product_id', $tenant->product_id)->first();
        if (!$productFirebase || empty($productFirebase->service_account_json)) {
            return response()->json(['status' => 'error', 'message' => 'Firebase is not configured for this product.'], 400);
        }

        try {
            $jsonContent = \Illuminate\Support\Facades\Storage::disk('local')->get($backup->file_path);
            if (!$jsonContent) {
                return response()->json(['status' => 'error', 'message' => 'Backup file not found in storage.'], 404);
            }

            $data = json_decode($jsonContent, true);

            $serviceAccount = json_decode($productFirebase->service_account_json, true);
            $databaseId = $tenant->firestoreDatabaseId();

            $importer = new \App\Services\FirestoreImporter($serviceAccount, $databaseId);
            $importer->import($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Backup restored successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Restore failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified tenant in storage.
     */
    public function update(Request $request, $uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();

        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        // Handle cases where frontend FormData sends array as JSON string or comma-separated string
        if ($request->has('add_ons') && is_string($request->add_ons)) {
            $decoded = json_decode($request->add_ons, true);
            if (is_array($decoded)) {
                $request->merge(['add_ons' => $decoded]);
            } else {
                $request->merge(['add_ons' => array_filter(explode(',', $request->add_ons))]);
            }
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'nullable|exists:users,id',
            'client_name' => 'nullable|string|max:255',
            'client_logo' => 'nullable',
            'business_name' => 'nullable|string|max:255',
            'primary_contact_email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:20',
            'industry' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'product_id' => 'nullable|exists:products,id',
            'plan_id' => 'nullable|exists:plans,id',
            'status' => 'nullable|string',
            
            // Domain
            'domain_type' => 'nullable|in:subdomain,shared,custom',
            'domain' => 'nullable|string|unique:domains,domain,' . ($tenant->domains()->first() ? $tenant->domains()->first()->id : 'NULL') . ',id',

            // Firebase
            'firebase_project_id' => 'nullable|string',
            'firebase_api_key' => 'nullable|string',
            'firebase_app_id' => 'nullable|string',
            'firebase_auth_domain' => 'nullable|string',
            'firebase_storage_bucket' => 'nullable|string',
            'firebase_messaging_sender_id' => 'nullable|string',
            'firebase_database_url' => 'nullable|string|url',

            // Add-ons
            'add_ons' => 'nullable|array',
            'add_ons.*' => 'exists:add_ons,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $this->syncClientProfile($request, $tenant->client_id);

            $updateData = $request->only([
                'client_id', 'business_name', 'primary_contact_email', 'phone_number', 'address', 'industry', 'product_id', 'plan_id', 'status'
            ]);
            
            // Auto-generate name/tenant_key if business_name or product_id changed
            if ($request->has('business_name')) {
                $updateData['name'] = $request->business_name;
            }
            if ($request->has('business_name') || $request->has('product_id')) {
                $bName = $request->business_name ?? $tenant->business_name;
                $pId = $request->product_id ?? $tenant->product_id;
                $updateData['tenant_key'] = Str::slug($bName) . '-p' . $pId;
            }

            $oldStatus = $tenant->status;
            $tenant->update($updateData);

            // Handle suspending / un-suspending domains
            if ($request->has('status') && $request->status !== $oldStatus) {
                $newStatus = strtolower($request->status);
                if (in_array($newStatus, ['suspended', 'past due', 'past_due', 'expired'])) {
                    \App\Services\TenantProvisionService::blockTenant($tenant);
                } elseif ($newStatus === 'active') {
                    \App\Services\TenantProvisionService::unblockTenant($tenant);
                }
            }

            // Sync Add-ons
            if ($request->has('add_ons') && is_array($request->add_ons)) {
                $tenant->addOns()->sync($request->add_ons);
            }

            if ($request->hasAny(['domain_type', 'domain'])) {
                $domainData = [];
                if ($request->has('domain_type')) $domainData['type'] = $request->domain_type;
                if ($request->has('domain')) $domainData['domain'] = $request->domain;
                
                $existingDomain = $tenant->domains()->first();
                if ($existingDomain) {
                    $existingDomain->update($domainData);
                } else {
                    $domainData['tenant_id'] = $tenant->id;
                    $domainData['client_id'] = $tenant->client_id;
                    $domainData['product_id'] = $tenant->product_id;
                    $domainData['status'] = 'pending';
                    Domain::create($domainData);
                }
            }

            if ($request->hasAny(['firebase_project_id', 'firebase_api_key', 'firebase_app_id', 'firebase_auth_domain', 'firebase_storage_bucket', 'firebase_messaging_sender_id', 'firebase_database_url'])) {
                $firebaseData = [];
                if ($request->has('firebase_project_id')) $firebaseData['firebase_project_id'] = $request->firebase_project_id;
                if ($request->has('firebase_api_key')) $firebaseData['firebase_api_key'] = $request->firebase_api_key;
                if ($request->has('firebase_app_id')) $firebaseData['firebase_app_id'] = $request->firebase_app_id;
                if ($request->has('firebase_auth_domain')) $firebaseData['firebase_auth_domain'] = $request->firebase_auth_domain;
                if ($request->has('firebase_storage_bucket')) $firebaseData['firebase_storage_bucket'] = $request->firebase_storage_bucket;
                if ($request->has('firebase_messaging_sender_id')) $firebaseData['firebase_messaging_sender_id'] = $request->firebase_messaging_sender_id;

                if ($tenant->firebaseProject) {
                    $tenant->firebaseProject->update($firebaseData);
                } else {
                    $firebaseData['tenant_id'] = $tenant->id;
                    $firebaseData['client_id'] = $tenant->client_id;
                    $firebaseData['product_id'] = $tenant->product_id;
                    FirebaseProject::create($firebaseData);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Tenant updated successfully.',
                'data' => $tenant->fresh(['domains', 'firebaseProject'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update tenant.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified tenant from storage (soft delete).
     */
    public function destroy($uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();

        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        try {
            DB::beginTransaction();

            // Remove domain symlink completely
            $tenantsDirectory = env('TENANTS_DIRECTORY', '/home/devtidcraftcomusr/tenants/');
            foreach ($tenant->domains as $domain) {
                $symlinkPath = rtrim($tenantsDirectory, '/') . '/' . $domain->domain;
                if (is_link($symlinkPath) || file_exists($symlinkPath)) {
                    unlink($symlinkPath);
                }
                $domain->delete();
            }

            // Drop Firebase resources
            if ($tenant->firebaseProject) {
                $productFirebase = \App\Models\ProductFirebaseProject::where('product_id', $tenant->product_id)->first();
                if ($productFirebase && !empty($productFirebase->service_account_json)) {
                    $serviceAccount = json_decode($productFirebase->service_account_json, true) ?? [];
                    if (!empty($serviceAccount)) {
                        $adminClient = new \App\Services\FirebaseAdminClient();
                        if ($tenant->firebaseProject->firebase_tenant_id) {
                            $adminClient->deleteIdentityTenant($serviceAccount, $tenant->firebaseProject->firebase_tenant_id);
                        }
                        if ($tenant->firebaseProject->firebase_database_id) {
                            $adminClient->deleteFirestoreDatabase($serviceAccount, $tenant->firebaseProject->firebase_database_id);
                        }
                    }
                }
                $tenant->firebaseProject->delete();
            }

            // Soft delete tenant
            $tenant->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Tenant deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete tenant.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyPayment(Request $request, $uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'razorpay_payment_id' => 'required|string',
            'razorpay_payment_link_id' => 'nullable|string',
            'razorpay_payment_link_status' => 'nullable|string',
            'razorpay_signature' => 'nullable|string', // frontend might not send signature if it's a simple flow
            'status' => 'nullable|string', // fallback for custom status
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // If a signature is provided, we verify it for security
        if ($request->has('razorpay_signature') && $request->has('razorpay_payment_link_id') && $request->has('razorpay_payment_link_reference_id')) {
            $razorpaySettings = \App\Models\Setting::whereIn('key', ['razorpay_key_secret'])->pluck('value', 'key')->toArray();
            $keySecret = $razorpaySettings['razorpay_key_secret'] ?? null;
            
            if ($keySecret) {
                $expectedSignature = hash_hmac('sha256', $request->razorpay_payment_link_id . '|' . $request->razorpay_payment_link_reference_id . '|' . $request->razorpay_payment_link_status . '|' . $request->razorpay_payment_id, $keySecret);
                if (!hash_equals($expectedSignature, $request->razorpay_signature)) {
                    return response()->json(['status' => 'error', 'message' => 'Invalid payment signature'], 400);
                }
            }
        }

        try {
            DB::beginTransaction();

            $paymentStatus = $request->razorpay_payment_link_status === 'paid' ? 'success' : ($request->status ?? 'success');

            // Fetch detailed info from Razorpay API
            $bankRrn = null;
            $orderId = null;
            $customerDetails = null;
            $paymentMethodStr = 'razorpay';
            
            $razorpaySettingsForFetch = \App\Models\Setting::whereIn('key', ['razorpay_key_id', 'razorpay_key_secret'])->pluck('value', 'key')->toArray();
            $keyId = $razorpaySettingsForFetch['razorpay_key_id'] ?? null;
            $keySecretFetch = $razorpaySettingsForFetch['razorpay_key_secret'] ?? null;

            if ($keyId && $keySecretFetch && $request->has('razorpay_payment_id')) {
                try {
                    $api = new \Razorpay\Api\Api($keyId, $keySecretFetch);
                    $rzpPayment = $api->payment->fetch($request->razorpay_payment_id);
                    
                    if ($rzpPayment) {
                        // Extract bank_rrn or generic RRN
                        $bankRrn = $rzpPayment->acquirer_data['bank_transaction_id'] ?? $rzpPayment->acquirer_data['rrn'] ?? null;
                        $orderId = $rzpPayment->order_id ?? null;
                        
                        $customerDetails = [
                            'email' => $rzpPayment->email ?? null,
                            'contact' => $rzpPayment->contact ?? null,
                        ];
                        
                        // Try to get a more specific payment method
                        if ($rzpPayment->method) {
                            $paymentMethodStr = $rzpPayment->method;
                            if ($rzpPayment->bank) {
                                $paymentMethodStr .= ' (' . $rzpPayment->bank . ')';
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Razorpay Payment Fetch Error: ' . $e->getMessage());
                }
            }

            // Update the pending payment record for this tenant
            $payment = $tenant->payments()->where('status', 'pending')->first();
            if ($payment) {
                $payment->update([
                    'transaction_id' => $request->razorpay_payment_id,
                    'status' => $paymentStatus,
                    'payment_method' => $paymentMethodStr,
                    'bank_rrn' => $bankRrn,
                    'order_id' => $orderId,
                    'customer_details' => $customerDetails,
                ]);
            } else {
                // fallback if no pending payment was found
                $tenant->payments()->create([
                    'transaction_id' => $request->razorpay_payment_id,
                    'amount' => 0,
                    'currency' => 'INR',
                    'payment_method' => $paymentMethodStr,
                    'status' => $paymentStatus,
                    'bank_rrn' => $bankRrn,
                    'order_id' => $orderId,
                    'customer_details' => $customerDetails,
                ]);
            }

            // Handle post-payment logic based on payment type
            if ($paymentStatus === 'success') {
                $paymentType = $payment ? $payment->type : 'provisioning';

                if ($paymentType === 'renewal') {
                    $subscription = $tenant->subscriptions()->whereIn('status', ['active', 'expired'])->first();
                    if ($subscription) {
                        $currentEndDate = $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date) : now();
                        if ($currentEndDate->isPast()) {
                            $currentEndDate = now();
                        }
                        $subscription->end_date = $currentEndDate->addMonth();
                        $subscription->status = 'active';
                        $subscription->save();
                    }
                    if ($tenant->status === 'expired') {
                        $tenant->status = 'active';
                        $tenant->save();
                        \App\Services\TenantProvisionService::unblockTenant($tenant);
                    }
                } elseif ($paymentType === 'upgrade') {
                    $subscription = $tenant->subscriptions()->whereIn('status', ['active', 'expired'])->first();
                    $metadata = $payment->metadata ?? [];
                    if ($subscription && isset($metadata['new_plan_id'])) {
                        $subscription->plan_id = $metadata['new_plan_id'];
                        $subscription->start_date = now();
                        $subscription->end_date = now()->addMonth();
                        $subscription->status = 'active';
                        $subscription->save();
                    }
                    if ($tenant->status === 'expired') {
                        $tenant->status = 'active';
                        $tenant->save();
                        \App\Services\TenantProvisionService::unblockTenant($tenant);
                    }
                } else {
                    // Provisioning type
                    $domainExists = Domain::where('tenant_id', $tenant->id)->exists();
                    if ($domainExists && $tenant->status === 'provisioning') {
                        \App\Jobs\ProvisionTenantJob::dispatch($tenant);
                        \App\Helpers\QueueRunner::runBackground();
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment verified successfully.',
                'data' => [
                    'tenant_id' => $tenant->uuid,
                    'tenant_status' => $tenant->status,
                    'payment_status' => $paymentStatus
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify payment.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Map request client_name / client_logo onto users.name / users.profile_image.
     */
    private function syncClientProfile(Request $request, $clientId): void
    {
        if (!$clientId || !($request->has('client_name') || $request->has('client_logo'))) {
            return;
        }

        $clientUser = \App\Models\User::find($clientId);
        if (!$clientUser) {
            return;
        }

        if ($request->filled('client_name')) {
            $clientUser->name = $request->client_name;
        }

        if ($request->hasFile('client_logo')) {
            $path = $request->file('client_logo')->store('profiles', 'public');
            $clientUser->profile_image = '/storage/' . $path;
        } elseif ($request->filled('client_logo')) {
            $clientUser->profile_image = $request->client_logo;
        }

        $clientUser->save();
    }

    public function paymentstatuschnage(Request $request)
    {
        $request->validate([
            'tenant_id' => 'required',
            'status' => 'required|string|in:success,pending,failed,canceled'
        ]);

        $tenant = Tenant::where('id', $request->tenant_id)
            ->orWhere('uuid', $request->tenant_id)
            ->firstOrFail();

        $payment = $tenant->payments()->latest('create_at')->first();

        if ($payment) {
            $payment->update([
                'status' => $request->status
            ]);
            
            // Handle post-payment logic
            if ($request->status === 'success') {
                if ($tenant->status === 'provisioning') {
                    \App\Jobs\ProvisionTenantJob::dispatch($tenant);
                    \App\Helpers\QueueRunner::runBackground();
                } else if ($payment->type === 'renewal' || $payment->type === 'upgrade') {
                    $subscription = $tenant->subscriptions()->whereIn('status', ['active', 'expired'])->first();
                    if ($subscription) {
                        $currentEndDate = $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date) : now();
                        if ($currentEndDate->isPast()) {
                            $currentEndDate = now();
                        }
                        $subscription->end_date = $currentEndDate->addMonth();
                        $subscription->status = 'active';
                        $subscription->save();
                    }
                    if ($tenant->status === 'expired' || $tenant->status === 'past_due') {
                        $tenant->status = 'active';
                        $tenant->save();
                        \App\Services\TenantProvisionService::unblockTenant($tenant);
                    }
                }
            } else {
                // Payment is not success, disable access
                if (in_array($tenant->status, ['active', 'expired', 'past_due', 'suspended'])) {
                    $tenant->status = 'past_due';
                    $tenant->save();
                    \App\Services\TenantProvisionService::blockTenant($tenant);
                }
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'No payment record found for this tenant.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Payment status updated successfully',
            'data' => $payment
        ]);
    }

    public function renewClient(Request $request, $uuid)
    {
        $tenant = Tenant::with(['plan', 'addOns'])->where('uuid', $uuid)->first();

        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        try {
            DB::beginTransaction();

            // Calculate actual total amount
            $plan = $tenant->plan;
            $paymentAmount = $plan ? (float) $plan->monthly_price : 0;
            
            if ($tenant->addOns && $tenant->addOns->count() > 0) {
                $paymentAmount += (float) $tenant->addOns->sum('price');
            }

            // Create Payment
            $payment = null;
            if ($paymentAmount > 0) {
                $payment = $tenant->payments()->create([
                    'transaction_id' => 'txn_' . Str::random(12),
                    'amount' => $paymentAmount,
                    'currency' => 'INR',
                    'payment_method' => 'razorpay',
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            // Generate Razorpay Payment Link
            $paymentLinkStr = null;

            if ($paymentAmount > 0) {
                $razorpaySettings = \App\Models\Setting::whereIn('key', ['razorpay_key_id', 'razorpay_key_secret', 'razorpay_active'])->pluck('value', 'key')->toArray();
                
                $isActive = isset($razorpaySettings['razorpay_active']) && in_array($razorpaySettings['razorpay_active'], ['true', '1', true, 1], true);
                if ($isActive) {
                    $keyId = $razorpaySettings['razorpay_key_id'] ?? null;
                    $keySecret = $razorpaySettings['razorpay_key_secret'] ?? null;

                    if ($keyId && $keySecret) {
                        try {
                            $api = new \Razorpay\Api\Api($keyId, $keySecret);
                            
                            $customerData = array_filter([
                                'name' => $tenant->business_name,
                                'email' => $tenant->primary_contact_email,
                                'contact' => $tenant->phone_number
                            ]);

                            $paymentLinkData = [
                                'amount' => (int) ($paymentAmount * 100), // convert to paise
                                'currency' => 'INR',
                                'description' => 'Payment for Tenant Renewal',
                                'customer' => $customerData,
                                'notify' => ['email' => true, 'sms' => true],
                                'reminder_enable' => true,
                            ];
                            
                            $paymentLinkResponse = $api->paymentLink->create($paymentLinkData);
                            $paymentLinkStr = $paymentLinkResponse->short_url;
                            
                            if ($payment) { 
                                $payment->update(['transaction_id' => $paymentLinkResponse->id]); 
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Razorpay Payment Link Error: ' . $e->getMessage());
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Client renewal payment link generated successfully.',
                'data' => [
                    'tenant_id' => $tenant->uuid,
                    'payment_link' => $paymentLinkStr,
                    'amount' => $paymentAmount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initiate renewal.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function renewManual(Request $request, $uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $subscription = $tenant->subscriptions()->where('status', 'active')->first();
        if (!$subscription) {
            return response()->json(['status' => 'error', 'message' => 'No active subscription found.'], 400);
        }

        try {
            DB::beginTransaction();

            // Extend subscription by 1 month manually
            $currentEndDate = $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date) : now();
            $subscription->end_date = $currentEndDate->addMonth();
            $subscription->save();

            // Calculate amount based on plan
            $plan = $subscription->plan;
            $paymentAmount = $plan ? (float) $plan->monthly_price : 0;

            // Log manual payment for reporting
            $tenant->payments()->create([
                'transaction_id' => 'manual_' . Str::random(10),
                'amount' => $paymentAmount,
                'currency' => 'INR',
                'payment_method' => 'manual',
                'status' => 'success',
                'type' => 'renewal',
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Tenant subscription manually renewed.',
                'data' => [
                    'tenant_id' => $tenant->uuid,
                    'new_end_date' => $subscription->end_date
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to manually renew tenant.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function upgradeManual(Request $request, $uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'new_plan_id' => 'required|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $subscription = $tenant->subscriptions()->where('status', 'active')->first();
        if (!$subscription) {
            return response()->json(['status' => 'error', 'message' => 'No active subscription found.'], 400);
        }

        try {
            DB::beginTransaction();

            $newPlan = \App\Models\Plan::find($request->new_plan_id);

            // Update subscription to new plan and reset dates
            $subscription->plan_id = $newPlan->id;
            $subscription->start_date = now();
            $subscription->end_date = $newPlan->duration_days ? now()->addDays($newPlan->duration_days) : now()->addMonth();
            $subscription->save();

            $tenant->plan_id = $newPlan->id;
            $tenant->save();

            $paymentAmount = (float) $newPlan->monthly_price;

            // Log manual payment for reporting
            $tenant->payments()->create([
                'transaction_id' => 'manual_' . Str::random(10),
                'amount' => $paymentAmount,
                'currency' => 'INR',
                'payment_method' => 'manual',
                'status' => 'success',
                'type' => 'upgrade',
                'metadata' => ['new_plan_id' => $newPlan->id]
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Tenant subscription manually upgraded.',
                'data' => [
                    'tenant_id' => $tenant->uuid,
                    'new_plan_id' => $newPlan->id,
                    'new_end_date' => $subscription->end_date
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to manually upgrade tenant.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Explicitly trigger background provisioning for a tenant (Super Admin only).
     */
    public function manualProvision(Request $request, $uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)
            ->orWhere('id', $uuid)
            ->first();

        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant not found.'
            ], 404);
        }

        // Check if product Firebase configuration is available
        $productFirebase = \App\Models\ProductFirebaseProject::where('product_id', $tenant->product_id)->first();
        if (!$productFirebase || empty($productFirebase->firebase_project_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product has no Firebase project configured. Please configure product Firebase first.',
            ], 422);
        }

        $tenant->update(['status' => 'provisioning']);

        \App\Jobs\ProvisionTenantJob::dispatch($tenant);
        \App\Helpers\QueueRunner::runBackground();

        AuditLogger::log('Tenant Provisioned', 'Manual Provisioning Triggered', "Tenant {$tenant->business_name} was queued for manual provisioning by admin.");

        return response()->json([
            'status' => 'success',
            'message' => 'Tenant has been queued for background provisioning.',
            'data' => [
                'tenant_id' => $tenant->uuid,
                'tenant_status' => $tenant->status,
            ]
        ]);
    }

    /**
     * Send setup/whitelabel email to tenant client.
     */
    public function sendSetupEmail(Request $request, $uuid)
    {
        $tenant = Tenant::with(['client', 'product'])->where('uuid', $uuid)->orWhere('id', $uuid)->first();
        
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $client = $tenant->client;
        if (!$client) {
            return response()->json(['status' => 'error', 'message' => 'Client not found for this tenant.'], 404);
        }

        // Determine the email template slug
        $slug = $request->input('slug');
        
        // Auto-detect based on product if slug not provided
        if (!$slug) {
            $productName = strtolower($tenant->product->name ?? '');
            if (str_contains($productName, 'food')) {
                $slug = 'foodapp-whitelabel-setup';
            } else {
                $slug = 'whitelabel-setup';
            }
        }

        $template = \App\Models\EmailTemplate::where('slug', $slug)->first();

        if (!$template || $template->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Email template not found or is inactive: ' . $slug
            ], 404);
        }

        try {
            $imageUrl = (!empty($template->images) && isset($template->images[0])) ? url($template->images[0]) : '';
            
            $replacements = [
                '{name}' => $client->name,
                '{email}' => $client->email,
                '{business_name}' => $tenant->business_name,
                '{tenant_name}' => $tenant->name,
                '{product_name}' => $tenant->product->name ?? '',
                '{image}' => $imageUrl,
            ];

            \Illuminate\Support\Facades\Mail::to($client->email)->send(new \App\Mail\DynamicEmail($template, $replacements));

            AuditLogger::log('Setup Email Sent', 'Tenant Setup Email', "Sent setup email ($slug) to {$client->email} for tenant {$tenant->business_name}.");

            return response()->json([
                'status' => 'success',
                'message' => 'Setup email sent successfully.'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send setup email: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send setup email.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
