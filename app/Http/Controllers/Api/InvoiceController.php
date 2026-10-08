<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    /**
     * Get all invoices (payments mapped to invoice format)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Payment::with(['tenant.client', 'tenant.product', 'tenant.plan']);

        // Scope to client's own invoices if logged in as Client
        if ($user && $user->hasRole('Client')) {
            $query->whereHas('tenant', function($tq) use ($user) {
                $tq->where('client_id', $user->id)
                   ->orWhere('create_by', $user->id);
            });
        }

        // Search Filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhereHas('tenant', function($tq) use ($search) {
                      $tq->where('business_name', 'like', "%{$search}%")
                         ->orWhereHas('client', function($cq) use ($search) {
                             $cq->where('name', 'like', "%{$search}%");
                         });
                  });
            });
        }

        // Status Filter
        if ($request->has('status') && !empty($request->status)) {
            $status = strtolower($request->status);
            if ($status === 'paid') {
                $query->where('status', 'success');
            } elseif ($status === 'pending') {
                $query->where('status', 'pending');
            } elseif ($status === 'failed') {
                $query->where('status', 'failed');
            } elseif (in_array($status, ['canceled', 'cancelled'])) {
                $query->whereIn('status', ['canceled', 'cancelled']);
            } else {
                $query->where('status', $request->status);
            }
        }

        $perPage = $request->get('per_page', 10);
        
        $sortColumn = \Illuminate\Support\Facades\Schema::hasColumn('payments', 'create_at') ? 'create_at' : 'created_at';
        $payments = $query->latest($sortColumn)->paginate($perPage);

        // Map to invoice structure
        $invoices = $payments->getCollection()->map(function ($payment) {
            $issueDate = $payment->create_at ?? $payment->created_at ?? now();
            // Due date: same day if paid, +14 days for pending/failed
            $dueDate = $payment->status === 'success' ? $issueDate : Carbon::parse($issueDate)->addDays(14);

            $statusLabel = match($payment->status) {
                'success' => 'Paid',
                'failed' => 'Failed',
                'canceled', 'cancelled' => 'Cancelled',
                default => 'Pending',
            };

            return [
                'id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
                'client_tenant' => [
                    'business_name' => $payment->tenant->business_name ?? 'Unknown',
                    'client_name' => $payment->tenant->client->name ?? 'Unknown',
                ],
                'product_name' => $payment->tenant->product->name ?? 'N/A',
                'plan_name' => $payment->tenant->plan->name ?? 'N/A',
                'billing_cycle' => $payment->billing_cycle ?? 'monthly',
                'issue_date' => Carbon::parse($issueDate)->format('M d, Y'),
                'due_date' => Carbon::parse($dueDate)->format('M d, Y'),
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? 'INR',
                'payment_method' => $payment->payment_method ?? 'Unknown',
                'status' => $statusLabel,
                'payment_status' => $payment->status,
                'pdf_url' => url("/api/invoices/{$payment->id}/pdf")
            ];
        });

        $payments->setCollection($invoices);

        // Calculate counts for filters
        $countsQuery = Payment::query();
        if ($user && $user->hasRole('Client')) {
            $countsQuery->whereHas('tenant', function($tq) use ($user) {
                $tq->where('client_id', $user->id)
                   ->orWhere('create_by', $user->id);
            });
        }
        $totalPaid = (clone $countsQuery)->where('status', 'success')->count();
        $totalPending = (clone $countsQuery)->where('status', 'pending')->count();
        $totalFailed = (clone $countsQuery)->where('status', 'failed')->count();
        $totalCanceled = (clone $countsQuery)->whereIn('status', ['canceled', 'cancelled'])->count();

        return response()->json([
            'status' => 'success',
            'summary' => [
                'total' => $totalPaid + $totalPending + $totalFailed + $totalCanceled,
                'paid' => $totalPaid,
                'pending' => $totalPending,
                'failed' => $totalFailed,
                'canceled' => $totalCanceled,
            ],
            'data' => $payments
        ]);
    }

    /**
     * Get single invoice details by ID
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $query = Payment::with(['tenant.client', 'tenant.product', 'tenant.plan']);

        if ($user && $user->hasRole('Client')) {
            $query->whereHas('tenant', function($tq) use ($user) {
                $tq->where('client_id', $user->id)
                   ->orWhere('create_by', $user->id);
            });
        }

        $payment = $query->find($id);

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found.'
            ], 404);
        }

        $issueDate = $payment->create_at ?? $payment->created_at ?? now();
        $dueDate = $payment->status === 'success' ? $issueDate : Carbon::parse($issueDate)->addDays(14);

        $statusLabel = match($payment->status) {
            'success' => 'Paid',
            'failed' => 'Failed',
            'canceled', 'cancelled' => 'Cancelled',
            default => 'Pending',
        };

        $invoice = [
            'id' => $payment->id,
            'invoice_number' => $payment->invoice_number,
            'client_tenant' => [
                'business_name' => $payment->tenant->business_name ?? 'Unknown',
                'client_name' => $payment->tenant->client->name ?? 'Unknown',
            ],
            'product' => [
                'id' => $payment->tenant->product_id ?? null,
                'name' => $payment->tenant->product->name ?? 'N/A',
            ],
            'plan' => [
                'id' => $payment->tenant->plan_id ?? null,
                'name' => $payment->tenant->plan->name ?? 'N/A',
            ],
            'billing_cycle' => $payment->billing_cycle ?? 'monthly',
            'issue_date' => Carbon::parse($issueDate)->format('M d, Y'),
            'due_date' => Carbon::parse($dueDate)->format('M d, Y'),
            'amount' => $payment->amount,
            'currency' => $payment->currency ?? 'INR',
            'payment_method' => $payment->payment_method ?? 'Unknown',
            'status' => $statusLabel,
            'payment_status' => $payment->status,
            'transaction_id' => $payment->transaction_id,
            'customer_details' => $payment->customer_details,
            'pdf_url' => url("/api/invoices/{$payment->id}/pdf")
        ];

        return response()->json([
            'status' => 'success',
            'data' => $invoice
        ]);
    }

    /**
     * Generate / Download Invoice PDF
     */
    public function downloadPdf(Request $request, $id)
    {
        $user = $request->user();
        $query = Payment::with(['tenant.client', 'tenant.product', 'tenant.plan', 'tenant.domains']);

        if ($user && $user->hasRole('Client')) {
            $query->whereHas('tenant', function($tq) use ($user) {
                $tq->where('client_id', $user->id)
                   ->orWhere('create_by', $user->id);
            });
        }

        $payment = $query->findOrFail($id);
        
        $pdfService = new \App\Services\InvoicePdfService();
        $pdfContent = $pdfService->generate($payment);
        $fileName = 'Invoice-' . ($payment->invoice_number ?: $payment->id) . '.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Cancel an invoice / pending payment
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $query = Payment::with(['tenant']);

        if ($user && $user->hasRole('Client')) {
            $query->whereHas('tenant', function($tq) use ($user) {
                $tq->where('client_id', $user->id)
                   ->orWhere('create_by', $user->id);
            });
        }

        $payment = $query->find($id);

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found.'
            ], 404);
        }

        if ($payment->status === 'success') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot cancel an already paid invoice.'
            ], 400);
        }

        $payment->status = 'canceled';
        $payment->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice marked as cancelled.',
            'data' => [
                'id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
                'status' => 'Cancelled',
                'payment_status' => 'canceled'
            ]
        ]);
    }
}
