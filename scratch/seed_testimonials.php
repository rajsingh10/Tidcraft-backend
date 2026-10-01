<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$json = '{
    "title": "Testimonials Section",
    "slug": "testimonials-section",
    "short_description": null,
    "page_type": "product",
    "product_id": 2,
    "content": {
        "badge": "OUR TESTIMONIALS",
        "headline": "What They Are Talking About Tidcraft",
        "testimonials": [
            {
                "id": "1",
                "name": "Sarah Chen",
                "role": "Operations Manager, ParkOne",
                "content": "TidPark gave us the technology we needed without having to build everything from scratch. Having our own branded apps and management dashboard made it much easier for us to launch and start serving customers.",
                "rating": 5,
                "image": "data:image/png;base64,iVBORw0KGgo"
            }
        ]
    }
}';

$data = json_decode($json, true);

$upsertKey = [
    'slug' => 'testimonials-section',
    'page_type' => 'product',
    'product_id' => 2
];

\App\Models\CmsPage::updateOrCreate($upsertKey, $data);
echo "Seeded Testimonials Section.\n";
