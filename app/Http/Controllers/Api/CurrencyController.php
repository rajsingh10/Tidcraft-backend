<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Plan;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CurrencyController extends Controller
{
    /**
     * Display a listing of currencies.
     * Supports search, is_active filter, and optional pagination.
     */
    public function index(Request $request)
    {
        $query = Currency::query();

        // Search filter (code, name, symbol)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        // Active status filter
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        } elseif ($request->has('status')) {
            $query->where('is_active', in_array($request->status, ['active', '1', 1, true], true));
        }

        // Pagination or full list
        if ($request->has('paginate') && filter_var($request->paginate, FILTER_VALIDATE_BOOLEAN)) {
            $perPage = (int) $request->get('per_page', 10);
            $currencies = $query->orderBy('name')->paginate($perPage);
        } else {
            $currencies = $query->orderBy('name')->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $currencies
        ]);
    }

    /**
     * Store a newly created currency (matches "Add Currency" modal).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:10|unique:currencies,code',
            'name' => 'required|string|max:100',
            'symbol' => 'required|string|max:10',
            'is_active' => 'nullable|boolean',
            'status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['code'] = strtoupper(trim($data['code']));

        // Resolve active status from is_active checkbox or status string
        $isActive = true;
        if ($request->has('is_active')) {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
        } elseif ($request->has('status')) {
            $isActive = in_array($request->status, ['active', '1', 1, true], true);
        }
        $data['is_active'] = $isActive;
        $data['create_by'] = auth()->id();
        $data['update_by'] = auth()->id();

        $currency = Currency::create($data);

        AuditLogger::log('Currency Created', 'Insert', "Currency {$currency->code} ({$currency->name}) was created.");

        return response()->json([
            'status' => 'success',
            'message' => 'Currency created successfully.',
            'data' => $currency
        ], 201);
    }

    /**
     * Display the specified currency.
     */
    public function show($id)
    {
        $currency = Currency::find($id);

        if (!$currency) {
            return response()->json([
                'status' => 'error',
                'message' => 'Currency not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $currency
        ]);
    }

    /**
     * Update the specified currency in storage.
     */
    public function update(Request $request, $id)
    {
        $currency = Currency::find($id);

        if (!$currency) {
            return response()->json([
                'status' => 'error',
                'message' => 'Currency not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|required|string|max:10|unique:currencies,code,' . $currency->id,
            'name' => 'sometimes|required|string|max:100',
            'symbol' => 'sometimes|required|string|max:10',
            'is_active' => 'nullable|boolean',
            'status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }

        if ($request->has('is_active')) {
            $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
        } elseif ($request->has('status')) {
            $data['is_active'] = in_array($request->status, ['active', '1', 1, true], true);
        }

        $data['update_by'] = auth()->id();

        $currency->update($data);

        AuditLogger::log('Currency Updated', 'Update', "Currency {$currency->code} was updated.");

        return response()->json([
            'status' => 'success',
            'message' => 'Currency updated successfully.',
            'data' => $currency
        ]);
    }

    /**
     * Remove the specified currency (soft delete).
     */
    public function destroy($id)
    {
        $currency = Currency::find($id);

        if (!$currency) {
            return response()->json([
                'status' => 'error',
                'message' => 'Currency not found.'
            ], 404);
        }

        // Prevent deletion if associated with existing plans
        if (Plan::where('currency_id', $currency->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete currency because it is currently associated with one or more plans.'
            ], 400);
        }

        $currency->delete_by = auth()->id();
        $currency->save();
        $currency->delete();

        AuditLogger::log('Currency Deleted', 'Delete', "Currency {$currency->code} was deleted.");

        return response()->json([
            'status' => 'success',
            'message' => 'Currency deleted successfully.'
        ]);
    }
}
