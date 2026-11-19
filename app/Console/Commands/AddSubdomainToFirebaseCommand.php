<?php

namespace App\Console\Commands;

use App\Models\ProductFirebaseProject;
use App\Services\FirebaseAdminClient;
use Illuminate\Console\Command;

class AddSubdomainToFirebaseCommand extends Command
{
    protected $signature = 'firebase:add-domain {domain} {product_id=1}';
    protected $description = 'Add a custom domain or subdomain to Firebase Authorized Domains list';

    public function handle(): int
    {
        $domain = $this->argument('domain');
        $productId = (int) $this->argument('product_id');

        $productFirebase = ProductFirebaseProject::where('product_id', $productId)->first();
        if (!$productFirebase || empty($productFirebase->service_account_json)) {
            $this->error("No Firebase project configuration found for product_id={$productId}");
            return Command::FAILURE;
        }

        $serviceAccount = json_decode($productFirebase->service_account_json, true);
        if (empty($serviceAccount)) {
            $this->error("Service account JSON is invalid for product_id={$productId}");
            return Command::FAILURE;
        }

        $this->info("Registering domain '{$domain}' for project: " . ($serviceAccount['project_id'] ?? 'unknown'));
        
        $admin = new FirebaseAdminClient();
        $admin->addAuthorizedDomain($serviceAccount, $domain);

        $this->info("Domain registration request sent.");
        return Command::SUCCESS;
    }
}

