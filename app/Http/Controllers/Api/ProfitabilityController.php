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
            $firebaseCost = $costs ? (float)$costs->firebase_cost : 0.00;

            // Final profit calculation
            $profit = (float)$revenue - $awsCost - $firebaseCost;

            $data[] = [
                'month_year' => $monthYear,
                'display_month' => $date->format('F Y'), // e.g., September 2026
                'revenue' => (float)$revenue,
                'aws_cost' => $awsCost,
                'firebase_cost' => $firebaseCost,
                'total_cost' => $awsCost + $firebaseCost,
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
            'firebase_cost' => 'required|numeric|min:0',
        ]);

        $serverCost = ServerCost::updateOrCreate(
            ['month_year' => $validated['month_year']],
            [
                'aws_cost' => $validated['aws_cost'],
                'firebase_cost' => $validated['firebase_cost']
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Server costs updated successfully',
            'data' => $serverCost
        ]);
    }
}
