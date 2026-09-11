<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class UsageMeteringController extends Controller
{
    public function index(Request $request)
    {
        // Fetch tenants with their active plan to extract limits
        $tenants = Tenant::with(['client', 'plan'])->orderBy('create_at', 'desc')->get();

        $usageData = $tenants->map(function ($tenant) {
            $plan = $tenant->plan;
            
            // Extract limits from the plan (fallback to defaults if unlimited or missing)
            $maxUsers = $plan && $plan->max_users ? $plan->max_users : 1000;
            $maxOrders = $plan && $plan->max_orders ? $plan->max_orders : 10000;
            $storageGb = $plan && $plan->storage_gb ? $plan->storage_gb : 100;

            // Mock current usage (in reality, we would query the tenant's isolated DB)
            // We use the tenant ID as a seed so the mock data stays consistent between reloads
            srand($tenant->id * 100);
            
            $currentUsers = rand(1, min($maxUsers, 50));
            $currentOrders = rand(0, min($maxOrders, 2000));
            $currentStorage = rand(1, min($storageGb, 20));
            
            // Calculate percentages
            $usersPercent = $maxUsers > 0 ? round(($currentUsers / $maxUsers) * 100) : 0;
            $ordersPercent = $maxOrders > 0 ? round(($currentOrders / $maxOrders) * 100) : 0;
            $storagePercent = $storageGb > 0 ? round(($currentStorage / $storageGb) * 100) : 0;

            return [
                'tenant_id' => $tenant->id,
                'business_name' => $tenant->business_name,
                'client_name' => $tenant->client ? $tenant->client->name : 'Unknown',
                'plan_name' => $plan ? $plan->name : 'N/A',
                'status' => $tenant->status,
                
                'metrics' => [
                    'users' => [
                        'used' => $currentUsers,
                        'limit' => $maxUsers,
                        'percentage' => $usersPercent
                    ],
                    'orders' => [
                        'used' => $currentOrders,
                        'limit' => $maxOrders,
                        'percentage' => $ordersPercent
                    ],
                    'storage' => [
                        'used' => $currentStorage,
                        'limit' => $storageGb,
                        'unit' => 'GB',
                        'percentage' => $storagePercent
                    ]
                ]
            ];
        });

        // Summary stats for the top of the usage metering page
        $totalStorageLimit = $tenants->sum(fn($t) => $t->plan ? $t->plan->storage_gb : 100);
        srand(date('Ymd')); // daily seed
        $totalStorageUsed = rand(10, min($totalStorageLimit, 500));

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'total_active_tenants' => $tenants->where('status', 'active')->count(),
                    'total_storage_used_gb' => $totalStorageUsed,
                    'total_storage_limit_gb' => $totalStorageLimit,
                ],
                'tenants' => $usageData->values()
            ]
        ]);
    }
}
