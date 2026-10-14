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
        
        // 2. Active Subscriptions (excluding demo tenants)
        $activeSubscriptions = Subscription::where('status', 'active')
            ->whereDoesntHave('tenant', function ($tq) {
                $tq->where('is_demo', true);
            })->count();
        $subsThisMonth = Subscription::where('status', 'active')
            ->whereDoesntHave('tenant', function ($tq) {
                $tq->where('is_demo', true);
            })
            ->where('create_at', '>=', $startOfMonth)->count();
        $subsLastMonth = Subscription::where('status', 'active')
            ->whereDoesntHave('tenant', function ($tq) {
                $tq->where('is_demo', true);
            })
            ->whereBetween('create_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $subsGrowth = $subsLastMonth > 0 ? (($subsThisMonth - $subsLastMonth) / $subsLastMonth) * 100 : 0;
        
        $pastDueSubs = Subscription::where('status', 'past_due')
            ->whereDoesntHave('tenant', function ($tq) {
                $tq->where('is_demo', true);
            })->count();

        // 3. Monthly Revenue (excluding demo tenants)
        // Assuming amount is a decimal field
        $revenueThisMonth = Payment::whereIn('status', ['completed', 'success'])
            ->whereDoesntHave('tenant', function ($tq) {
                $tq->where('is_demo', true);
            })
            ->where('create_at', '>=', $startOfMonth)
            ->sum('amount');
            
        $revenueLastMonth = Payment::whereIn('status', ['completed', 'success'])
            ->whereDoesntHave('tenant', function ($tq) {
                $tq->where('is_demo', true);
            })
            ->whereBetween('create_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');
            
        $revenueGrowth = $revenueLastMonth > 0 ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100 : 0;

        // 4. Open Support Tickets
        $openTickets = SupportTicket::whereIn('status', ['open', 'in_progress', 'pending'])->count();
        $criticalTickets = SupportTicket::whereIn('status', ['open', 'in_progress', 'pending'])
            ->where('priority', 'high')->count();

        // 5. Recent Client Activity (Live Feed)
        // We'll pull recent tenants as 'activity' (excluding demo tenants)
        $recentActivityQuery = Tenant::where('is_demo', false)->with(['client', 'plan']);
        
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
            'all' => Tenant::where('is_demo', false)->count(),
            'active' => Tenant::where('is_demo', false)->where('status', 'active')->count(),
            'past_due' => Tenant::where('is_demo', false)->where('status', 'past_due')->count(),
            'suspended' => Tenant::where('is_demo', false)->where('status', 'suspended')->count(),
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

    public function revenueAnalytics(Request $request)
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Total MRR of active subscriptions (excluding demo tenants)
        $totalMrr = (float) Subscription::where('subscriptions.status', 'active')
            ->whereDoesntHave('tenant', function ($tq) {
                $tq->where('is_demo', true);
            })
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.monthly_price');

        // Total MRR of active subscriptions from Last Month (excluding demo tenants)
        $lastMonthMrr = (float) Subscription::where('subscriptions.status', 'active')
            ->whereDoesntHave('tenant', function ($tq) {
                $tq->where('is_demo', true);
            })
            ->where('subscriptions.create_at', '<=', $endOfLastMonth)
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.monthly_price');

        // Active Tenants (excluding demo)
        $activeTenants = Subscription::where('status', 'active')
            ->whereDoesntHave('tenant', function ($tq) {
                $tq->where('is_demo', true);
            })
            ->distinct('tenant_id')
            ->count('tenant_id');

        // ARPU
        $arpu = $activeTenants > 0 ? $totalMrr / $activeTenants : 0;
        
        // Mock ARPU Growth since we don't have historical snapshot data
        $arpuGrowth = 4.2;

        // NRR Estimation
        // (Current MRR from existing customers / Last month MRR)
        if ($lastMonthMrr > 0) {
            $nrr = ($totalMrr / $lastMonthMrr) * 100;
        } else {
            $nrr = 100;
        }

        // Logo Churn Estimation (excluding demo tenants)
        $tenantsAtStartOfMonth = Tenant::where('is_demo', false)
            ->where('create_at', '<', $startOfMonth)->count();
        $churnedTenantsThisMonth = Subscription::where('status', 'cancelled')
            ->whereDoesntHave('tenant', function ($tq) {
                $tq->where('is_demo', true);
            })
            ->where('update_at', '>=', $startOfMonth)
            ->distinct('tenant_id')
            ->count('tenant_id');
        
        $logoChurn = $tenantsAtStartOfMonth > 0 ? ($churnedTenantsThisMonth / $tenantsAtStartOfMonth) * 100 : 0;

        // Extra Orders & Overage Revenue Analytics (excluding demo tenants)
        $overageQuery = \App\Models\TenantOverageBill::query()
            ->whereDoesntHave('tenant', function ($tq) {
                $tq->where('is_demo', true);
            });

        if ($request->filled('tenant_id')) {
            $overageQuery->where('tenant_id', $request->tenant_id);
        }
        if ($request->filled('product_id')) {
            $overageQuery->where('product_id', $request->product_id);
        }
        if ($request->filled('bill_type')) {
            $overageQuery->where('bill_type', $request->bill_type);
        }

        $paidOverage = (float) (clone $overageQuery)->whereIn('status', ['paid', 'success'])->sum('total_amount');
        $unpaidOverage = (float) (clone $overageQuery)->whereNotIn('status', ['paid', 'success'])->sum('total_amount');
        $totalOverage = round($paidOverage + $unpaidOverage, 2);

        $paidUnits = (int) (clone $overageQuery)->whereIn('status', ['paid', 'success'])->sum('overage_units');
        $unpaidUnits = (int) (clone $overageQuery)->whereNotIn('status', ['paid', 'success'])->sum('overage_units');
        $totalUnits = $paidUnits + $unpaidUnits;

        $paidCount = (int) (clone $overageQuery)->whereIn('status', ['paid', 'success'])->count();
        $unpaidCount = (int) (clone $overageQuery)->whereNotIn('status', ['paid', 'success'])->count();
        $totalCount = $paidCount + $unpaidCount;

        // Product-wise Extra Orders / Overage Revenue Breakdown
        $allProducts = \App\Models\Product::orderBy('id')->get();
        $denominator = $totalOverage > 0 ? $totalOverage : ($paidOverage > 0 ? $paidOverage : 1);

        $productBreakdown = $allProducts->map(function ($product) use ($denominator, $totalOverage) {
            $pQuery = \App\Models\TenantOverageBill::where(function ($q) use ($product) {
                $q->where('product_id', $product->id)
                  ->orWhereHas('tenant', fn ($tq) => $tq->where('product_id', $product->id));
            })->whereDoesntHave('tenant', function ($tq) {
                $tq->where('is_demo', true);
            });

            $pPaid = (float) (clone $pQuery)->whereIn('status', ['paid', 'success'])->sum('total_amount');
            $pUnpaid = (float) (clone $pQuery)->whereNotIn('status', ['paid', 'success'])->sum('total_amount');
            $pTotal = round($pPaid + $pUnpaid, 2);

            $pPaidUnits = (int) (clone $pQuery)->whereIn('status', ['paid', 'success'])->sum('overage_units');
            $pUnpaidUnits = (int) (clone $pQuery)->whereNotIn('status', ['paid', 'success'])->sum('overage_units');
            $pTotalUnits = $pPaidUnits + $pUnpaidUnits;

            $percentage = $totalOverage > 0 ? round(($pTotal / $totalOverage) * 100, 1) : 0;

            return [
                'id' => $product->id,
                'name' => $product->name,
                // Match frontend table columns: ID, NAME, MRR, PERCENTAGE
                'mrr' => round($pTotal > 0 ? $pTotal : $pPaid, 2),
                'amount' => round($pTotal, 2),
                'paid' => round($pPaid, 2),
                'unpaid' => round($pUnpaid, 2),
                'total' => round($pTotal, 2),
                'paid_orders' => $pPaidUnits,
                'unpaid_orders' => $pUnpaidUnits,
                'total_orders' => $pTotalUnits,
                'percentage' => $percentage,
            ];
        });

        return response()->json([
            'status' => 'success',
            'paid' => round($paidOverage, 2),
            'unpaid' => round($unpaidOverage, 2),
            'total' => round($totalOverage, 2),
            'data' => [
                'paid' => round($paidOverage, 2),
                'unpaid' => round($unpaidOverage, 2),
                'total' => round($totalOverage, 2),
                'extra_orders' => [
                    'paid' => round($paidOverage, 2),
                    'unpaid' => round($unpaidOverage, 2),
                    'total' => round($totalOverage, 2),
                    'paid_units' => $paidUnits,
                    'unpaid_units' => $unpaidUnits,
                    'total_units' => $totalUnits,
                    'paid_bills_count' => $paidCount,
                    'unpaid_bills_count' => $unpaidCount,
                    'total_bills_count' => $totalCount,
                ],
                'product_wise' => $productBreakdown,
                'product_breakdown' => $productBreakdown,
                'summary' => [
                    'paid' => round($paidOverage, 2),
                    'unpaid' => round($unpaidOverage, 2),
                    'total' => round($totalOverage, 2),
                ],
                'revenue' => [
                    'paid' => round($paidOverage, 2),
                    'unpaid' => round($unpaidOverage, 2),
                    'total' => round($totalOverage, 2),
                ],
                'metrics' => [
                    'paid' => round($paidOverage, 2),
                    'unpaid' => round($unpaidOverage, 2),
                    'total' => round($totalOverage, 2),
                    'arpu' => [
                        'value' => round($arpu, 2),
                        'growth' => round($arpuGrowth, 1)
                    ],
                    'nrr' => [
                        'value' => round($nrr, 1)
                    ],
                    'logo_churn' => [
                        'value' => round($logoChurn, 2)
                    ]
                ],
            ]
        ]);
    }
}
