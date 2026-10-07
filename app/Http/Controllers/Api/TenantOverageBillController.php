<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TenantOverageBill;
use App\Services\OverageBillingService;

class TenantOverageBillController extends Controller
{
    /**
     * Display a listing of all tenant overage bills for Super Admin,
     * or for the logged in Client user scoped to their tenants.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = TenantOverageBill::with(['tenant.client', 'tenant.product', 'tenant.plan', 'payments']);

        // Scope to client if user is a Client role
        if ($user && $user->hasRole('Client')) {
            $query->where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                  ->orWhereHas('tenant', function ($tq) use ($user) {
                      $tq->where('client_id', $user->id)
                         ->orWhere('create_by', $user->id);
                  });
            });
        }

        // Filter by Tenant ID
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        // Filter by Product ID
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by Bill Type (bookings or orders)
        if ($request->filled('bill_type')) {
            $query->where('bill_type', $request->bill_type);
        }

        // Filter by Status (pending, paid, failed, waived, etc.)
        if ($request->filled('status')) {
            $status = strtolower($request->status);
            if ($status === 'paid' || $status === 'success') {
                $query->whereIn('status', ['paid', 'success']);
            } else {
                $query->where('status', $status);
            }
        }

        // Search by bill number or business name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'LIKE', "%{$search}%")
                  ->orWhere('transaction_id', 'LIKE', "%{$search}%")
                  ->orWhereHas('tenant', function ($tq) use ($search) {
                      $tq->where('business_name', 'LIKE', "%{$search}%")
                         ->orWhere('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->get('per_page', 15);
        $bills = $query->latest('id')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $bills,
            'summary' => [
                'total_bills' => TenantOverageBill::count(),
                'total_paid_amount' => round((float) TenantOverageBill::whereIn('status', ['paid', 'success'])->sum('total_amount'), 2),
                'total_pending_amount' => round((float) TenantOverageBill::where('status', 'pending')->sum('total_amount'), 2),
                'pending_count' => TenantOverageBill::where('status', 'pending')->count(),
            ]
        ]);
    }

    /**
     * Display the specified overage bill details.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $query = TenantOverageBill::with(['tenant.client', 'tenant.product', 'tenant.plan', 'payments']);

        if ($user && $user->hasRole('Client')) {
            $query->where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                  ->orWhereHas('tenant', function ($tq) use ($user) {
                      $tq->where('client_id', $user->id)
                         ->orWhere('create_by', $user->id);
                  });
            });
        }

        $bill = is_numeric($id)
            ? $query->where('id', $id)->first()
            : $query->where('bill_number', $id)->first();

        if (!$bill) {
            return response()->json(['status' => 'error', 'message' => 'Overage bill not found.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $bill,
        ]);
    }

    /**
     * Mark overage bill as paid manually (by Super Admin).
     */
    public function markPaid(Request $request, $id)
    {
        $bill = is_numeric($id)
            ? TenantOverageBill::findOrFail($id)
            : TenantOverageBill::where('bill_number', $id)->firstOrFail();

        $transactionId = $request->input('transaction_id', 'MANUAL-' . strtoupper(uniqid()));
        $paymentMethod = $request->input('payment_method', 'offline_manual');

        OverageBillingService::recordBillPayment($bill, $transactionId, $paymentMethod);

        return response()->json([
            'status' => 'success',
            'message' => 'Overage bill marked as paid successfully.',
            'data' => $bill->fresh(['payments', 'tenant']),
        ]);
    }
}
