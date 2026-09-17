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
        // Only run if explicitly enabled via config/.env
        $enabled = filter_var(
            config('services.foodapp.enable_cloudfunction_deploy', env('ENABLE_CLOUDFUNCTION_DEPLOY', true)),
            FILTER_VALIDATE_BOOLEAN
        );
        if (!$enabled) {
            Log::info("DeployTenantFirebaseFunctionJob skipped for {$this->databaseId} (ENABLE_CLOUDFUNCTION_DEPLOY is false).");
            return;
        }

        Log::info("Starting DeployTenantFirebaseFunctionJob for database: {$this->databaseId}");

        $tenant = $this->tenantId ? \App\Models\Tenant::find($this->tenantId) : null;
        if ($tenant) {
            \App\Models\ProvisioningLog::create([
                'tenant_id' => $tenant->id,
                'step' => 'cloud_function',
                'status' => 'in_progress',
                'message' => "Deploying dedicated Firebase Cloud Function for database {$this->databaseId} to GCP...",
                'started_at' => now(),
            ]);
        }

        try {
            $exitCode = Artisan::call('foodapp:deploy-cloud-function', [
                'database_id' => $this->databaseId
            ]);
            $output = Artisan::output();

            if ($exitCode === 0) {
                Log::info("Cloud Function successfully deployed for database {$this->databaseId}");
                if ($tenant) {
                    \App\Models\ProvisioningLog::create([
                        'tenant_id' => $tenant->id,
                        'step' => 'cloud_function',
                        'status' => 'success',
                        'message' => "Dedicated Firebase Cloud Function successfully deployed for database {$this->databaseId}",
                        'started_at' => now(),
                        'completed_at' => now(),
                    ]);
                }
            } else {
                Log::error("Cloud Function deploy returned exit code {$exitCode} for database {$this->databaseId}: {$output}");
                if ($tenant) {
                    \App\Models\ProvisioningLog::create([
                        'tenant_id' => $tenant->id,
                        'step' => 'cloud_function',
                        'status' => 'failed',
                        'message' => "Cloud Function deployment failed (exit code {$exitCode})",
                        'error' => $output,
                        'started_at' => now(),
                        'completed_at' => now(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error("Failed to deploy Cloud Function for database {$this->databaseId}: " . $e->getMessage());
            if ($tenant) {
                \App\Models\ProvisioningLog::create([
                    'tenant_id' => $tenant->id,
                    'step' => 'cloud_function',
                    'status' => 'failed',
                    'message' => "Cloud Function deployment encountered an exception: " . $e->getMessage(),
                    'error' => $e->getMessage(),
                    'started_at' => now(),
                    'completed_at' => now(),
                ]);
            }
        }
    }
}
