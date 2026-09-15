<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FirebaseAdminClient
{
    /**
     * Create a named Firestore database inside the Firebase/GCP project.
     * Database IDs must be lowercase letters, numbers, and hyphens (e.g. tidcraft-acme).
     */
    public function createFirestoreDatabase(array $serviceAccount, string $databaseId, string $locationId): void
    {
        $projectId = $serviceAccount['project_id'] ?? null;
        if (!$projectId) {
            throw new \Exception('Firebase service account JSON is missing project_id.');
        }

        $accessToken = $this->accessToken($serviceAccount, [
            'https://www.googleapis.com/auth/datastore',
            'https://www.googleapis.com/auth/cloud-platform',
        ]);

        $url = 'https://firestore.googleapis.com/v1/projects/' . rawurlencode($projectId)
            . '/databases?databaseId=' . rawurlencode($databaseId);

        // Enterprise requires at least one data-access mode. Native = Firestore on, MongoDB off.
        $response = Http::withToken($accessToken)
            ->timeout(60)
            ->post($url, [
                'type' => 'FIRESTORE_NATIVE',
                'locationId' => $locationId,
                'databaseEdition' => 'ENTERPRISE',
                'firestoreDataAccessMode' => 'DATA_ACCESS_MODE_ENABLED',
                'mongodbCompatibleDataAccessMode' => 'DATA_ACCESS_MODE_DISABLED',
                'realtimeUpdatesMode' => 'REALTIME_UPDATES_MODE_ENABLED',
            ]);

        if ($response->status() === 409) {
            return;
        }

        $error = $response->json('error.message') ?? $response->body();

        if ($response->successful()) {
            $this->waitForOperation($accessToken, $response->json('name'));
            return;
        }

        if ($response->status() === 403 || str_contains(strtolower((string) $error), 'permission')) {
            $email = $serviceAccount['client_email'] ?? 'the service account';
            throw new \Exception(
                "Failed to create Firestore database '{$databaseId}' in project '{$projectId}': The service account {$email} does not have permission. In Google Cloud Console open project {$projectId} → IAM → grant this service account the role \"Cloud Datastore Owner\" (roles/datastore.owner). Also enable the Cloud Firestore API for this project. Then retry POST /api/tenant-provision."
            );
        }

        throw new \Exception("Failed to create Firestore database '{$databaseId}' in project '{$projectId}': {$error}");
    }

    public function createIdentityTenant(array $serviceAccount, string $displayName): string
    {
        $projectId = $serviceAccount['project_id'] ?? null;
        if (!$projectId) {
            throw new \Exception('Firebase service account JSON is missing project_id.');
        }

        $accessToken = $this->accessToken($serviceAccount, [
            'https://www.googleapis.com/auth/cloud-platform',
        ]);

        $url = 'https://identitytoolkit.googleapis.com/v2/projects/' . rawurlencode($projectId) . '/tenants';

        $response = Http::withToken($accessToken)
            ->timeout(30)
            ->post($url, [
                'displayName' => $displayName,
                'allowPasswordSignup' => true,
                'enableEmailLinkSignin' => false,
            ]);

        if ($response->successful()) {
            return $response->json('name'); // e.g. "projects/12345/tenants/tenant-abcd"
        }

        $error = $response->json('error.message') ?? $response->body();
        throw new \Exception("Failed to create Identity Platform Tenant '{$displayName}' in project '{$projectId}': {$error}");
    }

    /**
     * Copy composite indexes from a source database to a target database.
     */
    public function copyIndexes(array $serviceAccount, string $sourceDatabaseId, string $targetDatabaseId): void
    {
        $projectId = $serviceAccount['project_id'] ?? null;
        if (!$projectId) {
            return;
        }

        $accessToken = $this->accessToken($serviceAccount, [
            'https://www.googleapis.com/auth/datastore',
            'https://www.googleapis.com/auth/cloud-platform',
        ]);

        // 1. Fetch indexes from source
        $listUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/{$sourceDatabaseId}/collectionGroups/-/indexes";
        $listResponse = Http::withToken($accessToken)->get($listUrl);
        
        if (!$listResponse->successful()) {
            \Illuminate\Support\Facades\Log::warning("Failed to fetch indexes from source database {$sourceDatabaseId}: " . $listResponse->body());
            return;
        }

        $indexes = $listResponse->json('indexes') ?? [];

        // 2. Recreate each index on the target
        foreach ($indexes as $index) {
            // Index name looks like: projects/{projectId}/databases/{databaseId}/collectionGroups/{collectionId}/indexes/{indexId}
            $nameParts = explode('/', $index['name']);
            $collectionId = null;
            
            // Find collectionId which comes right after 'collectionGroups'
            foreach ($nameParts as $i => $part) {
                if ($part === 'collectionGroups' && isset($nameParts[$i + 1])) {
                    $collectionId = $nameParts[$i + 1];
                    break;
                }
            }

            if (!$collectionId) continue;

            $createUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/{$targetDatabaseId}/collectionGroups/{$collectionId}/indexes";
            
            $payload = [
                'queryScope' => $index['queryScope'] ?? 'COLLECTION',
                'fields' => $index['fields'] ?? []
            ];

            // Send async POST request to create index (they take time to build, we just trigger them)
            $createResponse = Http::withToken($accessToken)->post($createUrl, $payload);
            
            if (!$createResponse->successful() && $createResponse->status() !== 409) { // 409 means already exists
                \Illuminate\Support\Facades\Log::warning("Failed to create index on {$targetDatabaseId} for {$collectionId}: " . $createResponse->body());
            }
        }
    }

    /**
     * Set default public read/write security rules for the given database.
     */
    public function setDefaultSecurityRules(array $serviceAccount, string $databaseId): void
    {
        $projectId = $serviceAccount['project_id'] ?? null;
        if (!$projectId) {
            return;
        }

        $accessToken = $this->accessToken($serviceAccount, [
            'https://www.googleapis.com/auth/firebase',
            'https://www.googleapis.com/auth/cloud-platform',
        ]);

        $rulesUrl = "https://firebaserules.googleapis.com/v1/projects/{$projectId}/rulesets";
        $rulesContent = "rules_version = '2';\nservice cloud.firestore {\n  match /databases/{database}/documents {\n    match /{document=**} {\n      allow read, write: if true;\n    }\n  }\n}";

        $rulesetResponse = Http::withToken($accessToken)->post($rulesUrl, [
            'source' => [
                'files' => [
                    [
                        'name' => 'firestore.rules',
                        'content' => $rulesContent
                    ]
                ]
            ]
        ]);

        if (!$rulesetResponse->successful()) {
            \Illuminate\Support\Facades\Log::warning("Failed to create ruleset for {$databaseId}: " . $rulesetResponse->body());
            return;
        }

        $rulesetName = $rulesetResponse->json('name');
        $releaseName = "projects/{$projectId}/releases/cloud.firestore/{$databaseId}";
        $releaseUrl = "https://firebaserules.googleapis.com/v1/{$releaseName}";

        $getRelease = Http::withToken($accessToken)->get($releaseUrl);

        if ($getRelease->successful()) {
            $updateResponse = Http::withToken($accessToken)->patch($releaseUrl, [
                'rulesetName' => $rulesetName
            ]);
            if (!$updateResponse->successful()) {
                \Illuminate\Support\Facades\Log::warning("Failed to update release for {$databaseId}: " . $updateResponse->body());
            }
        } else {
            $createUrl = "https://firebaserules.googleapis.com/v1/projects/{$projectId}/releases";
            $createResponse = Http::withToken($accessToken)->post($createUrl, [
                'name' => $releaseName,
                'rulesetName' => $rulesetName
            ]);
            if (!$createResponse->successful()) {
                \Illuminate\Support\Facades\Log::warning("Failed to create release for {$databaseId}: " . $createResponse->body());
            }
        }
    }

    private function waitForOperation(string $accessToken, ?string $operationName): void
    {
        if (!$operationName) {
            return;
        }

        $deadline = time() + 60;
        while (time() < $deadline) {
            $operation = Http::withToken($accessToken)
                ->timeout(30)
                ->get('https://firestore.googleapis.com/v1/' . ltrim($operationName, '/'));

            if ($operation->json('done') === true) {
                if ($operation->json('error')) {
                    throw new \Exception('Firestore create operation failed: ' . json_encode($operation->json('error')));
                }
                return;
            }

            sleep(2);
        }
    }

    public function accessToken(array $serviceAccount, array $scopes): string
    {
        $clientEmail = $serviceAccount['client_email'] ?? null;
        $privateKey = $serviceAccount['private_key'] ?? null;
        $tokenUri = $serviceAccount['token_uri'] ?? 'https://oauth2.googleapis.com/token';

        if (!$clientEmail || !$privateKey) {
            throw new \Exception('Firebase service account JSON must include client_email and private_key.');
        }

        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => implode(' ', $scopes),
            'aud' => $tokenUri,
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $unsigned = $header . '.' . $claims;
        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            throw new \Exception('Invalid Firebase service account private key.');
        }

        openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256);
        $jwt = $unsigned . '.' . $this->base64UrlEncode($signature);

        $response = Http::asForm()->timeout(30)->post($tokenUri, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        $accessToken = $response->json('access_token');
        if (!$response->successful() || !$accessToken) {
            throw new \Exception('Failed to authenticate Firebase service account: ' . ($response->json('error_description') ?? $response->body()));
        }

        return $accessToken;
    }

    public function createAuthUser(array $serviceAccount, string $email, string $password): void
    {
        $projectId = $serviceAccount['project_id'] ?? null;
        if (!$projectId) {
            throw new \Exception('Firebase service account JSON is missing project_id.');
        }

        $accessToken = $this->accessToken($serviceAccount, [
            'https://www.googleapis.com/auth/identitytoolkit',
            'https://www.googleapis.com/auth/cloud-platform',
        ]);

        $url = 'https://identitytoolkit.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/accounts';

        $response = Http::withToken($accessToken)
            ->timeout(30)
            ->post($url, [
                'email' => $email,
                'password' => $password,
            ]);

        if (!$response->successful()) {
            $error = $response->json('error.message') ?? $response->body();
            if (str_contains($error, 'EMAIL_EXISTS')) {
                \Illuminate\Support\Facades\Log::warning("Firebase Auth user {$email} already exists. Skipping creation.");
            } else {
                throw new \Exception("Failed to create Firebase Auth user '{$email}': {$error}");
            }
        }
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
