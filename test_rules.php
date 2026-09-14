<?php
require __DIR__.'/vendor/autoload.php';
\ = require_once __DIR__.'/bootstrap/app.php';
\->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\ProductFirebaseProject;
use App\Services\FirebaseAdminClient;
use Illuminate\Support\Facades\Http;
\ = ProductFirebaseProject::first();
\ = json_decode(\->service_account_json, true);
\ = \['project_id'];
\ = new FirebaseAdminClient();
\ = \->accessToken(\, ['https://www.googleapis.com/auth/firebase', 'https://www.googleapis.com/auth/cloud-platform']);
\ = 'tidcraft-arun-prajapati-first';
\ = "https://firebaserules.googleapis.com/v1/projects/{\}/rulesets";
\ = "rules_version = '2';\nservice cloud.firestore {\n  match /databases/{database}/documents {\n    match /{document=**} {\n      allow read, write: if true;\n    }\n  }\n}";
\ = Http::withToken(\)->post(\, ['source' => ['files' => [['name' => 'firestore.rules', 'content' => \]]]]);
if (!\->successful()) { echo "Failed: " . \->body() . "\n"; exit; }
\ = \->json('name');
echo "Created ruleset: {\}\n";
\ = "projects/{\}/releases/cloud.firestore/{\}";
\ = "https://firebaserules.googleapis.com/v1/{\}";
\ = Http::withToken(\)->get(\);
if (\->successful()) {
    echo "Release exists, patching...\n";
    echo Http::withToken(\)->patch(\, ['rulesetName' => \])->body() . "\n";
} else {
    echo "Release does not exist, creating...\n";
    echo Http::withToken(\)->post("https://firebaserules.googleapis.com/v1/projects/{\}/releases", ['name' => \, 'rulesetName' => \])->body() . "\n";
}
