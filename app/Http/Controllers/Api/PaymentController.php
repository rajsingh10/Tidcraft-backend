<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Payment;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        // 1. Calculate Metrics
        $activeSubscriptionsCount = \App\Models\Subscription::where('status', 'active')->count();
        $pastDueSubscriptionsCount = \App\Models\Subscription::where('status', 'past_due')->count();
        
        // MRR Calculation (Sum of monthly_price of plans for active subscriptions)
        $mrr = (float) \App\Models\Subscription::where('subscriptions.status', 'active')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.monthly_price');
        
        $arr = $mrr * 12;

        $metrics = [
            'mrr' => $mrr,
            'arr' => $arr,
            'active_subscriptions' => $activeSubscriptionsCount,
            'past_due' => $pastDueSubscriptionsCount,
        ];

        // 2. Build Query for Transactions (Payments)
        $query = Payment::with(['tenant.subscriptions' => function ($q) {
            $q->latest('create_at'); // Get the latest subscription for status/plan display
        }, 'tenant.subscriptions.plan']);

        // Search Filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('tenant', function($tq) use ($search) {
                      $tq->where('business_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('tenant.subscriptions.plan', function($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Status Filter
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        $perPage = $request->get('per_page', 10);
        
        // Use create_at if that's the custom timestamp column, else fallback to latest()
        if (\Illuminate\Support\Facades\Schema::hasColumn('payments', 'create_at')) {
            $query->latest('create_at');
        } else {
            $query->latest();
        }

        $payments = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'metrics' => $metrics,
            'data' => $payments
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|exists:tenants,id',
            'transaction_id' => 'required|string',
            'amount' => 'required|numeric',
            'currency' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $payment = Payment::create($validator->validated());
        return response()->json(['status' => 'success', 'message' => 'Payment logged.', 'data' => $payment], 201);
    }

    public function show(string $id)
    {
        $payment = Payment::with(['tenant'])->find($id);
        if (!$payment) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found.'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $payment]);
    }

    public function update(Request $request, string $id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'tenant_id' => 'nullable|exists:tenants,id',
            'transaction_id' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'currency' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $payment->update($validator->validated());
        return response()->json(['status' => 'success', 'message' => 'Payment updated.', 'data' => $payment]);
    }

    public function destroy(string $id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found.'], 404);
        }
        
        $payment->delete();
        return response()->json(['status' => 'success', 'message' => 'Payment deleted.']);
    }
}
