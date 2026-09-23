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
                'billing_cycle' => $billingCycle,
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
                'billing_cycle' => $billingCycle,
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
     * Supports checking availability while excluding the caller's tenant (via tenant_id, uuid, etc.)
     * or user/client (via user_id, client_id, email, or auth user).
     */
    public function checkSubdomain(Request $request, $uuid = null)
    {
        // Support either 'subdomain_prefix' or 'domain'
        $rawPrefix = $request->input('subdomain_prefix') ?? $request->input('domain');

        if (!$rawPrefix) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => ['subdomain_prefix' => ['The subdomain prefix field is required.']]
            ], 422);
        }

        $prefix = strtolower(trim($rawPrefix, " ."));
        $prefix = preg_replace('/\.tidcraft\.(com|app)$/i', '', $prefix);
        $fullDomain = $prefix . '.tidcraft.com';

        // 1. Resolve Tenant ID from all possible parameter sources (body, route, query, or headers)
        $tenantId = null;
        $tenantIdentifier = $uuid 
            ?? $request->input('tenant_id') 
            ?? $request->input('tenant_uuid') 
            ?? $request->input('uuid') 
            ?? $request->input('id')
            ?? $request->query('tenant_id')
            ?? $request->query('tenant_uuid')
            ?? $request->query('uuid')
            ?? $request->query('id')
            ?? $request->header('X-Tenant-Id')
            ?? $request->header('X-Tenant-Uuid');

        $tenant = null;
        if ($tenantIdentifier) {
            if (is_numeric($tenantIdentifier)) {
                $tenant = Tenant::find($tenantIdentifier);
            } else {
                $tenant = Tenant::where('uuid', $tenantIdentifier)->first();
            }
            if ($tenant) {
                $tenantId = $tenant->id;
            }
        }

        // 2. Resolve User / Client ID from all possible parameter sources
        $userId = null;
        $userIdentifier = $request->input('user_id') 
            ?? $request->input('client_id')
            ?? $request->query('user_id')
            ?? $request->query('client_id')
            ?? $request->header('X-User-Id')
            ?? $request->header('X-Client-Id');

        $contactEmail = $request->input('email')
            ?? $request->input('user_email')
            ?? $request->input('client_email')
            ?? $request->input('primary_contact_email')
            ?? $request->query('email');

        if ($userIdentifier) {
            if (is_numeric($userIdentifier)) {
                $userId = (int)$userIdentifier;
            } elseif (filter_var($userIdentifier, FILTER_VALIDATE_EMAIL)) {
                $contactEmail = $userIdentifier;
                $userByEmail = \App\Models\User::where('email', $userIdentifier)->first();
                if ($userByEmail) {
                    $userId = $userByEmail->id;
                }
            } else {
                $userByUuid = \App\Models\User::where('uuid', $userIdentifier)->first();
                if ($userByUuid) {
                    $userId = $userByUuid->id;
                }
            }
        }

        if (!$userId && $contactEmail && filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $userByEmail = \App\Models\User::where('email', $contactEmail)->first();
            if ($userByEmail) {
                $userId = $userByEmail->id;
            }
        }

        // If tenant was resolved, link its client_id
        if ($tenant && !$userId && $tenant->client_id) {
            $userId = $tenant->client_id;
        }

        // Fallback to authenticated user if no user_id passed and authenticated as client
        if (!$userId && $request->user()) {
            $authUser = $request->user();
            if (!$authUser->hasRole('super-admin') && !$authUser->hasRole('admin')) {
                $userId = $authUser->id;
            }
        }

        // 3. Query existing domain (excluding soft-deleted tenants)
        $existingDomain = \App\Models\Domain::where(function ($q) use ($fullDomain, $prefix) {
                $q->where('domain', $fullDomain)
                  ->orWhere('domain', $prefix . '.tidcraft.app');
            })
            ->whereHas('tenant')
            ->with(['tenant', 'tenant.client'])
            ->first();

        // If no existing domain found at all, it's completely available
        if (!$existingDomain) {
            return response()->json([
                'status' => 'success',
                'available' => true,
                'domain' => $prefix,
                'full_domain' => $fullDomain,
                'message' => 'Subdomain is available'
            ]);
        }

        $ownerTenantId = $existingDomain->tenant_id;
        $ownerClientId = $existingDomain->client_id ?? $existingDomain->tenant?->client_id;
        $ownerEmail = $existingDomain->tenant?->primary_contact_email ?? $existingDomain->tenant?->client?->email;

        // Check if this domain reservation belongs to the caller's tenant or user
        $isOwnTenant = $tenantId && ((string)$ownerTenantId === (string)$tenantId);
        $isOwnUser = $userId && $ownerClientId && ((string)$ownerClientId === (string)$userId);
        $isOwnEmail = !empty($contactEmail) && $ownerEmail && (strtolower($contactEmail) === strtolower($ownerEmail));
        // In case user passed tenant_id inside user_id parameter
        $isTenantIdMatchFromUserParam = $userIdentifier && is_numeric($userIdentifier) && ((string)$ownerTenantId === (string)$userIdentifier);

        $isOwnReservation = ($isOwnTenant || $isOwnUser || $isOwnEmail || $isTenantIdMatchFromUserParam);

        if ($isOwnReservation) {
            return response()->json([
                'status' => 'success',
                'available' => true,
                'domain' => $prefix,
                'full_domain' => $fullDomain,
                'is_own_reservation' => true,
                'message' => 'Subdomain is reserved for this user and ready for setup',
                'reserved_by' => [
                    'tenant_id' => $existingDomain->tenant_id,
                    'tenant_uuid' => $existingDomain->tenant?->uuid,
                    'business_name' => $existingDomain->tenant?->business_name ?? 'Your Tenant',
                    'status' => $existingDomain->tenant?->status ?? 'pending',
                    'client_id' => $ownerClientId,
                    'user_id' => $ownerClientId,
                ]
            ]);
        }

        // Subdomain is taken by another tenant / user
        return response()->json([
            'status' => 'success',
            'available' => false,
            'domain' => $prefix,
            'full_domain' => $fullDomain,
            'is_own_reservation' => false,
            'message' => 'Subdomain is already taken',
            'reserved_by' => [
                'tenant_id' => $existingDomain->tenant_id,
                'tenant_uuid' => $existingDomain->tenant?->uuid,
                'business_name' => $existingDomain->tenant?->business_name ?? 'Another Tenant',
                'status' => $existingDomain->tenant?->status ?? 'unknown',
            ]
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
        
        $purchasedList = collect();

        foreach ($tenants as $tenant) {
            $this->checkAndMarkPastDue($tenant);

            // Determine latest purchase/payment date for sorting
            $latestPayment = $tenant->payments ? $tenant->payments->sortByDesc('id')->first() : null;
            $paymentDate = $latestPayment ? ($latestPayment->create_at ?? $latestPayment->created_at) : null;

            $latestSub = $tenant->subscriptions ? $tenant->subscriptions->sortByDesc('id')->first() : null;
            $subDate = $latestSub ? ($latestSub->created_at ?? $latestSub->start_date) : null;

            $purchaseDate = $paymentDate ?? $subDate ?? $tenant->created_at;
            $purchaseTimestamp = $purchaseDate ? \Carbon\Carbon::parse($purchaseDate)->timestamp : 0;

            $purchasedList->push([
                'tenant' => $tenant,
                'purchase_timestamp' => $purchaseTimestamp,
            ]);
        }

        // Sort real tenants by latest purchase/activity timestamp descending (latest purchases first)
        $sortedPurchasedTenants = $purchasedList->sortByDesc('purchase_timestamp')->pluck('tenant');

        // Get all clients (users) who DO NOT have any tenants
        $usersWithoutTenants = \App\Models\User::doesntHave('tenants')
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'SuperAdmin');
            })
            ->latest()
            ->get();

        $noTenantList = collect();
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
            
            $noTenantList->push($mockTenant);
        }

        // Combine: Real purchased tenants first, followed by users without tenants (both in latest-first order)
        $combined = $sortedPurchasedTenants->concat($noTenantList);

        $mappedTenants = $combined->map(function ($t) {
            $item = is_array($t) ? $t : $t->toArray();
            
            // Map client to user to match requested frontend structure
            if (isset($item['client'])) {
                $item['user'] = $item['client'];
                // Keep client as well just in case to prevent breaking existing frontend logic
            }
            
            return $item;
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => $mappedTenants
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

        // Check if tenant has completed provisioning in the past
        $isFullyProvisioned = $logs->where('step', 'activation')->where('status', 'success')->isNotEmpty()
            && $tenant->firebaseProject && !empty($tenant->firebaseProject->firebase_database_id);

        // If not fully provisioned, trigger background provisioning if domain is configured
        if (!$isFullyProvisioned) {
            $hasDomain = $tenant->domains()->whereNotNull('domain')->where('domain', '!=', '')->exists();
            $productFirebase = \App\Models\ProductFirebaseProject::where('product_id', $tenant->product_id)->first();

            if ($hasDomain && $productFirebase && !empty($productFirebase->firebase_project_id)) {
                if ($tenant->status !== 'provisioning') {
                    $tenant->update(['status' => 'provisioning']);
                }
                \App\Jobs\ProvisionTenantJob::dispatch($tenant);
                \App\Helpers\QueueRunner::runBackground();

                // Small delay to allow the first step to log if this is the initial trigger
                if ($logs->isEmpty()) {
                    usleep(300000);
                    $logs = \App\Models\ProvisioningLog::where('tenant_id', $tenant->id)
                        ->orderBy('id', 'asc')
                        ->get();
                }
            }

            // An unprovisioned tenant MUST NEVER report status 'active' with empty/incomplete logs
            return response()->json([
                'status' => 'success',
                'tenant_status' => 'provisioning',
                'data' => $logs
            ]);
        }

        return response()->json([
            'status' => 'success',
            'tenant_status' => 'active',
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

        // Auto-construct full domain from subdomain_prefix
        if ($request->domain_type === 'subdomain' && $request->has('subdomain_prefix')) {
            $prefix = trim($request->subdomain_prefix, " .");
            $request->merge(['domain' => $prefix . '.tidcraft.com']);
        } elseif ($request->domain_type === 'custom' && $request->has('domain')) {
            $request->merge(['domain' => \App\Services\DnsService::normalizeDomain($request->domain)]);
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

            // Check if tenant has already completed provisioning in the past
            $provisioningLogs = \App\Models\ProvisioningLog::where('tenant_id', $tenant->id)->get();
            $isFullyProvisioned = $provisioningLogs->where('step', 'activation')->where('status', 'success')->isNotEmpty()
                && $tenant->firebaseProject && !empty($tenant->firebaseProject->firebase_database_id);

            $updateData = $request->only([
                'client_id', 'business_name', 'primary_contact_email', 'phone_number', 'address', 'industry', 'product_id', 'plan_id', 'status'
            ]);

            // Never prematurely mark an unprovisioned tenant as 'active' via update API
            if (!$isFullyProvisioned && isset($updateData['status']) && strtolower($updateData['status']) === 'active') {
                unset($updateData['status']);
            }
            
            // Auto-generate name/tenant_key if business_name or product_id changed
            if ($request->has('business_name')) {
                $updateData['name'] = $request->business_name;
            }
            if ($request->has('business_name') || $request->has('product_id')) {
                $bName = $request->business_name ?? $tenant->business_name;
                $pId = $request->product_id ?? $tenant->product_id;
                $updateData['tenant_key'] = Str::slug($bName) . '-p' . $pId;
            }

            // If domain is provided/updated and tenant has not yet completed provisioning, queue background provisioning
            $shouldTriggerProvisioning = false;
            if (!$isFullyProvisioned) {
                $hasDomainNow = $request->filled('domain') || $tenant->domains()->whereNotNull('domain')->where('domain', '!=', '')->exists();
                if ($hasDomainNow) {
                    $updateData['status'] = 'provisioning';
                    $shouldTriggerProvisioning = true;
                }
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
                    $oldDomain = $existingDomain->domain;
                    $newType = $domainData['type'] ?? $existingDomain->type;
                    $newDomain = $domainData['domain'] ?? $oldDomain;
                    
                    if ($newType === 'subdomain') {
                        $domainData['status'] = 'active';
                    } else if ($newType === 'custom' && $newDomain !== $oldDomain) {
                        $domainData['status'] = 'pending';
                    }

                    $existingDomain->update($domainData);

                    if ($isFullyProvisioned && $newType === 'subdomain' && $newDomain !== $oldDomain) {
                        \App\Services\DnsService::createTenantSymlink($tenant, $newDomain);
                    }
                } else {
                    $domainData['tenant_id'] = $tenant->id;
                    $domainData['client_id'] = $tenant->client_id;
                    $domainData['product_id'] = $tenant->product_id;
                    $domainData['status'] = ($domainData['type'] ?? '') === 'subdomain' ? 'active' : 'pending';
                    $newDomainRecord = Domain::create($domainData);
                    
                    if ($isFullyProvisioned && $newDomainRecord->type === 'subdomain') {
                        \App\Services\DnsService::createTenantSymlink($tenant, $newDomainRecord->domain);
                    }
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

            if ($shouldTriggerProvisioning) {
                \App\Jobs\ProvisionTenantJob::dispatch($tenant);
                \App\Helpers\QueueRunner::runBackground();
                AuditLogger::log('Tenant Provisioned', 'Provisioning Queued', "Tenant {$tenant->business_name} domain setup completed and background provisioning queued.");
            }

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

            // Find the specific payment being verified:
            // 1. By explicit payment_id, invoice_number, or razorpay_order_id
            $payment = null;
            if ($request->filled('payment_id')) {
                $payment = $tenant->payments()->where('id', $request->payment_id)->first();
            } elseif ($request->filled('invoice_number')) {
                $inv = $request->invoice_number;
                $invId = null;
                if (preg_match('/INV-\d{4}-(\d+)/', $inv, $matches)) {
                    $invId = (int)$matches[1];
                } elseif (preg_match('/INV-(\d+)/', $inv, $matches)) {
                    $invId = (int)$matches[1];
                } elseif (is_numeric($inv)) {
                    $invId = (int)$inv;
                }
                if ($invId) {
                    $payment = $tenant->payments()->where('id', $invId)->first();
                }
            } elseif ($request->filled('razorpay_order_id')) {
                $payment = $tenant->payments()->where('order_id', $request->razorpay_order_id)->first();
            }

            // 2. Otherwise pick the LATEST pending payment for this tenant
            if (!$payment) {
                $payment = $tenant->payments()->where('status', 'pending')->latest('id')->first();
            }

            // 3. Fallback
            if (!$payment) {
                $payment = $tenant->payments()->latest('id')->first();
            }

            if ($payment) {
                $payment->update([
                    'transaction_id' => $request->razorpay_payment_id ?? $payment->transaction_id,
                    'status' => $paymentStatus,
                    'payment_method' => $paymentMethodStr,
                    'bank_rrn' => $bankRrn,
                    'order_id' => $orderId ?: $payment->order_id,
                    'customer_details' => $customerDetails ?: $payment->customer_details,
                ]);
            } else {
                // fallback if no pending payment was found
                $payment = $tenant->payments()->create([
                    'transaction_id' => $request->razorpay_payment_id,
                    'amount' => 0,
                    'currency' => 'INR',
                    'payment_method' => $paymentMethodStr,
                    'status' => $paymentStatus,
                    'bank_rrn' => $bankRrn,
                    'order_id' => $orderId,
                    'customer_details' => $customerDetails,
                    'type' => 'provisioning'
                ]);
            }

            // Handle post-payment logic based on payment type
            if ($paymentStatus === 'success') {
                $paymentType = $payment ? $payment->type : 'provisioning';

                // Automatically clean up any other orphaned pending renewal/upgrade attempts for this tenant
                $tenant->payments()
                    ->where('status', 'pending')
                    ->where('id', '!=', $payment->id)
                    ->whereIn('type', ['renewal', 'upgrade'])
                    ->update(['status' => 'canceled']);

                if ($paymentType === 'renewal') {
                    $subscription = $tenant->subscriptions()->whereIn('status', ['active', 'expired', 'past_due'])->first();
                    if ($subscription) {
                        $currentEndDate = $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date) : now();
                        if ($currentEndDate->isPast()) {
                            $currentEndDate = now();
                        }
                        $billingCycle = $payment->billing_cycle ?? $subscription->billing_cycle ?? 'monthly';
                        if ($billingCycle === 'yearly' || $billingCycle === 'annual') {
                            $subscription->end_date = $currentEndDate->addYear();
                        } else {
                            $subscription->end_date = $currentEndDate->addMonth();
                        }
                        $subscription->status = 'active';
                        $subscription->save();
                    }
                    if (in_array($tenant->status, ['expired', 'past_due', 'pending'])) {
                        $tenant->status = 'active';
                        $tenant->save();
                        \App\Services\TenantProvisionService::unblockTenant($tenant);
                    }
                } elseif ($paymentType === 'upgrade') {
                    $subscription = $tenant->subscriptions()->whereIn('status', ['active', 'expired', 'past_due'])->first();
                    $metadata = $payment->metadata ?? [];
                    $newPlanId = $metadata['new_plan_id'] ?? $request->input('new_plan_id');
                    if ($subscription && $newPlanId) {
                        $subscription->plan_id = $newPlanId;
                        if (isset($metadata['billing_cycle'])) {
                            $subscription->billing_cycle = $metadata['billing_cycle'];
                        }
                        $subscription->start_date = now();
                        
                        $plan = \App\Models\Plan::find($newPlanId);
                        $billingCycle = $subscription->billing_cycle ?? 'monthly';
                        
                        if ($billingCycle === 'yearly' || $billingCycle === 'annual') {
                            $subscription->end_date = now()->addYear();
                        } else if ($plan && $plan->duration_days) {
                            $subscription->end_date = now()->addDays($plan->duration_days);
                        } else {
                            $subscription->end_date = now()->addMonth();
                        }
                        
                        $subscription->status = 'active';
                        $subscription->save();
                    }
                    if ($newPlanId) {
                        $tenant->plan_id = $newPlanId;
                    }
                    if (in_array($tenant->status, ['expired', 'past_due', 'pending'])) {
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
            } elseif (in_array($paymentStatus, ['failed', 'canceled', 'cancelled'])) {
                if ($payment && in_array($payment->type, ['renewal', 'upgrade'])) {
                    // Keep subscription and tenant in their current active state
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment verified successfully.',
                'data' => [
                    'tenant_id' => $tenant->uuid,
                    'tenant_status' => $tenant->status,
                    'payment_status' => $paymentStatus,
                    'invoice_number' => $payment ? $payment->invoice_number : null,
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
            'status' => 'required|string|in:success,pending,failed,canceled,cancelled',
            'payment_id' => 'nullable|integer'
        ]);

        $tenant = Tenant::where('id', $request->tenant_id)
            ->orWhere('uuid', $request->tenant_id)
            ->firstOrFail();

        $payment = null;
        if ($request->filled('payment_id')) {
            $payment = $tenant->payments()->where('id', $request->payment_id)->first();
        }
        if (!$payment) {
            $payment = $tenant->payments()->latest('id')->first();
        }

        if ($payment) {
            $normalizedStatus = in_array($request->status, ['canceled', 'cancelled']) ? 'canceled' : $request->status;
            $payment->update([
                'status' => $normalizedStatus
            ]);
            
            // Handle post-payment logic
            if ($normalizedStatus === 'success') {
                if ($tenant->status === 'provisioning') {
                    \App\Jobs\ProvisionTenantJob::dispatch($tenant);
                    \App\Helpers\QueueRunner::runBackground();
                } else if ($payment->type === 'renewal' || $payment->type === 'upgrade') {
                    $subscription = $tenant->subscriptions()->whereIn('status', ['active', 'expired', 'past_due'])->first();
                    if ($subscription) {
                        $currentEndDate = $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date) : now();
                        if ($currentEndDate->isPast()) {
                            $currentEndDate = now();
                        }
                        $billingCycle = $payment->billing_cycle ?? $subscription->billing_cycle ?? 'monthly';
                        if ($billingCycle === 'yearly' || $billingCycle === 'annual') {
                            $subscription->end_date = $currentEndDate->addYear();
                        } else {
                            $subscription->end_date = $currentEndDate->addMonth();
                        }
                        $subscription->status = 'active';
                        $subscription->save();
                    }
                    if (in_array($tenant->status, ['expired', 'past_due', 'pending'])) {
                        $tenant->status = 'active';
                        $tenant->save();
                        \App\Services\TenantProvisionService::unblockTenant($tenant);
                    }
                }
            } elseif (in_array($normalizedStatus, ['canceled', 'pending'])) {
                // If payment was for renewal or upgrade, do NOT alter the tenant's active status or block access
            } else {
                // 'failed' status: only block/downgrade if NOT a renewal/upgrade for an existing active tenant
                if (!in_array($payment->type, ['renewal', 'upgrade'])) {
                    if (in_array($tenant->status, ['active', 'expired', 'past_due', 'suspended'])) {
                        $tenant->status = 'past_due';
                        $tenant->save();
                        \App\Services\TenantProvisionService::blockTenant($tenant);
                    }
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

            $subscription = $tenant->subscriptions()->whereIn('status', ['active', 'expired', 'past_due'])->first();
            $billingCycle = $subscription->billing_cycle ?? 'monthly';

            // Calculate actual total amount
            $plan = $tenant->plan;
            $paymentAmount = 0;
            if ($plan) {
                $paymentAmount = ($billingCycle === 'yearly' || $billingCycle === 'annual') ? (float) $plan->annual_price : (float) $plan->monthly_price;
            }
            
            if ($tenant->addOns && $tenant->addOns->count() > 0) {
                $paymentAmount += (float) $tenant->addOns->sum('price');
            }

            // Smart Pending Reuse: If a pending renewal payment already exists for this tenant, update it instead of creating duplicates
            $payment = $tenant->payments()
                ->where('status', 'pending')
                ->where('type', 'renewal')
                ->latest('id')
                ->first();

            if ($payment) {
                $payment->update([
                    'amount' => $paymentAmount,
                    'currency' => 'INR',
                    'billing_cycle' => $billingCycle,
                    'payment_method' => 'razorpay',
                ]);
            } else {
                $payment = $tenant->payments()->create([
                    'transaction_id' => 'txn_' . Str::random(12),
                    'amount' => $paymentAmount,
                    'currency' => 'INR',
                    'billing_cycle' => $billingCycle,
                    'payment_method' => 'razorpay',
                    'status' => 'pending',
                    'type' => 'renewal',
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
                    'payment_id' => $payment ? $payment->id : null,
                    'invoice_number' => $payment ? $payment->invoice_number : null,
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

    /**
     * Initiate online plan upgrade payment link for a tenant.
     */
    public function upgrade(Request $request, $uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'new_plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'nullable|in:monthly,yearly,annual',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $newPlan = \App\Models\Plan::find($request->new_plan_id);
        $subscription = $tenant->subscriptions()->whereIn('status', ['active', 'expired', 'past_due'])->first();
        $billingCycle = $request->billing_cycle ?? ($subscription->billing_cycle ?? 'monthly');
        $paymentAmount = ($billingCycle === 'yearly' || $billingCycle === 'annual') ? (float) $newPlan->annual_price : (float) $newPlan->monthly_price;

        // Smart Pending Reuse: If a pending upgrade payment already exists for this tenant, update it instead of creating duplicates
        $payment = $tenant->payments()
            ->where('status', 'pending')
            ->where('type', 'upgrade')
            ->latest('id')
            ->first();

        if ($payment) {
            $payment->update([
                'amount' => $paymentAmount,
                'currency' => 'INR',
                'billing_cycle' => $billingCycle,
                'payment_method' => 'razorpay',
                'metadata' => ['new_plan_id' => $newPlan->id, 'billing_cycle' => $billingCycle]
            ]);
        } else {
            $payment = $tenant->payments()->create([
                'transaction_id' => 'txn_' . Str::random(12),
                'amount' => $paymentAmount,
                'currency' => 'INR',
                'billing_cycle' => $billingCycle,
                'payment_method' => 'razorpay',
                'status' => 'pending',
                'type' => 'upgrade',
                'metadata' => ['new_plan_id' => $newPlan->id, 'billing_cycle' => $billingCycle]
            ]);
        }

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
                            'amount' => (int) ($paymentAmount * 100),
                            'currency' => 'INR',
                            'description' => 'Payment for Plan Upgrade to ' . $newPlan->name,
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
            'message' => 'Upgrade payment initiated.',
            'data' => [
                'tenant_id' => $tenant->uuid,
                'payment_id' => $payment ? $payment->id : null,
                'invoice_number' => $payment ? $payment->invoice_number : null,
                'payment_link' => $paymentLinkStr,
                'amount' => $paymentAmount
            ]
        ]);
    }

    public function renewManual(Request $request, $uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'plan_id' => 'nullable|exists:plans,id',
            'billing_cycle' => 'nullable|in:monthly,yearly,annual',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $subscription = $tenant->subscriptions()->whereIn('status', ['active', 'expired', 'past_due'])->first();
        if (!$subscription) {
            return response()->json(['status' => 'error', 'message' => 'No subscription found to renew.'], 400);
        }

        try {
            DB::beginTransaction();

            if ($request->has('plan_id')) {
                $newPlan = \App\Models\Plan::find($request->plan_id);
                if ($newPlan) {
                    $tenant->plan_id = $newPlan->id;
                    $subscription->plan_id = $newPlan->id;
                    $tenant->setRelation('plan', $newPlan);
                    $subscription->setRelation('plan', $newPlan);
                }
            }

            $billingCycle = $request->billing_cycle ?? $subscription->billing_cycle ?? 'monthly';
            $currentEndDate = $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date) : now();
            if ($currentEndDate->isPast()) {
                $currentEndDate = now();
            }

            if ($billingCycle === 'yearly' || $billingCycle === 'annual') {
                $subscription->end_date = $currentEndDate->addYear();
            } else {
                $subscription->end_date = $currentEndDate->addMonth();
            }
            $subscription->billing_cycle = $billingCycle;
            $subscription->status = 'active';
            $subscription->save();

            $tenant->status = 'active';
            $tenant->save();
            \App\Services\TenantProvisionService::unblockTenant($tenant);

            // Calculate amount based on plan
            $plan = $subscription->plan ?? $tenant->plan;
            $paymentAmount = 0;
            if ($plan) {
                $paymentAmount = ($billingCycle === 'yearly' || $billingCycle === 'annual') ? (float) $plan->annual_price : (float) $plan->monthly_price;
            }

            // Automatically clean up any orphaned pending renewal/upgrade attempts for this tenant
            $tenant->payments()
                ->where('status', 'pending')
                ->whereIn('type', ['renewal', 'upgrade'])
                ->update(['status' => 'canceled']);

            // Log manual payment for reporting
            $payment = $tenant->payments()->create([
                'transaction_id' => 'manual_' . Str::random(10),
                'amount' => $paymentAmount,
                'currency' => 'INR',
                'billing_cycle' => $billingCycle,
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
                    'new_end_date' => $subscription->end_date,
                    'invoice_number' => $payment->invoice_number
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
            'billing_cycle' => 'nullable|in:monthly,yearly,annual',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $subscription = $tenant->subscriptions()->whereIn('status', ['active', 'expired', 'past_due'])->first();
        if (!$subscription) {
            return response()->json(['status' => 'error', 'message' => 'No active subscription found.'], 400);
        }

        try {
            DB::beginTransaction();

            $newPlan = \App\Models\Plan::find($request->new_plan_id);
            $billingCycle = $request->billing_cycle ?? ($subscription->billing_cycle ?? 'monthly');

            // Update subscription to new plan and reset dates
            $subscription->plan_id = $newPlan->id;
            $subscription->billing_cycle = $billingCycle;
            $subscription->start_date = now();
            if ($billingCycle === 'yearly' || $billingCycle === 'annual') {
                $subscription->end_date = now()->addYear();
            } else if ($newPlan->duration_days) {
                $subscription->end_date = now()->addDays($newPlan->duration_days);
            } else {
                $subscription->end_date = now()->addMonth();
            }
            $subscription->status = 'active';
            $subscription->save();

            $tenant->plan_id = $newPlan->id;
            $tenant->status = 'active';
            $tenant->save();
            \App\Services\TenantProvisionService::unblockTenant($tenant);

            $paymentAmount = ($billingCycle === 'yearly' || $billingCycle === 'annual') ? (float) $newPlan->annual_price : (float) $newPlan->monthly_price;

            // Automatically clean up any orphaned pending renewal/upgrade attempts for this tenant
            $tenant->payments()
                ->where('status', 'pending')
                ->whereIn('type', ['renewal', 'upgrade'])
                ->update(['status' => 'canceled']);

            // Log manual payment for reporting
            $payment = $tenant->payments()->create([
                'transaction_id' => 'manual_' . Str::random(10),
                'amount' => $paymentAmount,
                'currency' => 'INR',
                'billing_cycle' => $billingCycle,
                'payment_method' => 'manual',
                'status' => 'success',
                'type' => 'upgrade',
                'metadata' => ['new_plan_id' => $newPlan->id, 'billing_cycle' => $billingCycle]
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Tenant subscription manually upgraded.',
                'data' => [
                    'tenant_id' => $tenant->uuid,
                    'new_plan_id' => $newPlan->id,
                    'new_end_date' => $subscription->end_date,
                    'invoice_number' => $payment->invoice_number
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
     * Cancel an unfulfilled or pending payment (e.g. when user closes/cancels checkout popup).
     */
    public function cancelPayment(Request $request, $uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->orWhere('id', $uuid)->first();
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $payment = null;
        if ($request->filled('payment_id')) {
            $payment = $tenant->payments()->where('id', $request->payment_id)->first();
        } elseif ($request->filled('invoice_number')) {
            $inv = $request->invoice_number;
            $invId = null;
            if (preg_match('/INV-\d{4}-(\d+)/', $inv, $matches)) {
                $invId = (int)$matches[1];
            } elseif (preg_match('/INV-(\d+)/', $inv, $matches)) {
                $invId = (int)$matches[1];
            } elseif (is_numeric($inv)) {
                $invId = (int)$inv;
            }
            if ($invId) {
                $payment = $tenant->payments()->where('id', $invId)->first();
            }
        }

        if (!$payment) {
            $payment = $tenant->payments()->where('status', 'pending')->latest('id')->first();
        }

        if ($payment && $payment->status === 'pending') {
            $payment->update([
                'status' => 'canceled'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Pending payment cancelled successfully.',
                'data' => [
                    'tenant_id' => $tenant->uuid,
                    'payment_id' => $payment->id,
                    'invoice_number' => $payment->invoice_number,
                    'payment_status' => 'canceled'
                ]
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'No active pending payment found to cancel.'
        ]);
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
        $tenant = Tenant::with(['client', 'product', 'domains'])->where('uuid', $uuid)->orWhere('id', $uuid)->first();
        
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        // Verify site provisioning is active before sending setup email
        if (strtolower($tenant->status ?? '') !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot send setup email. Site provisioning is not active yet (current status: ' . ($tenant->status ?: 'inactive') . '). Please wait until provisioning is complete.'
            ], 422);
        }

        $client = $tenant->client;
        $clientEmail = $request->input('email') ?: ($tenant->primary_contact_email ?? ($client ? $client->email : null));
        $clientName = $request->input('name') ?: ($client ? $client->name : $tenant->business_name);

        if (!$clientEmail) {
            return response()->json(['status' => 'error', 'message' => 'Client email not found for this tenant.'], 404);
        }

        // Determine the email template slug
        $slug = $request->input('slug');
        
        // Auto-detect based on product ID or product name if slug not provided
        if (!$slug) {
            $productId = $tenant->product_id;
            $productName = strtolower($tenant->product->name ?? '');
            if ($productId == 2 || str_contains($productName, 'park')) {
                $slug = 'parkmeapp-whitelabel-setup';
            } else {
                $slug = 'foodapp-whitelabel-setup';
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
            $domainObj = $tenant->domains()->first();
            $domainUrl = 'https://' . ($domainObj ? $domainObj->domain : 'tidcraft.com');
            $adminUrl = $domainUrl . '/admin_panel';
            $imageUrl = (!empty($template->images) && isset($template->images[0])) ? url($template->images[0]) : '';

            $replacements = [
                '{name}' => $clientName,
                '{{name}}' => $clientName,
                '{client_name}' => $clientName,
                '{{client_name}}' => $clientName,
                '{clientName}' => $clientName,
                '{email}' => $clientEmail,
                '{{email}}' => $clientEmail,
                '{business_name}' => $tenant->business_name,
                '{{business_name}}' => $tenant->business_name,
                '{tenant_name}' => $tenant->name,
                '{{tenant_name}}' => $tenant->name,
                '{product_name}' => $tenant->product->name ?? '',
                '{{product_name}}' => $tenant->product->name ?? '',
                '{app_name}' => $tenant->business_name ?: ($tenant->product->name ?? 'App'),
                '{{app_name}}' => $tenant->business_name ?: ($tenant->product->name ?? 'App'),
                '{meta_title}' => ($tenant->business_name ?: 'TidCraft') . ' - Platform',
                '{{meta_title}}' => ($tenant->business_name ?: 'TidCraft') . ' - Platform',
                '{website_url}' => $domainUrl,
                '{{website_url}}' => $domainUrl,
                '{store_url}' => $adminUrl,
                '{{store_url}}' => $adminUrl,
                '{support_email}' => $tenant->primary_contact_email ?: 'support@tidcraft.com',
                '{{support_email}}' => $tenant->primary_contact_email ?: 'support@tidcraft.com',
                '{support_phone}' => $tenant->phone_number ?: '+91 8545961258',
                '{{support_phone}}' => $tenant->phone_number ?: '+91 8545961258',
                '{support_url}' => $domainUrl . '/support',
                '{{support_url}}' => $domainUrl . '/support',
                '{business_address}' => $tenant->address ?: 'Adajan, Surat, Gujarat - India',
                '{{business_address}}' => $tenant->address ?: 'Adajan, Surat, Gujarat - India',
                '{image}' => $imageUrl,
                '{{image}}' => $imageUrl,
                '{tenant}' => $tenant,
                'tenant' => $tenant,
            ];

            \Illuminate\Support\Facades\Mail::to($clientEmail)->send(new \App\Mail\DynamicEmail($template, $replacements));

            AuditLogger::log('Setup Email Sent', 'Tenant Setup Email', "Sent setup email ($slug) to {$clientEmail} for tenant {$tenant->business_name}.");

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

    /**
     * Send Product Admin Panel Setup Reminder / Provisioned email manually.
     */
    public function sendProvisionedEmail(Request $request, $uuid)
    {
        $tenant = Tenant::with(['client', 'product', 'domains'])->where('uuid', $uuid)->orWhere('id', $uuid)->first();
        
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        // Verify site provisioning is active before sending provisioned / setup reminder email
        if (strtolower($tenant->status ?? '') !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot send provisioned email. Site provisioning is not active yet (current status: ' . ($tenant->status ?: 'inactive') . '). Please wait until provisioning is complete.'
            ], 422);
        }

        $client = $tenant->client;
        $adminEmail = $tenant->primary_contact_email ?? ($client ? $client->email : 'admin@' . ($tenant->domains()->first()?->domain ?? 'tidcraft.com'));
        $clientName = $client ? $client->name : $tenant->business_name;
        
        $baseName = trim($tenant->name ?: $tenant->business_name);
        $adminPassword = empty($baseName) ? 'tidcraft' : str_replace(' ', '', strtolower($baseName)) . '-tidcraft';
        $domainObj = $tenant->domains()->first();
        $domainUrl = 'https://' . ($domainObj ? $domainObj->domain : 'tidcraft.com');
        $adminUrl = rtrim($domainUrl, '/') . '/admin_panel';
        $apanelUrl = $adminUrl;
        $restaurantPanelUrl = rtrim($domainUrl, '/') . '/restaurant_panel';
        $ownerPanelUrl = rtrim($domainUrl, '/') . '/owner_panel';

        $prodName = strtolower($tenant->product?->slug ?? $tenant->product?->name ?? '');
        $isFoodApp = str_contains($prodName, 'food') || str_contains($prodName, 'eats');
        $isParkApp = str_contains($prodName, 'park') || str_contains($prodName, 'parkme');

        // Support both Setup_email (Admin Panel Setup Reminder) and Your_Application_is_Ready
        $slug = $request->input('slug', 'Your_Application_is_Ready');
        $template = \App\Models\EmailTemplate::where('slug', $slug)->first();

        $recipientEmails = array_filter(array_unique(array_map('strtolower', [
            $adminEmail,
            $tenant->client?->email,
            $tenant->primary_contact_email
        ])));

        try {
            if ($template && $template->status === 'active') {
                $imageUrl = (!empty($template->images) && isset($template->images[0])) ? url($template->images[0]) : '';
                
                $replacements = [
                    'name' => $clientName,
                    '{name}' => $clientName,
                    '{{name}}' => $clientName,
                    'client_name' => $clientName,
                    '{client_name}' => $clientName,
                    '{{client_name}}' => $clientName,
                    'clientName' => $clientName,
                    'email' => $adminEmail,
                    '{email}' => $adminEmail,
                    '{{email}}' => $adminEmail,
                    'login_email' => $adminEmail,
                    '{login_email}' => $adminEmail,
                    '{{login_email}}' => $adminEmail,
                    'admin_email' => $adminEmail,
                    '{admin_email}' => $adminEmail,
                    '{{admin_email}}' => $adminEmail,
                    'adminEmail' => $adminEmail,
                    'admin_password' => $adminPassword,
                    '{admin_password}' => $adminPassword,
                    '{{admin_password}}' => $adminPassword,
                    'adminPassword' => $adminPassword,
                    'password' => $adminPassword,
                    '{password}' => $adminPassword,
                    '{{password}}' => $adminPassword,
                    'domain_url' => $domainUrl,
                    '{domain_url}' => $domainUrl,
                    '{{domain_url}}' => $domainUrl,
                    'domainUrl' => $domainUrl,
                    'website_url' => $domainUrl,
                    '{website_url}' => $domainUrl,
                    '{{website_url}}' => $domainUrl,
                    'admin_url' => $adminUrl,
                    '{admin_url}' => $adminUrl,
                    '{{admin_url}}' => $adminUrl,
                    'adminUrl' => $adminUrl,
                    'apanel_url' => $apanelUrl,
                    '{apanel_url}' => $apanelUrl,
                    '{{apanel_url}}' => $apanelUrl,
                    'restaurantPanelUrl' => $restaurantPanelUrl,
                    'restaurant_panel_url' => $restaurantPanelUrl,
                    '{restaurant_panel_url}' => $restaurantPanelUrl,
                    'ownerPanelUrl' => $ownerPanelUrl,
                    'owner_panel_url' => $ownerPanelUrl,
                    '{owner_panel_url}' => $ownerPanelUrl,
                    'isFoodApp' => $isFoodApp,
                    'isParkApp' => $isParkApp,
                    'business_name' => $tenant->business_name,
                    '{business_name}' => $tenant->business_name,
                    '{{business_name}}' => $tenant->business_name,
                    'tenant_name' => $tenant->name,
                    '{tenant_name}' => $tenant->name,
                    '{{tenant_name}}' => $tenant->name,
                    'product_name' => $tenant->product->name ?? '',
                    '{product_name}' => $tenant->product->name ?? '',
                    '{{product_name}}' => $tenant->product->name ?? '',
                    'image' => $imageUrl,
                    '{image}' => $imageUrl,
                    '{{image}}' => $imageUrl,
                    'hasAppImage' => !empty($imageUrl),
                    'tenant' => $tenant,
                ];

                foreach ($recipientEmails as $recEmail) {
                    \Illuminate\Support\Facades\Mail::to($recEmail)->send(new \App\Mail\DynamicEmail($template, $replacements));
                }
            } else {
                foreach ($recipientEmails as $recEmail) {
                    if ($slug === 'Your_Application_is_Ready') {
                        \Illuminate\Support\Facades\Mail::to($recEmail)->send(new \App\Mail\TenantProvisionedEmail($tenant, $adminEmail, $adminPassword, $domainUrl));
                    } else {
                        \Illuminate\Support\Facades\Mail::to($recEmail)->send(new \App\Mail\TenantSetupReadyMail($tenant));
                    }
                }
            }

            $templateTitle = $template ? $template->title : ($slug === 'Your_Application_is_Ready' ? 'Application Ready' : 'Setup Reminder');
            AuditLogger::log('Provisioned Email Sent', 'Tenant Setup Reminder / Ready Email', "Sent {$templateTitle} to " . implode(', ', $recipientEmails) . " for tenant {$tenant->business_name}.");

            return response()->json([
                'status' => 'success',
                'message' => ($slug === 'Your_Application_is_Ready' ? 'Application Ready' : 'Setup reminder') . ' email sent successfully to ' . implode(', ', $recipientEmails) . '.'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send provisioned / setup reminder email: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send email.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify live DNS propagation for tenant custom domain (Admin / Public API).
     */
    public function verifyDns(Request $request, $uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $domain = Domain::where('tenant_id', $tenant->id)->first();
        if (!$domain) {
            return response()->json(['status' => 'error', 'message' => 'No domain configured for this tenant.'], 404);
        }

        $verification = \App\Services\DnsService::verifyDomainDns($domain->domain, $domain->type);

        if ($verification['verified']) {
            $domain->update(['status' => 'active']);

            // Create frontend symlink for Nginx
            \App\Services\DnsService::createTenantSymlink($tenant, $domain->domain);

            return response()->json([
                'status' => 'success',
                'verified' => true,
                'message' => 'DNS records verified successfully! Domain is now active.',
                'data' => [
                    'tenant_id' => $tenant->uuid,
                    'domain' => $domain->domain,
                    'domain_type' => $domain->type,
                    'status' => 'active',
                    'server_ip' => $verification['server_ip'],
                    'resolved_ips' => $verification['resolved_ips'],
                    'verified_at' => now()->toIso8601String()
                ]
            ]);
        }

        return response()->json([
            'status' => 'error',
            'verified' => false,
            'message' => $verification['message'],
            'data' => [
                'tenant_id' => $tenant->uuid,
                'domain' => $domain->domain,
                'domain_type' => $domain->type,
                'status' => $domain->status,
                'server_ip' => $verification['server_ip'],
                'current_resolved_ips' => $verification['resolved_ips'],
                'dns_records' => \App\Services\DnsService::getExpectedDnsRecords($domain->domain, $domain->type)
            ]
        ], 400);
    }

    /**
     * Resend DNS instructions email to the client for this tenant (Admin API).
     */
    public function sendDnsEmail(Request $request, $uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $domain = Domain::where('tenant_id', $tenant->id)->first();
        if (!$domain) {
            return response()->json(['status' => 'error', 'message' => 'No domain configured for this tenant.'], 404);
        }

        $sent = \App\Services\DnsService::sendDnsInstructionsEmail($tenant, $domain, true);

        if ($sent) {
            $client = $tenant->client ?? \App\Models\User::find($tenant->client_id ?? $tenant->create_by);
            $email = $client->email ?? $tenant->primary_contact_email;
            return response()->json([
                'status' => 'success',
                'message' => "DNS setup instructions email sent successfully to {$email}."
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to send DNS instructions email. Please check contact email configuration.'
        ], 500);
    }
}
