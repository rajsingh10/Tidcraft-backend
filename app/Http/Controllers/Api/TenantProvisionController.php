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

            // Step 5: Firebase Setup
            'firebase_project_id' => 'nullable|string',
            'firebase_api_key' => 'nullable|string',
            'firebase_app_id' => 'nullable|string',
            'firebase_auth_domain' => 'nullable|string',
            'firebase_storage_bucket' => 'nullable|string',
            'firebase_messaging_sender_id' => 'nullable|string',
            'firebase_database_url' => 'nullable|string|url',
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

            return response()->json([
                'status' => 'success',
                'message' => 'Tenant provisioned successfully.',
                'data' => [
                    'tenant_id' => $tenant->uuid,
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
