<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use App\Models\TenantApp;
use App\Models\ProductFirebaseProject;
use App\Services\FirebaseAdminClient;

class TenantAppController extends Controller
{
    /**
     * GET /api/tenants/{uuid}/apps
     * List all apps for a tenant.
     */
    public function index($uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();
        if (!$tenant) return response()->json(['status' => 'error', 'message' => 'Tenant not found'], 404);

        return response()->json([
            'status' => 'success',
            'data' => $tenant->apps()->get()
        ]);
    }

    /**
     * GET /api/apps
     * List all apps across all tenants with their tenant details.
     */
    public function getAllApps()
    {
        // Load the apps with their associated tenant and product details
        $apps = TenantApp::with(['tenant:id,uuid,business_name,tenant_key,product_id', 'tenant.product:id,name'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $apps
        ]);
    }

    /**
     * GET /api/products/{productId}/apps
     * List all apps across all tenants that belong to a specific product.
     */
    public function getByProduct($productId)
    {
        $apps = TenantApp::whereHas('tenant', function ($query) use ($productId) {
            $query->where('product_id', $productId);
        })->with('tenant:id,uuid,business_name,tenant_key')->get();

        return response()->json([
            'status' => 'success',
            'data' => $apps
        ]);
    }

    /**
     * POST /api/tenants/{uuid}/apps
     * Store new apps for an existing tenant.
     */
    public function store(Request $request, $uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();
        if (!$tenant) return response()->json(['status' => 'error', 'message' => 'Tenant not found'], 404);

        $validator = Validator::make($request->all(), [
            'admin_email' => 'nullable|email',
            'admin_password' => 'nullable|string',
            'apps' => 'required|array',
            'apps.*.app_name' => 'required|string',
            'apps.*.email' => 'nullable|email',
            'apps.*.password' => 'nullable|string',
            'apps.*.apk_url' => 'nullable|array',
            'apps.*.web_url' => 'nullable|array',
            'apps.*.web_url.*' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $appsData = $request->all()['apps'] ?? [];
        $adminEmail = $request->input('admin_email');
        $adminPassword = $request->input('admin_password');

        // Handle file uploads if apk_url contains files
        foreach ($appsData as &$appData) {
            if (!isset($appData['email']) && $adminEmail) {
                $appData['email'] = $adminEmail;
            }
            if (!isset($appData['password']) && $adminPassword) {
                $appData['password'] = $adminPassword;
            }

            if (isset($appData['apk_url']) && is_array($appData['apk_url'])) {
                foreach ($appData['apk_url'] as $key => $value) {
                    if ($value instanceof \Illuminate\Http\UploadedFile) {
                        $path = $value->store("apk_files/{$uuid}", 'public');
                        $appData['apk_url'][$key] = url('storage/' . $path);
                    }
                }
            }
        }

        $tenant->apps()->createMany($appsData);

        return response()->json([
            'status' => 'success',
            'message' => 'Tenant apps saved successfully.',
            'data' => $tenant->apps()->get()
        ], 201);
    }

    /**
     * GET /api/tenants/{uuid}/apps/{appId}
     * View a specific app.
     */
    public function show($uuid, $appId)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();
        if (!$tenant) return response()->json(['status' => 'error', 'message' => 'Tenant not found'], 404);

        $app = $tenant->apps()->find($appId);
        if (!$app) return response()->json(['status' => 'error', 'message' => 'App not found'], 404);

        return response()->json([
            'status' => 'success',
            'data' => $app
        ]);
    }

    /**
     * PUT /api/tenants/{uuid}/apps/{appId}
     * Update an app and sync credentials to Firebase.
     */
    public function update(Request $request, $uuid, $appId)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();
        if (!$tenant) return response()->json(['status' => 'error', 'message' => 'Tenant not found'], 404);

        $app = $tenant->apps()->find($appId);
        if (!$app) return response()->json(['status' => 'error', 'message' => 'App not found'], 404);

        $validator = Validator::make($request->all(), [
            'app_name' => 'sometimes|required|string',
            'admin_email' => 'nullable|email',
            'admin_password' => 'nullable|string',
            'email' => 'nullable|email',
            'password' => 'nullable|string',
            'apk_url' => 'nullable|array',
            'web_url' => 'nullable|array',
            'web_url.*' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $updateData = $request->all();

        // Map admin credentials to app credentials if they are provided
        if ($request->filled('admin_email') && empty($updateData['email'])) {
            $updateData['email'] = $request->input('admin_email');
        }
        if ($request->filled('admin_password') && empty($updateData['password'])) {
            $updateData['password'] = $request->input('admin_password');
            // Ensure the password check later picks this up
            $request->merge(['password' => $updateData['password']]);
        }
        if ($request->filled('admin_email')) {
             $request->merge(['email' => $updateData['email']]);
        }

        // Handle file uploads for apk_url update
        if (isset($updateData['apk_url']) && is_array($updateData['apk_url'])) {
            $currentApkUrl = is_array($app->apk_url) ? $app->apk_url : [];
            
            foreach ($updateData['apk_url'] as $key => $value) {
                if ($value instanceof \Illuminate\Http\UploadedFile) {
                    $path = $value->store("apk_files/{$uuid}", 'public');
                    $currentApkUrl[$key] = url('storage/' . $path);
                } elseif (is_string($value) && !empty($value)) {
                    $currentApkUrl[$key] = $value;
                }
            }
            $updateData['apk_url'] = $currentApkUrl;
        }

        $oldEmail = $app->email;
        
        $app->update($updateData);

        // Sync to Firebase if credentials were changed
        if (($request->filled('password') || $request->filled('email')) && $app->email) {
            $this->updateFirebaseCredentials($tenant, $oldEmail ?: $app->email, $app->email, $request->input('password'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Tenant app updated successfully.',
            'data' => $app
        ]);
    }

    /**
     * DELETE /api/tenants/{uuid}/apps/{appId}
     * Delete an app.
     */
    public function destroy($uuid, $appId)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();
        if (!$tenant) return response()->json(['status' => 'error', 'message' => 'Tenant not found'], 404);

        $app = $tenant->apps()->find($appId);
        if (!$app) return response()->json(['status' => 'error', 'message' => 'App not found'], 404);

        $app->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Tenant app deleted successfully.'
        ]);
    }

    /**
     * Helper to update Firebase auth credentials.
     */
    protected function updateFirebaseCredentials(Tenant $tenant, string $oldEmail, string $newEmail, ?string $newPassword)
    {
        try {
            $productFirebase = ProductFirebaseProject::where('product_id', $tenant->product_id)->first();
            if (!$productFirebase || empty($productFirebase->service_account_json)) {
                return; // Firebase not configured
            }

            $serviceAccount = json_decode($productFirebase->service_account_json, true);
            if (!$serviceAccount) return;

            $adminClient = new FirebaseAdminClient();
            
            $projectId = $serviceAccount['project_id'] ?? null;
            if (!$projectId) return;

            $accessToken = $adminClient->accessToken($serviceAccount, [
                'https://www.googleapis.com/auth/identitytoolkit',
                'https://www.googleapis.com/auth/cloud-platform',
            ]);

            // Lookup the user by old email
            $lookupUrl = 'https://identitytoolkit.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/accounts:lookup';
            $lookupResponse = Http::withToken($accessToken)
                ->post($lookupUrl, ['email' => [$oldEmail]]);
                
            $users = $lookupResponse->json('users');

            if (empty($users)) {
                // User doesn't exist, create them if we have a password
                if ($newPassword) {
                    $adminClient->createAuthUser($serviceAccount, $newEmail, $newPassword);
                }
                return;
            }

            $localId = $users[0]['localId'];
            
            // Update the user
            $updateData = ['localId' => $localId];
            if ($newEmail !== $oldEmail) {
                $updateData['email'] = $newEmail;
            }
            if ($newPassword) {
                $updateData['password'] = $newPassword;
            }

            $updateUrl = 'https://identitytoolkit.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/accounts:update';
            
            $updateResponse = Http::withToken($accessToken)->post($updateUrl, $updateData);
            
            if (!$updateResponse->successful()) {
                Log::warning("Firebase account update failed for $newEmail: " . $updateResponse->body());
            }

        } catch (\Exception $e) {
            Log::error("Failed to sync Firebase credentials: " . $e->getMessage());
        }
    }
}
