<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $plans = Plan::all();
        return response()->json([
            'status' => 'success',
            'data' => $plans
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
            'features' => 'nullable|array',
            'integrations' => 'nullable|array',
            'is_popular' => 'nullable|boolean',
            'max_users' => 'nullable|integer',
            'max_orders' => 'nullable|integer',
            'additional_order_price' => 'nullable|numeric|min:0',
            'store_configuration' => 'nullable|string',
            'has_hybrid_customer_app' => 'nullable|boolean',
            'has_hybrid_customer_merchant_app' => 'nullable|boolean',
            'has_unlimited_users_listings' => 'nullable|boolean',
            'has_white_labeled_solution' => 'nullable|boolean',
            'has_white_labeled_dashboard' => 'nullable|boolean',
            'storage_gb' => 'nullable|integer',
            'duration_days' => 'nullable|integer',
        ]);

        $plan = Plan::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Plan created successfully.',
            'data' => $plan
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Plan $plan)
    {
        return response()->json([
            'status' => 'success',
            'data' => $plan
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Plan $plan)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'billing_cycle' => 'sometimes|required|string|max:255',
            'is_active' => 'nullable|boolean',
            'features' => 'nullable|array',
            'integrations' => 'nullable|array',
            'is_popular' => 'nullable|boolean',
            'max_users' => 'nullable|integer',
            'max_orders' => 'nullable|integer',
            'additional_order_price' => 'nullable|numeric|min:0',
            'store_configuration' => 'nullable|string',
            'has_hybrid_customer_app' => 'nullable|boolean',
            'has_hybrid_customer_merchant_app' => 'nullable|boolean',
            'has_unlimited_users_listings' => 'nullable|boolean',
            'has_white_labeled_solution' => 'nullable|boolean',
            'has_white_labeled_dashboard' => 'nullable|boolean',
            'storage_gb' => 'nullable|integer',
            'duration_days' => 'nullable|integer',
        ]);

        $plan->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Plan updated successfully.',
            'data' => $plan
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plan $plan)
    {
        $plan->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Plan deleted successfully.'
        ]);
    }
}
