<?php
require __DIR__.'/vendor/autoload.php';
\ = require_once __DIR__.'/bootstrap/app.php';
\->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
\ = App\Models\ProductFirebaseProject::first();
\ = json_decode(\->service_account_json, true);
\ = new App\Services\FirebaseAdminClient();
\ = \->accessToken(\, ['https://www.googleapis.com/auth/datastore']);
\ = 'https://firestore.googleapis.com/v1/projects/' . \['project_id'] . '/databases/clientone/collectionGroups/-/indexes';
echo Illuminate\Support\Facades\Http::withToken(\)->get(\)->body();
