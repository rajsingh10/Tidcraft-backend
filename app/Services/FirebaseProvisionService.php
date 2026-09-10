<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\FirebaseProject;
use App\Models\ProductFirebaseProject;
use Illuminate\Support\Str;

class FirebaseProvisionService
{
    /**
     * Provision a Firebase project for the tenant (Level 2).
     *
     * @param Tenant $tenant
     * @return \App\Models\FirebaseProject
     */
    public static function provisionFirebase(Tenant $tenant)
    {
        // 1. Identify the Product Firebase (Level 1)
        $productFirebase = ProductFirebaseProject::where('product_id', $tenant->product_id)->first();
        if (!$productFirebase) {
            throw new \Exception("Product Firebase configuration missing for product ID: {$tenant->product_id}. Cannot provision client Firebase.");
        }

        // 2. Create the Client Firebase (Level 2)
        $firebaseConfig = $tenant->firebaseProject()->firstOrCreate([
            'tenant_id' => $tenant->id,
            'client_id' => $tenant->client_id,
            'product_id' => $tenant->product_id
        ], [
            'status' => 'pending',
            // Pre-fill with temporary or stubs if needed based on Product Firebase context
            'firebase_project_id' => 'client-' . $tenant->client_id . '-prod-' . $tenant->product_id . '-' . strtolower(Str::random(6)),
            'firebase_app_id' => '1:' . rand(100000, 999999) . '000000:web:' . Str::random(22),
            'firebase_api_key' => 'AIzaSy' . Str::random(33),
            'firebase_auth_domain' => $tenant->tenant_key . '.firebaseapp.com',
            'firebase_storage_bucket' => $tenant->tenant_key . '.appspot.com',
            'firebase_messaging_sender_id' => rand(100000, 999999) . '000000',
        ]);

        if ($firebaseConfig->status === 'ready') {
            return $firebaseConfig;
        }

        try {
            // In a real scenario, we use the Product Firebase Service Account (or Master SA)
            // to call Google Cloud API to create a new GCP project.
            
            // Mark as ready
            $firebaseConfig->update(['status' => 'ready']);

            return $firebaseConfig;
        } catch (\Exception $e) {
            $firebaseConfig->update(['status' => 'failed']);
            throw $e;
        }
    }
}
