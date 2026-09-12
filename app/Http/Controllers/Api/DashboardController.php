<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\SupportTicket;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // 1. Total Clients
        $totalClients = User::role('Client')->count();
        $clientsThisMonth = User::role('Client')->where('created_at', '>=', $startOfMonth)->count();
        $clientsLastMonth = User::role('Client')->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $clientGrowth = $clientsLastMonth > 0 ? (($clientsThisMonth - $clientsLastMonth) / $clientsLastMonth) * 100 : 0;
        
        $clientsInTrial = 0; // Assuming we add trial tracking later
        
        // 2. Active Subscriptions
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $subsThisMonth = Subscription::where('status', 'active')->where('create_at', '>=', $startOfMonth)->count();
        $subsLastMonth = Subscription::where('status', 'active')->whereBetween('create_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $subsGrowth = $subsLastMonth > 0 ? (($subsThisMonth - $subsLastMonth) / $subsLastMonth) * 100 : 0;
        
        $pastDueSubs = Subscription::where('status', 'past_due')->count();

        // 3. Monthly Revenue
        // Assuming amount is a decimal field
        $revenueThisMonth = Payment::whereIn('status', ['completed', 'success'])
            ->where('create_at', '>=', $startOfMonth)
            ->sum('amount');
            
        $revenueLastMonth = Payment::whereIn('status', ['completed', 'success'])
            ->whereBetween('create_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');
            
        $revenueGrowth = $revenueLastMonth > 0 ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100 : 0;

        // 4. Open Support Tickets
        $openTickets = SupportTicket::whereIn('status', ['open', 'in_progress', 'pending'])->count();
        $criticalTickets = SupportTicket::whereIn('status', ['open', 'in_progress', 'pending'])
            ->where('priority', 'high')->count();

        // 5. Recent Client Activity (Live Feed)
        // We'll pull recent tenants as 'activity'
        $recentActivityQuery = Tenant::with(['client', 'plan']);
        
        if ($request->has('filter') && $request->filter !== 'All') {
            $filterStatus = strtolower($request->filter);
            // Map frontend statuses if needed
            if ($filterStatus === 'past due') $filterStatus = 'past_due';
            $recentActivityQuery->where('status', $filterStatus);
        }
        
        $recentActivity = $recentActivityQuery->orderBy('create_at', 'desc')->take(8)->get()->map(function ($tenant) {
            return [
                'id' => $tenant->id,
                'business_name' => $tenant->business_name,
                'client_name' => $tenant->client ? $tenant->client->name : 'Unknown',
                'plan_name' => $tenant->plan ? $tenant->plan->name : 'N/A',
                'status' => $tenant->status,
                'mrr' => $tenant->plan ? $tenant->plan->monthly_price : 0, // Using the new monthly_price column
                'created_at' => $tenant->create_at,
            ];
        });
        
        $activityCounts = [
            'all' => Tenant::count(),
            'active' => Tenant::where('status', 'active')->count(),
            'past_due' => Tenant::where('status', 'past_due')->count(),
            'suspended' => Tenant::where('status', 'suspended')->count(),
        ];

        // 6. Mocked System Metrics for UI rendering
        $systemMetrics = [
            'health' => [
                'status' => 'Operational',
                'uptime' => '99.97%',
                'avg_cpu' => '43%',
                'latency' => '124ms',
                'nodes_online' => '4 / 4 nodes online'
            ],
            'resource_utilization' => [
                'status' => 'Healthy',
                'storage' => [
                    'used' => 710,
                    'total' => 1000,
                    'unit' => 'GB',
                    'percentage' => 71
                ],
                'database_iops' => [
                    'used' => 6400,
                    'total' => 10000,
                    'percentage' => 64
                ]
            ],
            'background_jobs' => [
                'status' => 'Normal Drain',
                'active' => 14,
                'failed_24h' => 0,
                'latency' => '42ms'
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'top_metrics' => [
                    'total_clients' => [
                        'value' => $totalClients,
                        'growth' => round($clientGrowth, 1),
                        'subtitle' => $clientsInTrial . ' in trial, ' . $clientsThisMonth . ' new'
                    ],
                    'active_subscriptions' => [
                        'value' => $activeSubscriptions,
                        'growth' => round($subsGrowth, 1),
                        'subtitle' => $pastDueSubs . ' past due'
                    ],
                    'monthly_revenue' => [
                        'value' => $revenueThisMonth,
                        'growth' => round($revenueGrowth, 1),
                        'subtitle' => '₹' . number_format($revenueLastMonth / 1000000, 1) . 'M last month'
                    ],
                    'open_support_tickets' => [
                        'value' => $openTickets,
                        'needs_attention' => $criticalTickets > 0,
                        'subtitle' => $criticalTickets . ' critical'
                    ]
                ],
                'recent_activity' => [
                    'feed' => $recentActivity,
                    'counts' => $activityCounts
                ],
                'system_metrics' => $systemMetrics
            ]
        ]);
    }
}
