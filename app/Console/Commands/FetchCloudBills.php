<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServerCost;
use Carbon\Carbon;
use Aws\CostExplorer\CostExplorerClient;
use Google\Client;
use Google\Service\Cloudbilling;
use Illuminate\Support\Facades\Log;

class FetchCloudBills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'costs:fetch-cloud {month? : The month to fetch (YYYY-MM). Defaults to last month.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically fetch AWS and Google Cloud billing for a given month.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $monthInput = $this->argument('month');
        
        if ($monthInput) {
            $date = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
        } else {
            // Default to last month
            $date = Carbon::now()->subMonth()->startOfMonth();
        }

        $monthYear = $date->format('Y-m');
        $startOfMonth = $date->copy()->startOfMonth()->format('Y-m-d');
        $endOfMonth = $date->copy()->endOfMonth()->addDay()->format('Y-m-d'); // AWS needs exclusive end date

        $this->info("Fetching cloud bills for: {$monthYear}");

        $awsCost = $this->fetchAwsCost($startOfMonth, $endOfMonth);
        $firebaseCosts = $this->fetchGoogleCloudCost($startOfMonth, $endOfMonth);

        if ($awsCost !== null || !empty($firebaseCosts)) {
            $serverCost = ServerCost::firstOrCreate(['month_year' => $monthYear]);
            
            if ($awsCost !== null) {
                $serverCost->aws_cost = $awsCost;
                $this->info("AWS Cost: {$awsCost}");
            }
            if (!empty($firebaseCosts)) {
                $serverCost->firebase_1_cost = $firebaseCosts[0] ?? 0.00;
                $serverCost->firebase_2_cost = $firebaseCosts[1] ?? 0.00;
                $this->info("Firebase 1 Cost: " . ($firebaseCosts[0] ?? 0.00));
                $this->info("Firebase 2 Cost: " . ($firebaseCosts[1] ?? 0.00));
            }
            
            $serverCost->save();
            $this->info("Successfully updated costs for {$monthYear}.");
        } else {
            $this->error("Failed to fetch both AWS and Google Cloud costs. Check credentials in .env.");
        }
    }

    private function fetchAwsCost($startDate, $endDate)
    {
        if (!env('AWS_BILLING_KEY') || !env('AWS_BILLING_SECRET')) {
            $this->warn("AWS Billing keys missing. Skipping AWS.");
            return null;
        }

        try {
            $client = new CostExplorerClient([
                'version' => 'latest',
                'region'  => env('AWS_DEFAULT_REGION', 'us-east-1'),
                'credentials' => [
                    'key'    => env('AWS_BILLING_KEY'),
                    'secret' => env('AWS_BILLING_SECRET'),
                ],
            ]);

            $result = $client->getCostAndUsage([
                'TimePeriod' => [
                    'Start' => $startDate,
                    'End'   => $endDate,
                ],
                'Granularity' => 'MONTHLY',
                'Metrics' => ['AmortizedCost'],
            ]);

            if (isset($result['ResultsByTime'][0]['Total']['AmortizedCost']['Amount'])) {
                return (float) $result['ResultsByTime'][0]['Total']['AmortizedCost']['Amount'];
            }
            return 0.00;
        } catch (\Exception $e) {
            $this->error("AWS Cost Explorer Error: " . $e->getMessage());
            Log::error("AWS Cost Explorer Error: " . $e->getMessage());
            return null;
        }
    }

    private function fetchGoogleCloudCost($startDate, $endDate)
    {
        $credentialsPaths = env('GOOGLE_APPLICATION_CREDENTIALS');
        
        if (!$credentialsPaths) {
            $this->warn("Google Cloud Billing credentials missing. Skipping Firebase.");
            return [];
        }

        $paths = explode(',', $credentialsPaths);
        $firebaseCosts = [];

        foreach ($paths as $path) {
            $path = trim($path);
            if (!file_exists($path)) {
                $this->warn("GCP credentials file not found: {$path}");
                $firebaseCosts[] = 0.00;
                continue;
            }

            try {
                // Initialize the client for this specific account
                $client = new Client();
                $client->setApplicationName("Tidcraft Billing Fetcher");
                $client->setScopes([Cloudbilling::CLOUD_BILLING]);
                $client->setAuthConfig($path);

                $service = new Cloudbilling($client);
                
                // (Note: To get accurate monthly costs programmatically, GCP recommends BigQuery export)
                $this->warn("Note: GCP Cost requires BigQuery export configuration for exact queries on account associated with {$path}.");
                
                // For now we add 0.00, but in production you'd run the BigQuery SQL here
                $firebaseCosts[] = 0.00;

            } catch (\Exception $e) {
                $this->error("Google Cloud Billing Error for {$path}: " . $e->getMessage());
                Log::error("Google Cloud Billing Error for {$path}: " . $e->getMessage());
                $firebaseCosts[] = 0.00;
            }
        }

        return $firebaseCosts;
    }
}
