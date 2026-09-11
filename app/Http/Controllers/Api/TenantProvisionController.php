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
            $request->merge(['domain' => $prefix . '.tidcraft.app']);
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
            $tenant->subscriptions()->create([
                'plan_id' => $request->plan_id,
                'status' => 'active',
                'start_date' => now(),
            ]);

            // Create Default Successful Payment
            $plan = \App\Models\Plan::find($request->plan_id);
            $tenant->payments()->create([
                'transaction_id' => 'txn_' . Str::random(12),
                'amount' => $plan ? $plan->monthly_price : 0,
                'currency' => 'INR',
                'payment_method' => 'manual',
                'status' => 'success',
            ]);



            // 2. Create Domain Configuration
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

            $tenant->load('domains');
            $firestoreDatabaseId = $tenant->firestoreDatabaseId();

            try {
                \App\Services\FirebaseProvisionService::provisionFirebase($tenant);
                $tenant->load('firebaseProject');
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tenant was created but Firestore database '.$firestoreDatabaseId.' was not created.',
                    'error' => $e->getMessage(),
                    'data' => [
                        'tenant_id' => $tenant->uuid,
                        'firebase_database_id' => $firestoreDatabaseId,
                    ]
                ], 500);
            }

            // MySQL tenant DB, migrations, and seed run in background
            \App\Jobs\ProvisionTenantJob::dispatch($tenant);
            
            // Optionally log the provisioning action
            AuditLogger::log('Tenant Provisioned', 'New Tenant Created', "Tenant {$tenant->business_name} was provisioned.");

            return response()->json([
                'status' => 'success',
                'message' => 'Tenant created. Firestore database '.$tenant->firestoreDatabaseId().' created. MySQL migrate/seed are running in the background.',
                'data' => [
                    'tenant_id' => $tenant->uuid,
                    'tenant_status' => $tenant->status,
                    'database_name' => $tenant->provisionedDatabaseName(),
                    'firebase_project_id' => $tenant->firebaseProject?->firebase_project_id ?? $productFirebase->firebase_project_id,
                    'firebase_database_id' => $tenant->firestoreDatabaseId(),
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $prefix = trim($request->subdomain_prefix, " .");

        $existsInDomains = \App\Models\Domain::where('domain', $prefix)->exists();

        $available = !$existsInDomains;

        return response()->json([
            'status' => 'success',
            'available' => $available,
            'domain' => $prefix,
            'message' => $available ? 'Subdomain is available' : 'Subdomain is already taken'
        ]);
    }

    /**
     * Display a listing of tenants.
     */
    public function index()
    {
        $tenants = Tenant::with(['client', 'product', 'plan', 'domains', 'firebaseProject', 'database', 'addOns', 'subscriptions', 'payments'])->get();
        return response()->json([
            'status' => 'success',
            'data' => $tenants
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

        return response()->json([
            'status' => 'success',
            'data' => $tenant
        ]);
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

            $tenant->update($updateData);

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

            // Soft delete relationships if they exist
            foreach ($tenant->domains as $domain) {
                $domain->delete();
            }
            if ($tenant->firebaseProject) {
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

            // If payment was successful, start automatic provisioning
            if ($paymentStatus === 'success') {
                \App\Jobs\ProvisionTenantJob::dispatch($tenant);
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
            
            // Trigger automatic provisioning if we switch it to success and it's not already provisioned
            if ($request->status === 'success' && $tenant->status === 'provisioning') {
                \App\Jobs\ProvisionTenantJob::dispatch($tenant);
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
}
