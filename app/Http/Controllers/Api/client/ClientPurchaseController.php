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
            'primary_contact_email' => 'nullable|email|max:255',
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
            $paymentAmount = $plan ? (float) $plan->monthly_price : 0;
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

            if (($paymentAmount == 0 || $request->payment_status === 'success') && $request->has('domain_type') && $request->has('domain')) {
                \App\Jobs\ProvisionTenantJob::dispatch($tenant);
            }
            
            // Optionally log the provisioning action
            AuditLogger::log('Tenant Provisioned', 'New Tenant Created', "Tenant {$tenant->business_name} was provisioned.");

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
                                'currency' => $request->currency ?? 'INR',
                                'description' => 'Payment for Tenant Provisioning',
                                'customer' => $customerData,
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

            // If payment was successful and domain exists, start automatic provisioning
            $domainExists = Domain::where('tenant_id', $tenant->id)->exists();
            if ($paymentStatus === 'success' && $domainExists) {
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

            // Check if payment is successful, if so dispatch provisioning
            $payment = $tenant->payments()->latest()->first();
            if ($payment && $payment->status === 'success') {
                \App\Jobs\ProvisionTenantJob::dispatch($tenant);
            }

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
}
