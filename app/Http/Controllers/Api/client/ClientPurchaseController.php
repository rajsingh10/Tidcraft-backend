<?php

namespace App\Http\Controllers\Api\client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\Payment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\TenantDomain;
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

        // Fetch tenants linked to this user's email
        $tenants = Tenant::with(['product', 'plan', 'domain', 'subscriptions'])
            ->where('create_by', $user->id)
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
        $tenant = Tenant::with(['product', 'plan', 'domain', 'firebaseConfig', 'addOns', 'subscriptions'])
            ->where('uuid', $uuid)
            ->where('create_by', $user->id)
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
     * Display a listing of all payments made by this client.
     */
    public function payments(Request $request)
    {
        $user = $request->user();

        // First find all tenant IDs owned by the user
        $tenantIds = Tenant::where('create_by', $user->id)->pluck('id');

        // Fetch all payments associated with those tenants
        $payments = Payment::whereIn('tenant_id', $tenantIds)
            ->orderBy('created_at', 'desc')
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
        
        $validator = Validator::make($request->all(), [
            // Step 1: Client Info
            'business_name' => 'required|string|max:255',
            'primary_contact_email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:20',
            'industry' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            
            // Step 2 & 3: Product and Plan
            'product_id' => 'required|exists:products,id',
            'plan_id' => 'required|exists:plans,id',

            // Step 4: Domain Setup
            'domain_type' => 'required|in:subdomain,shared,custom',
            'domain' => 'required|string|unique:tenant_domains,domain',

            // Step 5: Firebase Setup
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

            // 1. Create Tenant
            $tenant = Tenant::create([
                'uuid' => Str::uuid()->toString(),
                'business_name' => $request->business_name,
                'primary_contact_email' => $user->email, // Always use the logged-in client's email securely
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

            // Calculate actual total amount
            $plan = \App\Models\Plan::find($request->plan_id);
            $paymentAmount = $plan ? (float) $plan->price : 0;
            if ($request->has('add_ons') && is_array($request->add_ons)) {
                $paymentAmount += (float) \App\Models\AddOn::whereIn('id', $request->add_ons)->sum('price');
            }

            // Create Subscription
            $tenant->subscriptions()->create([
                'plan_id' => $request->plan_id,
                'status' => 'active',
                'start_date' => now(),
            ]);

            // Create Payment
            if ($paymentAmount > 0) {
                $tenant->payments()->create([
                    'transaction_id' => $request->transaction_id ?? null,
                    'amount' => $paymentAmount,
                    'currency' => $request->currency ?? 'INR',
                    'payment_method' => $request->payment_method ?? 'razorpay',
                    'status' => $request->payment_status ?? 'pending',
                ]);
            }

            // 2. Create Domain Configuration
            TenantDomain::create([
                'tenant_id' => $tenant->id,
                'type' => $request->domain_type,
                'domain' => $request->domain,
                'status' => 'pending',
            ]);

            // 3. Create Firebase Configuration
            TenantFirebaseConfig::create([
                'tenant_id' => $tenant->id,
                'project_id' => $request->firebase_project_id,
                'api_key' => $request->firebase_api_key,
                'app_id' => $request->firebase_app_id,
                'auth_domain' => $request->firebase_auth_domain,
                'storage_bucket' => $request->firebase_storage_bucket,
                'messaging_sender_id' => $request->firebase_messaging_sender_id,
                'database_url' => $request->firebase_database_url,
            ]);

            DB::commit();
            
            // Optionally log the provisioning action
            AuditLogger::log('Tenant Provisioned', 'New Tenant Created', "Tenant {$tenant->business_name} was provisioned.");

            // Generate Razorpay Payment Link
            $paymentLinkStr = null;

            if ($paymentAmount > 0) {
                $razorpaySettings = \App\Models\Setting::whereIn('key', ['razorpay_key_id', 'razorpay_key_secret', 'razorpay_active'])->pluck('value', 'key')->toArray();
                
                if (isset($razorpaySettings['razorpay_active']) && $razorpaySettings['razorpay_active'] === 'true') {
                    $keyId = $razorpaySettings['razorpay_key_id'] ?? null;
                    $keySecret = $razorpaySettings['razorpay_key_secret'] ?? null;

                    if ($keyId && $keySecret) {
                        try {
                            $api = new \Razorpay\Api\Api($keyId, $keySecret);
                            
                            $paymentLinkData = [
                                'amount' => (int) ($paymentAmount * 100), // convert to paise
                                'currency' => $request->currency ?? 'INR',
                                'description' => 'Payment for Tenant Provisioning',
                                'customer' => [
                                    'name' => $tenant->business_name,
                                    'email' => $tenant->primary_contact_email,
                                    'contact' => $tenant->phone_number ?? ''
                                ],
                                'notify' => ['email' => true, 'sms' => true],
                                'reminder_enable' => true,
                            ];
                            
                            $paymentLinkResponse = $api->paymentLink->create($paymentLinkData);
                            $paymentLinkStr = $paymentLinkResponse->short_url;
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Razorpay Payment Link Error: ' . $e->getMessage());
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Tenant provisioned successfully.',
                'data' => [
                    'tenant_id' => $tenant->uuid,
                    'payment_link' => $paymentLinkStr,
                    'amount' => $paymentAmount
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
}
