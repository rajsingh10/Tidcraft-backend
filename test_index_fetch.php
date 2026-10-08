<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$pf = App\Models\ProductFirebaseProject::first();
$sa = json_decode($pf->service_account_json, true);
$admin = new App\Services\FirebaseAdminClient();
$token = $admin->accessToken($sa, ['https://www.googleapis.com/auth/datastore']);
$url = 'https://firestore.googleapis.com/v1/projects/' . $sa['project_id'] . '/databases/clientone/collectionGroups/-/indexes';
echo Illuminate\Support\Facades\Http::withToken($token)->get($url)->body();
