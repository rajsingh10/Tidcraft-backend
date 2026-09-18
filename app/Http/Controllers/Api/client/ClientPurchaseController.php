<?php

namespace App\Http\Controllers\Api\client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\Payment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Domain;
use App\Models\TenantFirebaseConfig;
use App\Services\AuditLogger;

class ClientPurchaseController extends Controller
{
    /**
     * Display a listing of all purchases (tenants) owned by the logged-in client.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Fetch tenants linked to this user
        $tenants = Tenant::with(['product', 'plan', 'domains', 'subscriptions', 'payments'])
            ->where(function ($q) use ($user) {
                $q->where('create_by', $user->id)
                  ->orWhere('client_id', $user->id);
            })
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $tenants
        ]);
    }

    /**
     * Display the detailed information of a specific purchase (tenant) owned by the client.
     */
    public function show(Request $request, $uuid)
    {
        $user = $request->user();

        // Fetch the specific tenant ensuring it belongs to this client
        $tenant = Tenant::with(['product', 'plan', 'domains', 'firebaseProject', 'addOns', 'subscriptions', 'provisioningLogs', 'payments'])
            ->where('uuid', $uuid)
            ->where(function ($q) use ($user) {
                $q->where('create_by', $user->id)
                  ->orWhere('client_id', $user->id);
            })
            ->first();

        if (!$tenant) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Purchase not found or you do not have permission to view it.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $tenant
        ]);
    }

    /**
     * Display the provisioning status steps for a specific purchase.
     */
    public function provisioningStatus(Request $request, $uuid)
    {
        $user = $request->user();

        $tenant = Tenant::where('uuid', $uuid)
            ->where('create_by', $user->id)
            ->first();

        if (!$tenant) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Purchase not found or you do not have permission to view it.'
            ], 404);
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
     * Download a JSON backup of the Firebase Firestore DB for this purchase.
     */
    public function backupFirebase(Request $request, $uuid)
    {
        $user = $request->user();

        $tenant = Tenant::where('uuid', $uuid)
            ->where('create_by', $user->id)
            ->first();

        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Purchase not found.'], 404);
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
     * List all Firebase backups for this purchase.
     */
    public function listBackups(Request $request, $uuid)
    {
        $user = $request->user();

        $tenant = Tenant::where('uuid', $uuid)->where('create_by', $user->id)->first();

        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Purchase not found.'], 404);
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
    public function restoreBackup(Request $request, $uuid, $backupId)
    {
        $user = $request->user();

        $tenant = Tenant::where('uuid', $uuid)->where('create_by', $user->id)->first();

        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Purchase not found.'], 404);
        }

        $backup = $tenant->tenantBackups()->findOrFail($backupId);

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
     * Display a listing of all payments made by this client.
     */
    public function payments(Request $request)
    {
        $user = $request->user();

        // First find all tenant IDs owned by the user
        $tenantIds = Tenant::where('create_by', $user->id)
            ->orWhere('client_id', $user->id)
            ->pluck('id');

        // Fetch all payments associated with those tenants, along with product & plan info
        $payments = Payment::whereIn('tenant_id', $tenantIds)
            ->with(['tenant.product', 'tenant.plan'])
            ->orderBy('create_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $payments
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // Auto-fill from user profile if not provided
        if (!$request->has('business_name') && $user->company_name) {
            $request->merge(['business_name' => $user->company_name]);
        }
        
        // Auto-construct full domain from subdomain_prefix
        if ($request->domain_type === 'subdomain' && $request->has('subdomain_prefix')) {
            $prefix = trim($request->subdomain_prefix, " .");
            $request->merge(['domain' => $prefix . '.tidcraft.com']);
        }
        
        $validator = Validator::make($request->all(), [
            // Step 1: Client Info
            'business_name' => 'required|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'company_logo' => 'nullable',
            'primary_contact_email' => 'nullable|email|max:255',
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

            // Payment Details
            'transaction_id' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'currency' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_status' => 'nullable|string',
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
                'message' => 'This product has no Firebase project ID configured. Please contact support.',
            ], 422);
        }
        if (empty($productFirebase->service_account_json)) {
            return response()->json([
                'status' => 'error',
                'message' => 'This product has no Firebase service account configured. Please contact support.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update user profile with client name and logo if provided
            if ($request->filled('client_name')) {
                $user->name = $request->client_name;
            }
            if ($request->hasFile('company_logo')) {
                $path = $request->file('company_logo')->store('profiles', 'public');
                $user->profile_image = '/storage/' . $path;
            } elseif ($request->filled('company_logo') && is_string($request->company_logo)) {
                $user->profile_image = $request->company_logo;
            }
            $user->save();

            // Clean up any previously abandoned checkouts for this same product to prevent duplicate pending entries
            $abandonedTenants = Tenant::where('client_id', $user->id)
                ->where('product_id', $request->product_id)
                ->where('status', 'provisioning')
                ->whereDoesntHave('payments', function ($query) {
                    $query->where('status', 'success');
                })
                ->get();
                
            foreach ($abandonedTenants as $abandoned) {
                // Delete related records to prevent orphan data before force deleting the abandoned tenant
                $abandoned->subscriptions()->delete();
                $abandoned->payments()->delete();
                Domain::where('tenant_id', $abandoned->id)->delete();
                $abandoned->forceDelete();
            }

            $tenantKey = Str::slug($request->business_name) . '-p' . $request->product_id;

            // 1. Create Tenant
            $tenant = Tenant::create([
                'client_id' => $user->id,
                'create_by' => $user->id,
                'uuid' => Str::uuid()->toString(),
                'name' => $request->business_name,
                'tenant_key' => $tenantKey,
                'business_name' => $request->business_name,
                'primary_contact_email' => $user->email, // Always use the logged-in client's email securely
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'industry' => $request->industry,
                'product_id' => $request->product_id,
                'plan_id' => $request->plan_id,
                'status' => 'pending',
            ]);

            // Attach Add-ons if any
            if ($request->has('add_ons') && is_array($request->add_ons)) {
                $tenant->addOns()->attach($request->add_ons);
            }

            // Calculate actual total amount based on billing cycle
            $plan = \App\Models\Plan::find($request->plan_id);
            $billingCycle = $request->billing_cycle ?? 'monthly';
            
            $paymentAmount = 0;
            if ($plan) {
                $paymentAmount = $billingCycle === 'yearly' ? (float) $plan->annual_price : (float) $plan->monthly_price;
            }
            if ($request->has('add_ons') && is_array($request->add_ons)) {
                $paymentAmount += (float) \App\Models\AddOn::whereIn('id', $request->add_ons)->sum('price');
            }

            // Determine End Date
            $endDate = now()->addMonth();
            if ($billingCycle === 'yearly') {
                $endDate = now()->addYear();
            } else if ($plan && $plan->duration_days) {
                $endDate = now()->addDays($plan->duration_days);
            }

            // Determine initial payment status and subscription status
            $initialPaymentStatus = $paymentAmount > 0 ? ($request->payment_status ?? 'pending') : 'success';
            $subscriptionStatus = $initialPaymentStatus === 'success' ? 'active' : 'pending';

            // Create Subscription
            $tenant->subscriptions()->create([
                'plan_id' => $request->plan_id,
                'status' => $subscriptionStatus,
                'start_date' => now(),
                'end_date' => $endDate,
            ]);

            // Create Payment
            $payment = $tenant->payments()->create([
                'transaction_id' => $request->transaction_id ?? ($paymentAmount > 0 ? null : ('FREE-' . strtoupper(Str::random(10)))),
                'amount' => $paymentAmount,
                'currency' => $request->currency ?? 'INR',
                'payment_method' => $paymentAmount > 0 ? ($request->payment_method ?? 'razorpay') : 'free',
                'status' => $initialPaymentStatus,
                'type' => 'purchase',
            ]);

            // 2. Create Domain Configuration
            if ($request->has('domain_type') && $request->has('domain')) {
                Domain::create([
                    'tenant_id' => $tenant->id,
                    'client_id' => $user->id,
                    'product_id' => $tenant->product_id,
                    'type' => $request->domain_type,
                    'domain' => $request->domain,
                    'status' => 'pending',
                ]);
            }



            DB::commit();

            // Generate Razorpay Payment Link
            $paymentLinkStr = null;
            $paymentErrorStr = null;

            if ($paymentAmount > 0) {
                $razorpaySettings = \App\Models\Setting::whereIn('key', ['razorpay_key_id', 'razorpay_key_secret', 'razorpay_active'])->pluck('value', 'key')->toArray();
                
                $isActive = isset($razorpaySettings['razorpay_active']) && in_array($razorpaySettings['razorpay_active'], ['true', '1', true, 1], true);
                if ($isActive) {
                    $keyId = $razorpaySettings['razorpay_key_id'] ?? null;
                    $keySecret = $razorpaySettings['razorpay_key_secret'] ?? null;

                    if ($keyId && $keySecret) {
                        try {
                            $customerData = array_filter([
                                'name' => $tenant->business_name,
                                'email' => $tenant->primary_contact_email,
                                'contact' => $tenant->phone_number
                            ]);

                            $paymentLinkData = [
                                'amount' => (int) ($paymentAmount * 100), // convert to paise
                                'currency' => $request->currency ?? 'INR',
                                'description' => 'Payment for Tenant Provisioning',
                                'customer' => $customerData,
                                'notify' => ['email' => true, 'sms' => true],
                                'reminder_enable' => true,
                            ];
                            
                            $response = \Illuminate\Support\Facades\Http::withBasicAuth($keyId, $keySecret)
                                ->post('https://api.razorpay.com/v1/payment_links', $paymentLinkData);

                            if ($response->successful()) {
                                $paymentLinkStr = $response->json('short_url');
                            } else {
                                throw new \Exception('Razorpay Error: ' . $response->body());
                            }
                        } catch (\Exception $e) {
                            $paymentErrorStr = $e->getMessage();
                            \Illuminate\Support\Facades\Log::error('Razorpay Payment Link Error: ' . $e->getMessage());
                        }
                    } else {
                        $paymentErrorStr = 'razorpay_key_id or razorpay_key_secret is missing in settings table';
                    }
                } else {
                    $paymentErrorStr = 'razorpay_active is not set to 1 or true in settings table';
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Tenant provisioned successfully.',
                'data' => [
                    'tenant_id' => $tenant->uuid,
                    'tenant_status' => $tenant->status,
                    'payment_status' => $payment->status,
                    'payment_link' => $paymentLinkStr,
                    'payment_link_error' => $paymentErrorStr,
                    'amount' => $paymentAmount,
                    'invoice_number' => $payment->invoice_number,
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

    public function renew(Request $request, $uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->where('client_id', auth()->id())->first();
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $subscription = $tenant->subscriptions()->whereIn('status', ['active', 'expired'])->first();
        if (!$subscription) {
            return response()->json(['status' => 'error', 'message' => 'No active subscription found to renew.'], 400);
        }

        $plan = $subscription->plan;
        if (!$plan) {
            return response()->json(['status' => 'error', 'message' => 'Subscription plan not found.'], 400);
        }

        $paymentAmount = (float) $plan->monthly_price;
        
        $payment = $tenant->payments()->create([
            'amount' => $paymentAmount,
            'currency' => 'INR',
            'payment_method' => 'razorpay',
            'status' => 'pending',
            'type' => 'renewal',
        ]);

        $paymentLinkStr = $this->generateRazorpayLink($tenant, $paymentAmount, 'Payment for Subscription Renewal');

        return response()->json([
            'status' => 'success',
            'message' => 'Renewal payment initiated.',
            'data' => [
                'tenant_id' => $tenant->uuid,
                'payment_link' => $paymentLinkStr,
                'amount' => $paymentAmount
            ]
        ]);
    }

    public function upgrade(Request $request, $uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->where('client_id', auth()->id())->first();
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'new_plan_id' => 'required|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $newPlan = \App\Models\Plan::find($request->new_plan_id);
        $paymentAmount = (float) $newPlan->monthly_price;

        $payment = $tenant->payments()->create([
            'amount' => $paymentAmount,
            'currency' => 'INR',
            'payment_method' => 'razorpay',
            'status' => 'pending',
            'type' => 'upgrade',
            'metadata' => ['new_plan_id' => $newPlan->id]
        ]);

        $paymentLinkStr = $this->generateRazorpayLink($tenant, $paymentAmount, 'Payment for Plan Upgrade to ' . $newPlan->name);

        return response()->json([
            'status' => 'success',
            'message' => 'Upgrade payment initiated.',
            'data' => [
                'tenant_id' => $tenant->uuid,
                'payment_link' => $paymentLinkStr,
                'amount' => $paymentAmount
            ]
        ]);
    }

    private function generateRazorpayLink($tenant, $amount, $description)
    {
        $razorpaySettings = \App\Models\Setting::whereIn('key', ['razorpay_key_id', 'razorpay_key_secret', 'razorpay_active'])->pluck('value', 'key')->toArray();
        $isActive = isset($razorpaySettings['razorpay_active']) && in_array($razorpaySettings['razorpay_active'], ['true', '1', true, 1], true);
        
        if (!$isActive) return null;

        $keyId = $razorpaySettings['razorpay_key_id'] ?? null;
        $keySecret = $razorpaySettings['razorpay_key_secret'] ?? null;

        if ($keyId && $keySecret) {
            try {
                $api = new \Razorpay\Api\Api($keyId, $keySecret);
                $paymentLinkData = [
                    'amount' => (int) ($amount * 100),
                    'currency' => 'INR',
                    'description' => $description,
                    'customer' => array_filter([
                        'name' => $tenant->business_name,
                        'email' => $tenant->primary_contact_email,
                        'contact' => $tenant->phone_number
                    ]),
                    'notify' => ['email' => true, 'sms' => true],
                    'reminder_enable' => true,
                ];
                return $api->paymentLink->create($paymentLinkData)->short_url;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Razorpay Payment Link Error: ' . $e->getMessage());
            }
        }
        return null;
    }

    public function verifyPayment(Request $request, $uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'razorpay_payment_id' => 'nullable|string',
            'razorpay_payment_link_id' => 'nullable|string',
            'razorpay_payment_link_status' => 'nullable|string',
            'razorpay_signature' => 'nullable|string', // frontend might not send signature if it's a simple flow
            'status' => 'nullable|string', // fallback for custom status ('success', 'failed', 'pending', 'canceled')
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

            // Determine initial payment status from request params
            $paymentStatus = 'pending';

            if ($request->filled('razorpay_payment_link_status')) {
                $linkStatus = strtolower($request->razorpay_payment_link_status);
                $paymentStatus = match ($linkStatus) {
                    'paid' => 'success',
                    'failed', 'expired' => 'failed',
                    'cancelled', 'canceled' => 'canceled',
                    default => $linkStatus
                };
            } elseif ($request->filled('status')) {
                $incomingStatus = strtolower($request->status);
                $paymentStatus = match ($incomingStatus) {
                    'success', 'paid' => 'success',
                    'failed' => 'failed',
                    'cancelled', 'canceled' => 'canceled',
                    'pending' => 'pending',
                    default => $incomingStatus
                };
            } elseif ($request->filled('razorpay_payment_id')) {
                $paymentStatus = 'success';
            }

            // Fetch detailed info from Razorpay API if payment ID is available
            $bankRrn = null;
            $orderId = null;
            $customerDetails = null;
            $paymentMethodStr = 'razorpay';
            
            $razorpaySettingsForFetch = \App\Models\Setting::whereIn('key', ['razorpay_key_id', 'razorpay_key_secret'])->pluck('value', 'key')->toArray();
            $keyId = $razorpaySettingsForFetch['razorpay_key_id'] ?? null;
            $keySecretFetch = $razorpaySettingsForFetch['razorpay_key_secret'] ?? null;

            if ($keyId && $keySecretFetch && $request->filled('razorpay_payment_id')) {
                try {
                    $api = new \Razorpay\Api\Api($keyId, $keySecretFetch);
                    $rzpPayment = $api->payment->fetch($request->razorpay_payment_id);
                    
                    if ($rzpPayment) {
                        if (isset($rzpPayment->status)) {
                            if (in_array($rzpPayment->status, ['captured', 'authorized', 'paid'])) {
                                $paymentStatus = 'success';
                            } elseif ($rzpPayment->status === 'failed') {
                                $paymentStatus = 'failed';
                            }
                        }

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
            if (!$payment) {
                $payment = $tenant->payments()->latest('id')->first();
            }

            if ($payment) {
                $payment->update([
                    'transaction_id' => $request->razorpay_payment_id ?? $payment->transaction_id,
                    'status' => $paymentStatus,
                    'payment_method' => $paymentMethodStr,
                    'bank_rrn' => $bankRrn,
                    'order_id' => $orderId,
                    'customer_details' => $customerDetails,
                ]);
            } else {
                // Fallback if no payment was found
                $payment = $tenant->payments()->create([
                    'transaction_id' => $request->razorpay_payment_id,
                    'amount' => 0,
                    'currency' => 'INR',
                    'payment_method' => $paymentMethodStr,
                    'status' => $paymentStatus,
                    'bank_rrn' => $bankRrn,
                    'order_id' => $orderId,
                    'customer_details' => $customerDetails,
                    'type' => 'purchase'
                ]);
            }

            // Handle subscription and tenant status logic
            $subscription = $tenant->subscriptions()->latest('id')->first();

            if ($paymentStatus === 'success') {
                $paymentType = $payment ? ($payment->type ?? 'purchase') : 'purchase';

                if ($paymentType === 'renewal') {
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
                    // Initial Client Purchase: Activate subscription, keep tenant status 'pending'
                    if ($subscription) {
                        $subscription->status = 'active';
                        $subscription->save();
                    }
                    $tenant->status = 'pending';
                    $tenant->save();
                }
            } elseif (in_array($paymentStatus, ['failed', 'canceled', 'cancelled'])) {
                if ($subscription) {
                    $subscription->status = $paymentStatus;
                    $subscription->save();
                }
                // Tenant remains pending
                $tenant->status = 'pending';
                $tenant->save();
            }

            DB::commit();

            if ($paymentStatus === 'success' && isset($payment)) {
                try {
                    $client = $tenant->client;
                    $clientEmail = $client ? $client->email : $tenant->primary_contact_email;
                    if ($clientEmail) {
                        \Illuminate\Support\Facades\Mail::to($clientEmail)->send(new \App\Mail\ClientPaymentReceivedMail($tenant, $payment));
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send payment received email to client: ' . $e->getMessage());
                }

                // Send Email to Admin
                try {
                    $admin = \App\Models\User::role('SuperAdmin')->first();
                    if ($admin && $admin->email) {
                        \Illuminate\Support\Facades\Mail::to($admin->email)->send(new \App\Mail\AdminPaymentReceivedMail($tenant, $payment));
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send admin payment notification email: ' . $e->getMessage());
                }

                // Create Admin Notification for Payment
                try {
                    $clientName = $tenant->client ? $tenant->client->name : $tenant->business_name;
                    \App\Models\AdminNotification::create([
                        'type' => 'payment_received',
                        'title' => 'Payment Received',
                        'message' => 'Payment of ' . $payment->currency . ' ' . $payment->amount . ' received from ' . $clientName . '.',
                        'related_id' => $tenant->id,
                        'client_name' => $clientName,
                        'is_read' => false
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to create payment received notification: ' . $e->getMessage());
                }
            } elseif ($paymentStatus === 'failed' && isset($payment)) {
                try {
                    $client = $tenant->client;
                    $clientEmail = $client ? $client->email : $tenant->primary_contact_email;
                    if ($clientEmail) {
                        \Illuminate\Support\Facades\Mail::to($clientEmail)->send(new \App\Mail\ClientPaymentFailedMail($tenant, $payment));
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send payment failed email to client: ' . $e->getMessage());
                }

                try {
                    $admin = \App\Models\User::role('SuperAdmin')->first();
                    if ($admin && $admin->email) {
                        \Illuminate\Support\Facades\Mail::to($admin->email)->send(new \App\Mail\AdminPaymentFailedMail($tenant, $payment));
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send admin payment failed notification email: ' . $e->getMessage());
                }

                try {
                    $clientName = $tenant->client ? $tenant->client->name : $tenant->business_name;
                    \App\Models\AdminNotification::create([
                        'type' => 'payment_failed',
                        'title' => 'Payment Failed',
                        'message' => 'Payment attempt of ' . $payment->currency . ' ' . $payment->amount . ' failed from ' . $clientName . '.',
                        'related_id' => $tenant->id,
                        'client_name' => $clientName,
                        'is_read' => false
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to create payment failed notification: ' . $e->getMessage());
                }
            }

            return response()->json([
                'status' => $paymentStatus === 'success' ? 'success' : 'error',
                'message' => $paymentStatus === 'success' ? 'Payment verified successfully.' : ('Payment ' . $paymentStatus . '.'),
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

    public function setupDomain(Request $request, $uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();
        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found.'], 404);
        }

        // Auto-construct full domain from subdomain_prefix
        if ($request->domain_type === 'subdomain' && $request->has('subdomain_prefix')) {
            $prefix = trim($request->subdomain_prefix, " .");
            $request->merge(['domain' => $prefix . '.tidcraft.com']);
        }

        $validator = Validator::make($request->all(), [
            'domain_type' => 'required|in:subdomain,shared,custom',
            'domain' => 'required|string|unique:domains,domain',
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

            Domain::create([
                'tenant_id' => $tenant->id,
                'client_id' => $tenant->client_id ?? $tenant->create_by,
                'product_id' => $tenant->product_id,
                'type' => $request->domain_type,
                'domain' => $request->domain,
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Domain configured successfully.',
                'data' => [
                    'tenant_id' => $tenant->uuid,
                    'domain' => $request->domain
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to configure domain.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkoutproduct(Request $request)
    {
        $user = $request->user();

        // Auto-fill from user profile if not provided
        if (!$request->has('business_name') && $user->company_name) {
            $request->merge(['business_name' => $user->company_name]);
        }
        
        // Auto-construct full domain from subdomain_prefix
        if ($request->domain_type === 'subdomain' && $request->has('subdomain_prefix')) {
            $prefix = trim($request->subdomain_prefix, " .");
            $request->merge(['domain' => $prefix . '.tidcraft.com']);
        }
        
        $validator = Validator::make($request->all(), [
            // Step 1: Client Info
            'business_name' => 'required|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'company_logo' => 'nullable',
            'primary_contact_email' => 'nullable|email|max:255',
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

            // Payment Details
            'transaction_id' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'currency' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_status' => 'nullable|string',
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

            // Update user profile with client name and logo if provided
            if ($request->filled('client_name')) {
                $user->name = $request->client_name;
            }
            if ($request->hasFile('company_logo')) {
                $path = $request->file('company_logo')->store('profiles', 'public');
                $user->profile_image = '/storage/' . $path;
            } elseif ($request->filled('company_logo') && is_string($request->company_logo)) {
                $user->profile_image = $request->company_logo;
            }
            $user->save();

            // Clean up any previously abandoned checkouts for this same product to prevent duplicate pending entries
            $abandonedTenants = Tenant::where('client_id', $user->id)
                ->where('product_id', $request->product_id)
                ->whereIn('status', ['provisioning', 'pending'])
                ->whereDoesntHave('payments', function ($query) {
                    $query->where('status', 'success');
                })
                ->get();
                
            foreach ($abandonedTenants as $abandoned) {
                // Delete related records to prevent orphan data before force deleting the abandoned tenant
                $abandoned->subscriptions()->delete();
                $abandoned->payments()->delete();
                Domain::where('tenant_id', $abandoned->id)->delete();
                $abandoned->forceDelete();
            }

            // Calculate actual total amount based on billing cycle
            $plan = \App\Models\Plan::find($request->plan_id);
            $billingCycle = $request->billing_cycle ?? 'monthly';
            
            $paymentAmount = 0;
            if ($plan) {
                $paymentAmount = $billingCycle === 'yearly' ? (float) $plan->annual_price : (float) $plan->monthly_price;
            }
            if ($request->has('add_ons') && is_array($request->add_ons)) {
                $paymentAmount += (float) \App\Models\AddOn::whereIn('id', $request->add_ons)->sum('price');
            }

            // Determine End Date
            $endDate = now()->addMonth();
            if ($billingCycle === 'yearly') {
                $endDate = now()->addYear();
            } else if ($plan && $plan->duration_days) {
                $endDate = now()->addDays($plan->duration_days);
            }

            $initialStatus = 'pending';

            $tenantKey = Str::slug($request->business_name) . '-p' . $request->product_id;

            // 1. Create Tenant (stores the product plan purchase record)
            $tenant = Tenant::create([
                'client_id' => $user->id,
                'create_by' => $user->id,
                'uuid' => Str::uuid()->toString(),
                'name' => $request->business_name,
                'tenant_key' => $tenantKey,
                'business_name' => $request->business_name,
                'primary_contact_email' => $user->email,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'industry' => $request->industry,
                'product_id' => $request->product_id,
                'plan_id' => $request->plan_id,
                'status' => $initialStatus,
            ]);

            // Attach Add-ons if any
            if ($request->has('add_ons') && is_array($request->add_ons)) {
                $tenant->addOns()->attach($request->add_ons);
            }

            // Determine initial payment status and subscription status
            $initialPaymentStatus = $paymentAmount > 0 ? ($request->payment_status ?? 'pending') : 'success';
            $subscriptionStatus = $initialPaymentStatus === 'success' ? 'active' : 'pending';

            // Create Subscription
            $tenant->subscriptions()->create([
                'plan_id' => $request->plan_id,
                'status' => $subscriptionStatus,
                'start_date' => now(),
                'end_date' => $endDate,
            ]);

            // Create Payment (Status defaults to pending, frontend handles actual payment; free plans are auto success)
            $payment = $tenant->payments()->create([
                'transaction_id' => $request->transaction_id ?? ($paymentAmount > 0 ? null : ('FREE-' . strtoupper(Str::random(10)))),
                'amount' => $paymentAmount,
                'currency' => $request->currency ?? 'INR',
                'payment_method' => $paymentAmount > 0 ? ($request->payment_method ?? 'razorpay') : 'free',
                'status' => $initialPaymentStatus,
                'type' => 'purchase',
            ]);

            // 2. Create Domain Configuration
            if ($request->has('domain_type') && $request->has('domain')) {
                Domain::create([
                    'tenant_id' => $tenant->id,
                    'client_id' => $user->id,
                    'product_id' => $tenant->product_id,
                    'type' => $request->domain_type,
                    'domain' => $request->domain,
                    'status' => 'pending',
                ]);
            }

            // Create Admin Notification
            \App\Models\AdminNotification::create([
                'type' => 'new_purchase',
                'title' => 'New Checkout Initiated',
                'message' => 'Client ' . $user->name . ' has initiated a checkout for product ID ' . $request->product_id . '.',
                'related_id' => $tenant->id,
                'client_name' => $user->name,
                'is_read' => false
            ]);

            DB::commit();

            // Send Email to Admin for Checkout Initiation
            try {
                $admin = \App\Models\User::role('SuperAdmin')->first();
                if ($admin && $admin->email) {
                    \Illuminate\Support\Facades\Mail::to($admin->email)->send(new \App\Mail\AdminNewPurchaseMail($tenant, $user));
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send admin notification email: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product checkout initiated successfully.',
                'data' => [
                    'tenant_id' => $tenant->uuid,
                    'tenant_status' => $tenant->status,
                    'payment_status' => $payment->status,
                    'amount' => $paymentAmount,
                    'invoice_number' => $payment->invoice_number,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process checkout.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
