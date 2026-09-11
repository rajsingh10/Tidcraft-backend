<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class UsageMeteringController extends Controller
{
    public function index(Request $request)
    {
        // Fetch tenants with their active plan
        $tenants = Tenant::with(['client', 'plan'])->orderBy('create_at', 'desc')->get();

        $nearQuotaCount = 0;
        
        $usageData = $tenants->map(function ($tenant) use (&$nearQuotaCount) {
            $plan = $tenant->plan;
            
            // Extract limit for storage (fallback to default)
            $storageLimit = $plan && $plan->storage_gb ? $plan->storage_gb : 150;
            
            // Define mock limits for API usage based on plan name
            $planName = $plan ? strtolower($plan->name) : 'basic';
            if (str_contains($planName, 'enterprise')) {
                $apiLimitM = 5.0; // 5.0M
            } elseif (str_contains($planName, 'professional') || str_contains($planName, 'pro')) {
                $apiLimitM = 5.0; // 5.0M
            } else {
                $apiLimitM = 1.0; // 1.0M
            }

            // Seed mock data using tenant ID
            srand($tenant->id * 200);
            
            // Current mock usage
            $currentApiM = round($apiLimitM * (rand(30, 95) / 100), 1);
            $currentStorageGb = rand(10, min($storageLimit, $storageLimit - 5));
            $egressTb = round(rand(5, 25) / 10, 1); // 0.5 to 2.5 TB
            
            // Calculate percentages
            $apiPercent = $apiLimitM > 0 ? round(($currentApiM / $apiLimitM) * 100) : 0;
            $storagePercent = $storageLimit > 0 ? round(($currentStorageGb / $storageLimit) * 100) : 0;

            if ($apiPercent > 85 || $storagePercent > 85) {
                $nearQuotaCount++;
            }

            return [
                'tenant_id' => $tenant->id,
                'client' => $tenant->business_name,
                'tier' => $plan ? $plan->name : 'N/A',
                
                'metrics' => [
                    'api_usage' => [
                        'used_m' => $currentApiM,
                        'limit_m' => $apiLimitM,
                        'percentage' => $apiPercent
                    ],
                    'storage' => [
                        'used_gb' => $currentStorageGb,
                        'limit_gb' => $storageLimit,
                        'percentage' => $storagePercent
                    ],
                    'egress' => [
                        'used_tb' => $egressTb
                    ]
                ]
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'api_calls' => [
                        'value' => '38.4M',
                        'trend' => '+14.2% vs last cycle'
                    ],
                    'encrypted_storage' => [
                        'value' => '4.82 TB',
                        'subtitle' => 'Across ' . max(1, count($tenants)) . ' isolated clusters'
                    ],
                    'egress_bandwidth' => [
                        'value' => '18.9 TB',
                        'subtitle' => 'Multi-cloud Cloudflare CDN'
                    ],
                    'tenants_near_quota' => [
                        'value' => $nearQuotaCount,
                        'subtitle' => 'Overage alerts sent'
                    ]
                ],
                'tenants' => $usageData->values()
            ]
        ]);
    }
}
