<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = App\Models\CmsPage::where('slug', 'features-section')->first();
if ($p) {
    echo "ID: " . $p->id . "\n";
    echo "Title: " . $p->title . "\n";
    $len = strlen(json_encode($p->content));
    echo "Content length: " . $len . " bytes\n";
} else {
    echo "Not found\n";
}

