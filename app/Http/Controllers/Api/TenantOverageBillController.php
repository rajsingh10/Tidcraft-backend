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
        
        // Clone the query BEFORE paginating so we can use it for accurate summaries
        $summaryQuery = clone $query;
        
        $bills = $query->latest('id')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $bills,
            'summary' => [
                'total_bills' => $summaryQuery->count(),
                'total_paid_amount' => round((float) (clone $summaryQuery)->whereIn('status', ['paid', 'success'])->sum('total_amount'), 2),
                'total_pending_amount' => round((float) (clone $summaryQuery)->where('status', 'pending')->sum('total_amount'), 2),
                'pending_count' => (clone $summaryQuery)->where('status', 'pending')->count(),
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
        $user = $request->user();
        if ($user && $user->hasRole('Client')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized. Only admins can manually mark bills as paid.'], 403);
        }

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

    /**
     * Verify online payment from client (e.g., Razorpay success).
     * This marks the current pending bill as paid, allowing a new pending bill 
     * to be generated for subsequent usages.
     */
    public function verifyPayment(Request $request, $id)
    {
        $request->validate([
            'transaction_id' => 'required|string',
            'payment_method' => 'nullable|string',
            'razorpay_payment_id' => 'nullable|string',
            'razorpay_payment_link_id' => 'nullable|string',
            'razorpay_payment_link_status' => 'nullable|string',
            'razorpay_signature' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $user = $request->user();
        $query = TenantOverageBill::query();

        if ($user && $user->hasRole('Client')) {
            $query->where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                  ->orWhereHas('tenant', function ($tq) use ($user) {
                      $tq->where('client_id', $user->id)
                         ->orWhere('create_by', $user->id);
                  });
            });
        }

        $bill = $query->where('id', $id)->firstOrFail();

        if ($bill->isPaid()) {
            return response()->json(['status' => 'error', 'message' => 'Bill is already paid.'], 400);
        }

        $transactionId = $request->input('transaction_id');
        $paymentMethod = $request->input('payment_method', 'razorpay');

        $extraData = array_filter($request->only([
            'razorpay_payment_id',
            'razorpay_payment_link_id',
            'razorpay_payment_link_status',
            'razorpay_signature',
            'status'
        ]));

        // Note: You can add Razorpay signature verification logic here if required.

        // Mark the bill as paid and record the payment in the `payments` table
        OverageBillingService::recordBillPayment($bill, $transactionId, $paymentMethod, $extraData);

        // When the frontend reloads and sends new usage, a new pending bill will automatically be created.

        return response()->json([
            'status' => 'success',
            'message' => 'Payment verified successfully. Overage bill paid.',
            'data' => $bill->fresh(['payments'])
        ]);
    }

    /**
     * Manually trigger the overage billing generation command.
     * Can be run via the admin panel.
     */
    public function generateAll(Request $request)
    {
        $user = $request->user();
        if ($user && $user->hasRole('Client')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized. Only admins can trigger overage generation.'], 403);
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('tenants:generate-overage-billing', ['--all' => true]);
            $output = \Illuminate\Support\Facades\Artisan::output();

            $query = TenantOverageBill::with(['tenant.client', 'tenant.product', 'tenant.plan', 'payments'])
                        ->orderBy('id', 'desc');
            $bills = $query->paginate(15);

            $summaryQuery = clone $query;

            return response()->json([
                'status' => 'success',
                'message' => 'Overage billing generation completed.',
                'output' => trim($output),
                'data' => $bills,
                'summary' => [
                    'total_bills' => $summaryQuery->count(),
                    'total_paid_amount' => round((float) (clone $summaryQuery)->whereIn('status', ['paid', 'success'])->sum('total_amount'), 2),
                    'total_pending_amount' => round((float) (clone $summaryQuery)->where('status', 'pending')->sum('total_amount'), 2),
                    'pending_count' => (clone $summaryQuery)->where('status', 'pending')->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to run overage billing generation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
