<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServerCost;
use App\Models\Payment;
use Carbon\Carbon;

class ProfitabilityController extends Controller
{
    /**
     * Get profitability data for the last 12 months.
     */
    public function index()
    {
        $monthsToFetch = 12;
        $data = [];

        for ($i = $monthsToFetch - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthYear = $date->format('Y-m'); // e.g., 2026-09
            
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            // Total revenue for this month
            // Note: Dashboard uses create_at in payment table
            $revenue = Payment::whereIn('status', ['completed', 'success'])
                ->whereBetween('create_at', [$startOfMonth, $endOfMonth])
                ->sum('amount');

            // Infrastructure costs for this month
            $costs = ServerCost::where('month_year', $monthYear)->first();
            $awsCost = $costs ? (float)$costs->aws_cost : 0.00;
            $firebase1Cost = $costs ? (float)$costs->firebase_1_cost : 0.00;
            $firebase2Cost = $costs ? (float)$costs->firebase_2_cost : 0.00;
            $serverInfraCost = $costs ? (float)$costs->server_infrastructure_cost : 0.00;

            // Final profit calculation
            $profit = (float)$revenue - $awsCost - $firebase1Cost - $firebase2Cost - $serverInfraCost;

            $data[] = [
                'month_year' => $monthYear,
                'display_month' => $date->format('F Y'), // e.g., September 2026
                'revenue' => (float)$revenue,
                'aws_cost' => $awsCost,
                'firebase_1_cost' => $firebase1Cost,
                'firebase_2_cost' => $firebase2Cost,
                'server_infrastructure_cost' => $serverInfraCost,
                'total_cost' => $awsCost + $firebase1Cost + $firebase2Cost + $serverInfraCost,
                'profit' => $profit
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => array_reverse($data) // Return most recent first
        ]);
    }

    /**
     * Store or update server costs for a specific month.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'month_year' => 'required|date_format:Y-m',
            'aws_cost' => 'required|numeric|min:0',
            'firebase_1_cost' => 'required|numeric|min:0',
            'firebase_2_cost' => 'required|numeric|min:0',
            'server_infrastructure_cost' => 'required|numeric|min:0',
        ]);

        $serverCost = ServerCost::updateOrCreate(
            ['month_year' => $validated['month_year']],
            [
                'aws_cost' => $validated['aws_cost'],
                'firebase_1_cost' => $validated['firebase_1_cost'],
                'firebase_2_cost' => $validated['firebase_2_cost'],
                'server_infrastructure_cost' => $validated['server_infrastructure_cost']
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Server costs updated successfully',
            'data' => $serverCost
        ]);
    }

    /**
     * Trigger the background command to fetch cloud bills manually via API.
     */
    public function fetchCloudBillsManually(Request $request)
    {
        $month = $request->input('month'); // optional, e.g., '2026-08'
        
        try {
            if ($month) {
                \Illuminate\Support\Facades\Artisan::call('costs:fetch-cloud', ['month' => $month]);
            } else {
                \Illuminate\Support\Facades\Artisan::call('costs:fetch-cloud');
            }
            
            $output = \Illuminate\Support\Facades\Artisan::output();

            return response()->json([
                'status' => 'success',
                'message' => 'Cloud bills fetched successfully.',
                'log' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch cloud bills.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
