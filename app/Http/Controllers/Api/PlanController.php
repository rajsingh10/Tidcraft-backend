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
        $plans = Plan::with(['prices.currency', 'currency'])->get();
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
        $prices = $this->parseJsonFields($request);

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
            'prices' => 'nullable|array',
            'prices.*.currency_id' => 'required_with:prices|exists:currencies,id',
            'prices.*.monthly_price' => 'required_with:prices|numeric|min:0',
            'prices.*.annual_price' => 'required_with:prices|numeric|min:0',
        ]);

        $data = $request->except('prices');

        // Resolve legacy currency: prioritize currency_id, then currency / currency_code string, fallback to default INR
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

        // Store prices array
        if (!empty($prices) && is_array($prices)) {
            foreach ($prices as $p) {
                if (!empty($p['currency_id'])) {
                    $plan->prices()->create([
                        'currency_id' => (int) $p['currency_id'],
                        'monthly_price' => (float) ($p['monthly_price'] ?? 0),
                        'annual_price' => (float) ($p['annual_price'] ?? 0),
                    ]);
                }
            }
        } elseif ($plan->currency_id) {
            // Legacy single price fallback
            $plan->prices()->create([
                'currency_id' => $plan->currency_id,
                'monthly_price' => (float) ($plan->monthly_price ?? 0),
                'annual_price' => (float) ($plan->annual_price ?? 0),
            ]);
        }

        $plan->load(['prices.currency', 'currency']);

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
        $plan->load(['prices.currency', 'currency']);
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
        $prices = $this->parseJsonFields($request);

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
            'prices' => 'nullable|array',
            'prices.*.currency_id' => 'required_with:prices|exists:currencies,id',
            'prices.*.monthly_price' => 'required_with:prices|numeric|min:0',
            'prices.*.annual_price' => 'required_with:prices|numeric|min:0',
        ]);

        $data = $request->except('prices');

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

        // Sync prices array if provided
        if ($request->has('prices') && is_array($prices)) {
            $syncedCurrencyIds = [];
            foreach ($prices as $p) {
                if (!empty($p['currency_id'])) {
                    $currId = (int) $p['currency_id'];
                    $syncedCurrencyIds[] = $currId;
                    $plan->prices()->updateOrCreate(
                        ['currency_id' => $currId],
                        [
                            'monthly_price' => (float) ($p['monthly_price'] ?? 0),
                            'annual_price' => (float) ($p['annual_price'] ?? 0),
                        ]
                    );
                }
            }
            $plan->prices()->whereNotIn('currency_id', $syncedCurrencyIds)->delete();
        }

        $plan->load(['prices.currency', 'currency']);

        return response()->json([
            'status' => 'success',
            'message' => 'Plan updated successfully.',
            'data' => $plan
        ]);
    }

    /**
     * Parse JSON encoded fields that might be sent via FormData.
     */
    private function parseJsonFields(Request $request): array
    {
        $prices = $request->input('prices');
        if (is_string($prices)) {
            $decoded = json_decode($prices, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $prices = $decoded;
                $request->merge(['prices' => $prices]);
            }
        }

        if (is_string($request->features)) {
            $decoded = json_decode($request->features, true);
            if (is_array($decoded)) {
                $request->merge(['features' => $decoded]);
            }
        }

        if (is_string($request->integrations)) {
            $decoded = json_decode($request->integrations, true);
            if (is_array($decoded)) {
                $request->merge(['integrations' => $decoded]);
            }
        }

        // Auto-populate legacy fields from first price if not sent directly
        $pricesArray = $request->input('prices');
        if (!empty($pricesArray) && is_array($pricesArray) && isset($pricesArray[0])) {
            $first = $pricesArray[0];
            if (!$request->filled('monthly_price') && isset($first['monthly_price'])) {
                $request->merge(['monthly_price' => $first['monthly_price']]);
            }
            if (!$request->filled('annual_price') && isset($first['annual_price'])) {
                $request->merge(['annual_price' => $first['annual_price']]);
            }
            if (!$request->filled('currency_id') && isset($first['currency_id'])) {
                $request->merge(['currency_id' => $first['currency_id']]);
            }
        }

        return (array) $request->input('prices', []);
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
