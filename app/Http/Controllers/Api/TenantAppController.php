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
     * POST /api/tenants/{uuid}/apps
     * Store new apps for an existing tenant.
     */
    public function store(Request $request, $uuid)
    {
        $tenant = Tenant::where('uuid', $uuid)->first();
        if (!$tenant) return response()->json(['status' => 'error', 'message' => 'Tenant not found'], 404);

        $validator = Validator::make($request->all(), [
            'apps' => 'required|array',
            'apps.*.app_name' => 'required|string',
            'apps.*.email' => 'nullable|email',
            'apps.*.password' => 'nullable|string',
            'apps.*.apk_url' => 'nullable|array',
            'apps.*.apk_url.*' => 'url',
            'apps.*.web_url' => 'nullable|array',
            'apps.*.web_url.*' => 'url',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $tenant->apps()->createMany($request->apps);

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
            'email' => 'nullable|email',
            'password' => 'nullable|string',
            'apk_url' => 'nullable|array',
            'apk_url.*' => 'url',
            'web_url' => 'nullable|array',
            'web_url.*' => 'url',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $oldEmail = $app->email;
        
        $app->update($request->all());

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
