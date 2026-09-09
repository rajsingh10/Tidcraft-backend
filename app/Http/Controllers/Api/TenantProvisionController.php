<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\TenantFirebaseConfig;
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
            'business_name' => 'required|string|max:255',
            'primary_contact_email' => 'required|email|max:255',
            'phone_number' => 'nullable|string|max:20',
            'industry' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            
            // Step 2 & 3: Product and Plan
            'product_id' => 'required|exists:products,id',
            'plan_id' => 'required|exists:plans,id',

            // Step 4: Domain Setup
            'domain_type' => 'required|in:subdomain,shared,custom',
            'domain' => 'required|string|unique:tenant_domains,domain',



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

    /**
     * Display a listing of tenants.
     */
    public function index()
    {
        $tenants = Tenant::with(['product', 'plan', 'domain', 'addOns', 'subscriptions', 'payments'])->get();
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
        $tenant = Tenant::with(['product', 'plan', 'domain', 'firebaseConfig', 'addOns', 'subscriptions', 'payments'])->where('uuid', $uuid)->first();

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
            'domain' => 'nullable|string|unique:tenant_domains,domain,' . ($tenant->domain ? $tenant->domain->id : 'NULL') . ',id',

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

            $tenant->update($request->only([
                'business_name', 'primary_contact_email', 'phone_number', 'address', 'industry', 'product_id', 'plan_id', 'status'
            ]));

            // Sync Add-ons
            if ($request->has('add_ons') && is_array($request->add_ons)) {
                $tenant->addOns()->sync($request->add_ons);
            }

            if ($request->hasAny(['domain_type', 'domain'])) {
                $domainData = [];
                if ($request->has('domain_type')) $domainData['type'] = $request->domain_type;
                if ($request->has('domain')) $domainData['domain'] = $request->domain;
                
                if ($tenant->domain) {
                    $tenant->domain->update($domainData);
                } else {
                    $domainData['tenant_id'] = $tenant->id;
                    $domainData['status'] = 'pending';
                    TenantDomain::create($domainData);
                }
            }

            if ($request->hasAny(['firebase_project_id', 'firebase_api_key', 'firebase_app_id', 'firebase_auth_domain', 'firebase_storage_bucket', 'firebase_messaging_sender_id', 'firebase_database_url'])) {
                $firebaseData = [];
                if ($request->has('firebase_project_id')) $firebaseData['project_id'] = $request->firebase_project_id;
                if ($request->has('firebase_api_key')) $firebaseData['api_key'] = $request->firebase_api_key;
                if ($request->has('firebase_app_id')) $firebaseData['app_id'] = $request->firebase_app_id;
                if ($request->has('firebase_auth_domain')) $firebaseData['auth_domain'] = $request->firebase_auth_domain;
                if ($request->has('firebase_storage_bucket')) $firebaseData['storage_bucket'] = $request->firebase_storage_bucket;
                if ($request->has('firebase_messaging_sender_id')) $firebaseData['messaging_sender_id'] = $request->firebase_messaging_sender_id;
                if ($request->has('firebase_database_url')) $firebaseData['database_url'] = $request->firebase_database_url;

                if ($tenant->firebaseConfig) {
                    $tenant->firebaseConfig->update($firebaseData);
                } else {
                    $firebaseData['tenant_id'] = $tenant->id;
                    TenantFirebaseConfig::create($firebaseData);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Tenant updated successfully.',
                'data' => $tenant->fresh(['domain', 'firebaseConfig'])
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
            if ($tenant->domain) {
                $tenant->domain->delete();
            }
            if ($tenant->firebaseConfig) {
                $tenant->firebaseConfig->delete();
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

            // If payment was successful, activate the tenant and subscription
            if ($paymentStatus === 'success') {
                $tenant->update(['status' => 'active']);
                
                $subscription = $tenant->subscriptions()->first();
                if ($subscription) {
                    $subscription->update(['status' => 'active']);
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
}
