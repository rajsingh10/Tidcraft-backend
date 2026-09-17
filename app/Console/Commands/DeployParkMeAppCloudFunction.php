<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DeployParkMeAppCloudFunction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parkmeapp:deploy-cloud-function {database_id : The Firestore database ID (e.g. tidcraft-parkxyz)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy a dedicated Cloud Function v2 trigger for a specific ParkMeApp tenant Firestore database.';

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
        $fnName = 'parkme_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $cleanDb));

        $this->info("=================================================");
        $this->info(" Deploying ParkMeApp Cloud Function             ");
        $this->info(" Database: $cleanDb");
        $this->info(" Function: $fnName");
        $this->info("=================================================");

        // Locate functions directory
        $candidates = [
            base_path('products/ParkMeApp/firebase_functions'),
            '/home/devtidcraftcomusr/parkme-app/firebase_functions'
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

        $tempDir = sys_get_temp_dir() ?: '/tmp';
        $writableHome = (isset($_SERVER['HOME']) && $_SERVER['HOME'] !== '/var/www' && is_dir($_SERVER['HOME']) && is_writable($_SERVER['HOME']))
            ? $_SERVER['HOME']
            : $tempDir;

        $env = [
            'TARGET_TENANT_DB' => $cleanDb,
            'PATH' => (getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin') . ':/usr/local/bin:/usr/bin:/bin',
            'HOME' => $writableHome,
            'XDG_CONFIG_HOME' => rtrim($writableHome, '/\\') . '/.config',
            'XDG_CACHE_HOME' => rtrim($writableHome, '/\\') . '/.cache',
            'CI' => 'true',
        ];
        if (getenv('USERPROFILE')) {
            $env['USERPROFILE'] = getenv('USERPROFILE');
        }
        if (getenv('GOOGLE_APPLICATION_CREDENTIALS')) {
            $env['GOOGLE_APPLICATION_CREDENTIALS'] = getenv('GOOGLE_APPLICATION_CREDENTIALS');
        }

        $command = ['node', 'deploy_tenant.js', $cleanDb];
        $process = new Process($command, $functionsDir, $env);
        $process->setTimeout(600); // 10 minutes timeout for GCP Cloud Build

        $this->info("Executing deployment (this may take 2-4 minutes)...");

        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $errorOutput = trim($process->getErrorOutput() ?: $process->getOutput());
            $this->error("Deployment failed: " . $errorOutput);
            return 1;
        }

        $this->info("Function $fnName successfully deployed for database $cleanDb!");
        return 0;
    }
}
