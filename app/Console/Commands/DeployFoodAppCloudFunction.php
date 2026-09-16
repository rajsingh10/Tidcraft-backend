<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DeployFoodAppCloudFunction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'foodapp:deploy-cloud-function {database_id : The Firestore database ID (e.g. tidcraft-hareshfood)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy a dedicated Cloud Function v2 trigger for a specific FoodApp tenant Firestore database (Solution 2).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $databaseId = trim($this->argument('database_id'));
        if (empty($databaseId)) {
            $this->error('A valid database_id is required.');
            return 1;
        }

        $cleanDb = $databaseId;
        $fnName = 'delivery_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $cleanDb));

        $this->info("=================================================");
        $this->info(" Deploying FoodApp Cloud Function (Solution 2)  ");
        $this->info(" Database: $cleanDb");
        $this->info(" Function: $fnName");
        $this->info("=================================================");

        // Locate functions directory
        $candidates = [
            base_path('products/FoodApp/firebase_functions'),
            base_path('products/FoodApp/backup/Order Tracking Firebase Function'),
            '/home/devtidcraftcomusr/food-app/firebase_functions',
            '/home/devtidcraftcomusr/food-app/backup/Order Tracking Firebase Function'
        ];

        $functionsDir = null;
        foreach ($candidates as $dir) {
            if (is_dir($dir) && file_exists($dir . '/deploy_tenant.js')) {
                $functionsDir = $dir;
                break;
            }
        }

        if (!$functionsDir) {
            $this->error("Cloud Functions directory with deploy_tenant.js not found.");
            return 1;
        }

        $this->info("Using Functions Directory: $functionsDir");

        $command = ['node', 'deploy_tenant.js', $cleanDb];
        $process = new Process($command, $functionsDir, [
            'TARGET_TENANT_DB' => $cleanDb,
            'PATH' => getenv('PATH')
        ]);
        $process->setTimeout(600); // 10 minutes timeout for GCP Cloud Build

        $this->info("Executing deployment (this may take 2-4 minutes)...");

        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error("Deployment failed: " . $process->getErrorOutput());
            return 1;
        }

        $this->info("Function $fnName successfully deployed for database $cleanDb!");
        return 0;
    }
}
