<?php

namespace App\Services;

use App\Models\FirebaseProject;
use App\Models\ProductFirebaseProject;
use App\Models\Tenant;

class FirebaseProvisionService
{
    /**
     * Attach this tenant to the product's Firebase project and create a
     * named Firestore database (tidcraft-{subdomain}) inside that project.
     */
    public static function provisionFirebase(Tenant $tenant): FirebaseProject
    {
        $productFirebase = ProductFirebaseProject::where('product_id', $tenant->product_id)->first();

        if (!$productFirebase || empty($productFirebase->firebase_project_id)) {
            throw new \Exception("Product Firebase configuration is missing a project ID for product ID: {$tenant->product_id}. Save it via POST /api/products/{id}/firebase before provisioning.");
        }

        $databaseId = $tenant->firestoreDatabaseId();
        $locationId = $productFirebase->firebase_location_id ?: config('services.firebase.location_id', 'nam5');

        $payload = [
            'client_id' => $tenant->client_id,
            'product_id' => $tenant->product_id,
            'firebase_project_id' => $productFirebase->firebase_project_id,
            'firebase_project_name' => $productFirebase->firebase_project_name,
            'firebase_app_id' => $productFirebase->firebase_app_id,
            'firebase_api_key' => $productFirebase->firebase_api_key,
            'firebase_auth_domain' => $productFirebase->firebase_auth_domain,
            'firebase_storage_bucket' => $productFirebase->firebase_storage_bucket,
            'firebase_messaging_sender_id' => $productFirebase->firebase_messaging_sender_id,
            'firebase_database_id' => $databaseId,
            'status' => 'pending',
        ];

        $firebaseConfig = $tenant->firebaseProject()->firstOrNew(['tenant_id' => $tenant->id]);
        $firebaseConfig->fill($payload);
        $firebaseConfig->tenant_id = $tenant->id;
        $firebaseConfig->save();

        $serviceAccount = self::decodeServiceAccount($productFirebase->service_account_json);
        if (!$serviceAccount) {
            throw new \Exception('Product Firebase service account JSON is missing. Upload it via POST /api/products/{id}/firebase as service_account_json (Firebase Console → Project settings → Service accounts → Generate new private key).');
        }

        $projectId = $serviceAccount['project_id'] ?? $productFirebase->firebase_project_id;

        try {
            $adminClient = new FirebaseAdminClient();
            
            // 1. Create Firestore Database
            $adminClient->createFirestoreDatabase($serviceAccount, $databaseId, $locationId);

            // 1.5 Setup Indexes from local JSON based on product
            $product = \App\Models\Product::find($tenant->product_id);
            if ($product) {
                // Determine folder name (e.g. "Food App" -> "food-app")
                $productSlug = \Illuminate\Support\Str::slug($product->name);
                $indexPath = public_path("collection/{$productSlug}/firestore_indexes.json");
                
                if (file_exists($indexPath)) {
                    $indexData = json_decode(file_get_contents($indexPath), true);
                    if (isset($indexData['indexes']) && is_array($indexData['indexes'])) {
                        $adminClient->createIndexesFromJson($serviceAccount, $databaseId, $indexData['indexes']);
                        \Illuminate\Support\Facades\Log::info("Successfully triggered index creation for {$databaseId} from {$indexPath}");
                    }
                } else {
                    \Illuminate\Support\Facades\Log::warning("No firestore_indexes.json found for product {$product->name} at {$indexPath}");
                }
            } else {
                \Illuminate\Support\Facades\Log::warning("Product not found for tenant {$tenant->id}, skipping indexes.");
            }
            // 1.6 Set default public security rules
            $adminClient->setDefaultSecurityRules($serviceAccount, $databaseId);
            
            // 2. Create GCIP Authentication Tenant
            // display_name must start with a letter, only contain letters/digits/hyphens, and be 4-20 chars.
            $tenantDisplayName = preg_replace('/[^a-zA-Z0-9-]/', '-', $tenant->business_name ?? $databaseId);
            $tenantDisplayName = trim(preg_replace('/-+/', '-', $tenantDisplayName), '-');
            if (!preg_match('/^[a-zA-Z]/', $tenantDisplayName)) {
                $tenantDisplayName = 't-' . $tenantDisplayName;
            }
            $tenantDisplayName = substr($tenantDisplayName, 0, 20);
            $tenantDisplayName = str_pad($tenantDisplayName, 4, '0');

            $gcipTenantPath = $adminClient->createIdentityTenant($serviceAccount, $tenantDisplayName);
            
            // The API returns the resource name e.g., "projects/12345/tenants/tenant-abcd"
            $parts = explode('/', $gcipTenantPath);
            $gcipTenantId = end($parts);

            $firebaseConfig->update([
                'firebase_project_id' => $projectId,
                'firebase_database_id' => $databaseId,
                'firebase_tenant_id' => $gcipTenantId,
                'status' => 'ready',
            ]);
        } catch (\Exception $e) {
            $firebaseConfig->update(['status' => 'failed']);
            throw $e;
        }

        return $firebaseConfig->fresh();
    }

    private static function decodeServiceAccount(?string $json): ?array
    {
        if (!$json) {
            return null;
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded) || empty($decoded['private_key']) || empty($decoded['client_email'])) {
            return null;
        }

        return $decoded;
    }
}
