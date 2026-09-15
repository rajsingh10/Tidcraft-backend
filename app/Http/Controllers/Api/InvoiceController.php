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
        $query = Payment::with(['tenant.client']);

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
                $query->where('status', '!=', 'success');
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
            // Assuming due date is same as issue date for immediate payments, or +14 days for pending
            $dueDate = $payment->status === 'success' ? $issueDate : Carbon::parse($issueDate)->addDays(14);

            return [
                'id' => $payment->id,
                'invoice_number' => 'INV-' . Carbon::parse($issueDate)->format('Y') . '-' . str_pad($payment->id, 3, '0', STR_PAD_LEFT),
                'client_tenant' => [
                    'business_name' => $payment->tenant->business_name ?? 'Unknown',
                    'client_name' => $payment->tenant->client->name ?? 'Unknown',
                ],
                'issue_date' => Carbon::parse($issueDate)->format('M d, Y'),
                'due_date' => Carbon::parse($dueDate)->format('M d, Y'),
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method ?? 'Unknown',
                'status' => $payment->status === 'success' ? 'Paid' : 'Pending',
                'payment_status' => $payment->status,
                'pdf_url' => url("/api/invoices/{$payment->id}/pdf")
            ];
        });

        $payments->setCollection($invoices);

        // Calculate counts for filters
        $totalPaid = Payment::where('status', 'success')->count();
        $totalPending = Payment::where('status', '!=', 'success')->count();

        return response()->json([
            'status' => 'success',
            'summary' => [
                'total' => $totalPaid + $totalPending,
                'paid' => $totalPaid,
                'pending' => $totalPending
            ],
            'data' => $payments
        ]);
    }

    /**
     * Get single invoice details by ID
     */
    public function show($id)
    {
        $payment = Payment::with(['tenant.client'])->find($id);

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found.'
            ], 404);
        }

        $issueDate = $payment->create_at ?? $payment->created_at ?? now();
        $dueDate = $payment->status === 'success' ? $issueDate : Carbon::parse($issueDate)->addDays(14);

        $invoice = [
            'id' => $payment->id,
            'invoice_number' => 'INV-' . Carbon::parse($issueDate)->format('Y') . '-' . str_pad($payment->id, 3, '0', STR_PAD_LEFT),
            'client_tenant' => [
                'business_name' => $payment->tenant->business_name ?? 'Unknown',
                'client_name' => $payment->tenant->client->name ?? 'Unknown',
            ],
            'issue_date' => Carbon::parse($issueDate)->format('M d, Y'),
            'due_date' => Carbon::parse($dueDate)->format('M d, Y'),
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method ?? 'Unknown',
            'status' => $payment->status === 'success' ? 'Paid' : 'Pending',
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
    public function downloadPdf($id)
    {
        $payment = Payment::with(['tenant.client'])->findOrFail($id);
        
        // Return a JSON response for now until a PDF library (like dompdf) is integrated
        return response()->json([
            'status' => 'success',
            'message' => 'PDF generation will be supported here.',
            'invoice_id' => $id
        ]);
    }
}
