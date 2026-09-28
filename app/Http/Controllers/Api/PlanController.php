<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $plans = Plan::with('currency')->get();
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
            'product_id' => 'required|exists:products,id',
            'currency_id' => 'nullable|exists:currencies,id',
            'currency' => 'nullable|string|max:10',
            'currency_code' => 'nullable|string|max:10',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'monthly_price' => 'required|numeric|min:0',
            'annual_price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'features' => 'nullable|array',
            'integrations' => 'nullable|array',
            'is_popular' => 'nullable|boolean',
            'max_users' => 'nullable|integer',
            'max_users_annual' => 'nullable|integer',
            'max_orders' => 'nullable|integer',
            'max_orders_monthly' => 'nullable|integer',
            'additional_order_price' => 'nullable|numeric|min:0',
            'store_configuration' => 'nullable|string',
            'has_hybrid_customer_app' => 'nullable|boolean',
            'has_hybrid_customer_merchant_app' => 'nullable|boolean',
            'has_unlimited_users_listings' => 'nullable|boolean',
            'has_white_labeled_solution' => 'nullable|boolean',
            'has_white_labeled_dashboard' => 'nullable|boolean',
            'storage_gb' => 'nullable|integer',
            'storage_gb_annual' => 'nullable|integer',
            'duration_days' => 'nullable|integer',
        ]);

        $data = $request->all();

        // Resolve currency: prioritize currency_id, then currency / currency_code string, fallback to default INR
        $currencyModel = null;
        if (!empty($data['currency_id'])) {
            $currencyModel = \App\Models\Currency::find($data['currency_id']);
        } elseif (!empty($data['currency'])) {
            if (is_numeric($data['currency'])) {
                $currencyModel = \App\Models\Currency::find($data['currency']);
            } else {
                $currencyModel = \App\Models\Currency::where('code', strtoupper($data['currency']))->first();
            }
        } elseif (!empty($data['currency_code'])) {
            $currencyModel = \App\Models\Currency::where('code', strtoupper($data['currency_code']))->first();
        }

        if (!$currencyModel) {
            $currencyModel = \App\Models\Currency::where('code', 'INR')->first() ?? \App\Models\Currency::first();
        }

        if ($currencyModel) {
            $data['currency_id'] = $currencyModel->id;
            $data['currency_code'] = $currencyModel->code;
        } else {
            $data['currency_code'] = 'INR';
        }

        $plan = Plan::create($data);
        $plan->load('currency');

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
        $plan->load('currency');
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
            'product_id' => 'sometimes|required|exists:products,id',
            'currency_id' => 'nullable|exists:currencies,id',
            'currency' => 'nullable|string|max:10',
            'currency_code' => 'nullable|string|max:10',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'monthly_price' => 'sometimes|required|numeric|min:0',
            'annual_price' => 'sometimes|required|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'features' => 'nullable|array',
            'integrations' => 'nullable|array',
            'is_popular' => 'nullable|boolean',
            'max_users' => 'nullable|integer',
            'max_users_annual' => 'nullable|integer',
            'max_orders' => 'nullable|integer',
            'max_orders_monthly' => 'nullable|integer',
            'additional_order_price' => 'nullable|numeric|min:0',
            'store_configuration' => 'nullable|string',
            'has_hybrid_customer_app' => 'nullable|boolean',
            'has_hybrid_customer_merchant_app' => 'nullable|boolean',
            'has_unlimited_users_listings' => 'nullable|boolean',
            'has_white_labeled_solution' => 'nullable|boolean',
            'has_white_labeled_dashboard' => 'nullable|boolean',
            'storage_gb' => 'nullable|integer',
            'storage_gb_annual' => 'nullable|integer',
            'duration_days' => 'nullable|integer',
        ]);

        $data = $request->all();

        $currencyModel = null;
        if (isset($data['currency_id'])) {
            $currencyModel = \App\Models\Currency::find($data['currency_id']);
        } elseif (isset($data['currency'])) {
            if (is_numeric($data['currency'])) {
                $currencyModel = \App\Models\Currency::find($data['currency']);
            } else {
                $currencyModel = \App\Models\Currency::where('code', strtoupper($data['currency']))->first();
            }
        } elseif (isset($data['currency_code'])) {
            $currencyModel = \App\Models\Currency::where('code', strtoupper($data['currency_code']))->first();
        }

        if ($currencyModel) {
            $data['currency_id'] = $currencyModel->id;
            $data['currency_code'] = $currencyModel->code;
        }

        $plan->update($data);
        $plan->load('currency');

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
        if (Subscription::where('plan_id', $plan->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete plan because it is associated with one or more subscriptions.'
            ], 400);
        }

        $plan->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Plan deleted successfully.'
        ]);
    }
}
