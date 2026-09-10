<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirestoreImporter
{
    private string $accessToken;
    private string $projectId;
    private string $databaseId;

    public function __construct(array $serviceAccount, string $databaseId)
    {
        $this->projectId = $serviceAccount['project_id'];
        $this->databaseId = $databaseId;
        $this->accessToken = (new FirebaseAdminClient())->accessToken($serviceAccount, [
            'https://www.googleapis.com/auth/datastore',
            'https://www.googleapis.com/auth/cloud-platform',
        ]);
    }

    public function import(array $data)
    {
        $collections = $data['__collections__'] ?? [];
        $this->processCollections($collections, "projects/{$this->projectId}/databases/{$this->databaseId}/documents");
    }

    private function processCollections(array $collections, string $parentPath)
    {
        foreach ($collections as $collectionId => $documents) {
            foreach ($documents as $docId => $docData) {
                $subCollections = $docData['__collections__'] ?? [];
                unset($docData['__collections__']);

                $this->createDocument($parentPath, $collectionId, $docId, $docData);

                if (!empty($subCollections)) {
                    $this->processCollections($subCollections, "{$parentPath}/{$collectionId}/{$docId}");
                }
            }
        }
    }

    private function createDocument(string $parentPath, string $collectionId, string $docId, array $docData)
    {
        $fields = $this->parseFields($docData);

        // We use PATCH to upsert the document.
        $url = "https://firestore.googleapis.com/v1/{$parentPath}/{$collectionId}/{$docId}";

        $response = Http::withToken($this->accessToken)
            ->patch($url, [
                'fields' => $fields
            ]);

        if (!$response->successful()) {
            Log::error("Failed to insert document {$docId} in {$collectionId}: " . $response->body());
        }
    }

    private function parseFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[$key] = $this->parseValue($value);
        }
        return $fields;
    }

    private function parseValue($value): array
    {
        if (is_null($value)) {
            return ['nullValue' => null];
        }

        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }

        if (is_int($value)) {
            return ['integerValue' => (string) $value];
        }

        if (is_float($value)) {
            return ['doubleValue' => $value];
        }

        if (is_string($value)) {
            return ['stringValue' => $value];
        }

        if (is_array($value)) {
            // Check for node-firestore-import-export __datatype__
            if (isset($value['__datatype__'])) {
                if ($value['__datatype__'] === 'timestamp') {
                    $seconds = $value['value']['_seconds'] ?? 0;
                    $nanos = $value['value']['_nanoseconds'] ?? 0;
                    $dt = new \DateTime("@{$seconds}");
                    $fraction = str_pad(substr((string)$nanos, 0, 9), 9, '0', STR_PAD_RIGHT);
                    return ['timestampValue' => $dt->format('Y-m-d\TH:i:s') . '.' . $fraction . 'Z'];
                }
                if ($value['__datatype__'] === 'geopoint') {
                    return [
                        'geoPointValue' => [
                            'latitude' => $value['value']['_latitude'] ?? 0,
                            'longitude' => $value['value']['_longitude'] ?? 0
                        ]
                    ];
                }
                if ($value['__datatype__'] === 'reference') {
                    return ['referenceValue' => $value['value']];
                }
            }

            // Check if associative or indexed array
            if (array_is_list($value) || empty($value)) {
                $arrayValues = [];
                foreach ($value as $item) {
                    $arrayValues[] = $this->parseValue($item);
                }
                return ['arrayValue' => ['values' => $arrayValues]];
            } else {
                return ['mapValue' => ['fields' => $this->parseFields($value)]];
            }
        }

        // Fallback
        return ['stringValue' => (string) $value];
    }
}
