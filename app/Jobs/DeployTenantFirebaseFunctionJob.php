<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DeployTenantFirebaseFunctionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 600; // 10 minutes for GCP Cloud Functions deploy

    protected string $databaseId;
    protected ?int $tenantId;

    public function __construct(string $databaseId, ?int $tenantId = null)
    {
        $this->databaseId = $databaseId;
        $this->tenantId = $tenantId;
    }

    public function handle(): void
    {
        // Only run if explicitly enabled via .env
        $enabled = config('services.foodapp.enable_cloudfunction_deploy', env('ENABLE_CLOUDFUNCTION_DEPLOY', false));
        if (!$enabled) {
            Log::info("DeployTenantFirebaseFunctionJob skipped for {$this->databaseId} (ENABLE_CLOUDFUNCTION_DEPLOY is false).");
            return;
        }

        Log::info("Starting DeployTenantFirebaseFunctionJob for database: {$this->databaseId}");

        try {
            $exitCode = Artisan::call('foodapp:deploy-cloud-function', [
                'database_id' => $this->databaseId
            ]);

            if ($exitCode === 0) {
                Log::info("Cloud Function successfully deployed for database {$this->databaseId}");
            } else {
                Log::error("Cloud Function deploy returned exit code {$exitCode} for database {$this->databaseId}");
            }
        } catch (\Throwable $e) {
            Log::error("Failed to deploy Cloud Function for database {$this->databaseId}: " . $e->getMessage());
        }
    }
}
