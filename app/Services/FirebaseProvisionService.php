<?php

namespace App\Services;

use App\Models\FirebaseProject;
use App\Models\ProductFirebaseProject;
use App\Models\Tenant;

class FirebaseProvisionService
{
    /**
     * Attach this tenant to the product's Firebase project and record the
     * per-subdomain database id (tidcraft_{subdomain}).
     */
    public static function provisionFirebase(Tenant $tenant): FirebaseProject
    {
        $productFirebase = ProductFirebaseProject::where('product_id', $tenant->product_id)->first();

        if (!$productFirebase || empty($productFirebase->firebase_project_id)) {
            throw new \Exception("Product Firebase configuration is missing a project ID for product ID: {$tenant->product_id}. Save it via POST /api/products/{id}/firebase before provisioning.");
        }

        $databaseId = $tenant->provisionedDatabaseName();

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
            'status' => 'ready',
        ];

        $firebaseConfig = $tenant->firebaseProject()->firstOrNew(['tenant_id' => $tenant->id]);
        $firebaseConfig->fill($payload);
        $firebaseConfig->tenant_id = $tenant->id;
        $firebaseConfig->save();

        return $firebaseConfig->fresh();
    }
}
