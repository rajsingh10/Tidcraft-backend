<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FirestoreExporter
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

    public function export(): array
    {
        $rootPath = "projects/{$this->projectId}/databases/{$this->databaseId}/documents";
        
        $data = [];
        $collections = $this->listCollections($rootPath);
        
        if (!empty($collections)) {
            $data['__collections__'] = [];
            foreach ($collections as $collectionId) {
                $data['__collections__'][$collectionId] = $this->fetchCollection("{$rootPath}/{$collectionId}");
            }
        }
        
        return $data;
    }

    private function listCollections(string $parentPath): array
    {
        $url = "https://firestore.googleapis.com/v1/{$parentPath}:listCollectionIds";
        $response = Http::withToken($this->accessToken)->post($url);
        
        if ($response->successful()) {
            return $response->json('collectionIds') ?? [];
        }
        
        return [];
    }

    private function fetchCollection(string $collectionPath): array
    {
        $url = "https://firestore.googleapis.com/v1/{$collectionPath}?pageSize=300";
        $response = Http::withToken($this->accessToken)->get($url);
        
        $collectionData = [];
        
        if ($response->successful()) {
            $documents = $response->json('documents') ?? [];
            foreach ($documents as $doc) {
                // Extract document ID from path
                $docName = $doc['name'];
                $docId = basename($docName);
                
                $parsedFields = $this->parseFields($doc['fields'] ?? []);
                
                // Fetch subcollections recursively
                $subCollections = $this->listCollections($docName);
                if (!empty($subCollections)) {
                    $parsedFields['__collections__'] = [];
                    foreach ($subCollections as $subCollId) {
                        $parsedFields['__collections__'][$subCollId] = $this->fetchCollection("{$docName}/{$subCollId}");
                    }
                }
                
                $collectionData[$docId] = $parsedFields;
            }
        }
        
        return $collectionData;
    }

    private function parseFields(array $fields): array
    {
        $result = [];
        foreach ($fields as $key => $valueData) {
            $result[$key] = $this->parseValue($valueData);
        }
        return $result;
    }

    private function parseValue(array $valueData)
    {
        if (array_key_exists('nullValue', $valueData)) {
            return null;
        }
        if (array_key_exists('booleanValue', $valueData)) {
            return $valueData['booleanValue'];
        }
        if (array_key_exists('integerValue', $valueData)) {
            return (int) $valueData['integerValue'];
        }
        if (array_key_exists('doubleValue', $valueData)) {
            return (float) $valueData['doubleValue'];
        }
        if (array_key_exists('stringValue', $valueData)) {
            return $valueData['stringValue'];
        }
        if (array_key_exists('timestampValue', $valueData)) {
            return [
                '__datatype__' => 'timestamp',
                'value' => [
                    '_seconds' => strtotime($valueData['timestampValue']),
                    '_nanoseconds' => 0 // Simplified
                ]
            ];
        }
        if (array_key_exists('geoPointValue', $valueData)) {
            return [
                '__datatype__' => 'geopoint',
                'value' => [
                    '_latitude' => $valueData['geoPointValue']['latitude'] ?? 0,
                    '_longitude' => $valueData['geoPointValue']['longitude'] ?? 0
                ]
            ];
        }
        if (array_key_exists('referenceValue', $valueData)) {
            return [
                '__datatype__' => 'reference',
                'value' => $valueData['referenceValue']
            ];
        }
        if (array_key_exists('arrayValue', $valueData)) {
            $values = $valueData['arrayValue']['values'] ?? [];
            $array = [];
            foreach ($values as $item) {
                $array[] = $this->parseValue($item);
            }
            return $array;
        }
        if (array_key_exists('mapValue', $valueData)) {
            $fields = $valueData['mapValue']['fields'] ?? [];
            return $this->parseFields($fields);
        }

        return null;
    }
}
