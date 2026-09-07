<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Subscription;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::with(['tenant', 'plan'])->get();
        return response()->json(['status' => 'success', 'data' => $subscriptions]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|exists:tenants,id',
            'plan_id' => 'required|exists:plans,id',
            'status' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $subscription = Subscription::create($validator->validated());
        return response()->json(['status' => 'success', 'message' => 'Subscription created.', 'data' => $subscription], 201);
    }

    public function show(string $id)
    {
        $subscription = Subscription::with(['tenant', 'plan'])->find($id);
        if (!$subscription) {
            return response()->json(['status' => 'error', 'message' => 'Subscription not found.'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $subscription]);
    }

    public function update(Request $request, string $id)
    {
        $subscription = Subscription::find($id);
        if (!$subscription) {
            return response()->json(['status' => 'error', 'message' => 'Subscription not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'tenant_id' => 'nullable|exists:tenants,id',
            'plan_id' => 'nullable|exists:plans,id',
            'status' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $subscription->update($validator->validated());
        return response()->json(['status' => 'success', 'message' => 'Subscription updated.', 'data' => $subscription]);
    }

    public function destroy(string $id)
    {
        $subscription = Subscription::find($id);
        if (!$subscription) {
            return response()->json(['status' => 'error', 'message' => 'Subscription not found.'], 404);
        }
        
        $subscription->delete();
        return response()->json(['status' => 'success', 'message' => 'Subscription deleted.']);
    }
}
