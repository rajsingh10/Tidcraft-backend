<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\FirebaseAdminClient;

class ImportFirestoreCollectionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 minutes max execution time for large imports
    
    protected $serviceAccount;
    protected $databaseId;
    protected $collectionPath;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $serviceAccount, string $databaseId, string $collectionPath)
    {
        $this->serviceAccount = $serviceAccount;
        $this->databaseId = $databaseId;
        $this->collectionPath = $collectionPath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!file_exists($this->collectionPath)) {
            Log::warning("Import job failed: File not found {$this->collectionPath}");
            return;
        }

        $data = json_decode(file_get_contents($this->collectionPath), true);
        if (!$data || !isset($data['__collections__'])) {
            Log::warning("Import job failed: Invalid JSON format in {$this->collectionPath}");
            return;
        }

        $adminClient = new FirebaseAdminClient();
        $projectId = $this->serviceAccount['project_id'] ?? null;
        if (!$projectId) return;

        $accessToken = $adminClient->accessToken($this->serviceAccount, [
            'https://www.googleapis.com/auth/datastore',
            'https://www.googleapis.com/auth/cloud-platform',
        ]);

        $writes = [];
        $this->extractDocuments($data['__collections__'], '', $projectId, $this->databaseId, $writes);

        // Chunk writes into batches of 500 (Google Cloud Firestore limit per commit)
        $chunks = array_chunk($writes, 500);

        foreach ($chunks as $index => $batch) {
            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/{$this->databaseId}/documents:commit";
            
            $payload = [
                'writes' => $batch
            ];

            $response = Http::withToken($accessToken)
                ->timeout(60)
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::error("Failed to commit batch {$index} to Firestore: " . $response->body());
            }
        }
        
        Log::info("Successfully imported " . count($writes) . " documents to {$this->databaseId}");
    }

    /**
     * Recursively extract documents and convert to Firestore REST format
     */
    protected function extractDocuments(array $collections, string $parentPath, string $projectId, string $databaseId, array &$writes)
    {
        foreach ($collections as $collectionId => $documents) {
            foreach ($documents as $docId => $docData) {
                // If it's a nested collection, it's under '__collections__'
                $nestedCollections = null;
                if (isset($docData['__collections__'])) {
                    $nestedCollections = $docData['__collections__'];
                    unset($docData['__collections__']);
                }

                $docPath = $parentPath ? "{$parentPath}/{$collectionId}/{$docId}" : "{$collectionId}/{$docId}";

                // Convert JSON values to Firestore Document REST format
                $fields = $this->convertToFirestoreFields($docData);

                $writes[] = [
                    'update' => [
                        'name' => "projects/{$projectId}/databases/{$databaseId}/documents/{$docPath}",
                        'fields' => $fields
                    ]
                ];

                if ($nestedCollections) {
                    $this->extractDocuments($nestedCollections, $docPath, $projectId, $databaseId, $writes);
                }
            }
        }
    }

    /**
     * Helper to map standard JSON types to Firestore REST types
     */
    protected function convertToFirestoreFields(array $data)
    {
        $fields = [];
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $fields[$key] = ['nullValue' => null];
            } elseif (is_bool($value)) {
                $fields[$key] = ['booleanValue' => $value];
            } elseif (is_int($value)) {
                $fields[$key] = ['integerValue' => (string) $value];
            } elseif (is_float($value)) {
                $fields[$key] = ['doubleValue' => $value];
            } elseif (is_array($value)) {
                // Check if sequential array (list) or associative array (map)
                if (array_keys($value) === range(0, count($value) - 1) && !empty($value)) {
                    $arrayElements = [];
                    foreach ($value as $item) {
                        $converted = $this->convertToFirestoreFields(['x' => $item]);
                        $arrayElements[] = $converted['x'];
                    }
                    $fields[$key] = ['arrayValue' => ['values' => $arrayElements]];
                } else if (empty($value)) {
                    $fields[$key] = ['arrayValue' => ['values' => []]];
                } else {
                    $fields[$key] = ['mapValue' => ['fields' => $this->convertToFirestoreFields($value)]];
                }
            } else {
                $fields[$key] = ['stringValue' => (string) $value];
            }
        }
        return $fields;
    }
}
