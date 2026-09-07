<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Payment;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['tenant'])->get();
        return response()->json(['status' => 'success', 'data' => $payments]);
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
