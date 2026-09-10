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
        if (!empty($serviceAccount['project_id']) && $serviceAccount['project_id'] !== $productFirebase->firebase_project_id) {
            throw new \Exception("Service account project_id ({$serviceAccount['project_id']}) does not match the selected Firebase project ({$productFirebase->firebase_project_id}).");
        }

        try {
            (new FirebaseAdminClient())->createFirestoreDatabase($serviceAccount, $databaseId, $locationId);
            $firebaseConfig->update([
                'firebase_project_id' => $projectId,
                'firebase_database_id' => $databaseId,
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
