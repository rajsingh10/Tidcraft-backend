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

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
