<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Inquiry;
use Illuminate\Support\Facades\Validator;

class InquiryController extends Controller
{
    public function index()
    {
        $inquiries = Inquiry::all();
        return response()->json(['status' => 'success', 'data' => $inquiries]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'project_id' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        if (auth()->check()) {
            $data['create_by'] = auth()->id();
            $data['update_by'] = auth()->id();
        }

        try {
            $inquiry = Inquiry::create($data);

            // Create Admin Notification
            \App\Models\AdminNotification::create([
                'type' => 'inquiry',
                'title' => 'New Inquiry Received',
                'message' => 'A new inquiry has been received from ' . $inquiry->customer_name . '.',
                'related_id' => $inquiry->id,
                'client_name' => $inquiry->customer_name,
                'is_read' => false,
            ]);

            // Dispatch Emails safely
            try {
                $adminEmail = env('MAIL_FROM_ADDRESS', 'admin@example.com');
                // You can also get it from settings if available
                $settings = \App\Models\Setting::where('key', 'smtp_from_address')->first();
                if ($settings && $settings->value) {
                    $adminEmail = $settings->value;
                }

                \Illuminate\Support\Facades\Mail::to($adminEmail)->send(new \App\Mail\AdminInquiryNotification($inquiry));
                \Illuminate\Support\Facades\Mail::to($inquiry->email)->send(new \App\Mail\ClientInquiryConfirmation($inquiry));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Inquiry Email failed: ' . $e->getMessage());
                // Non-blocking: continue without throwing error for email failure
            }

            return response()->json([
                'success' => true,
                'message' => 'Your inquiry has been received successfully. Our team will contact you soon.',
                'inquiry_id' => $inquiry->id
            ], 201);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Inquiry creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving your inquiry. Please try again later.'
            ], 500);
        }
    }

    public function show(string $id)
    {
        $inquiry = Inquiry::find($id);
        if (!$inquiry) {
            return response()->json(['status' => 'error', 'message' => 'Inquiry not found.'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $inquiry]);
    }

    public function update(Request $request, string $id)
    {
        $inquiry = Inquiry::find($id);
        if (!$inquiry) {
            return response()->json(['status' => 'error', 'message' => 'Inquiry not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'project_id' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (auth()->check()) {
            $data['update_by'] = auth()->id();
        }

        $inquiry->update($data);
        return response()->json(['status' => 'success', 'message' => 'Inquiry updated.', 'data' => $inquiry]);
    }

    public function destroy(string $id)
    {
        $inquiry = Inquiry::find($id);
        if (!$inquiry) {
            return response()->json(['status' => 'error', 'message' => 'Inquiry not found.'], 404);
        }
        
        if (auth()->check()) {
            $inquiry->delete_by = auth()->id();
            $inquiry->save();
        }
        
        $inquiry->delete();
        return response()->json(['status' => 'success', 'message' => 'Inquiry deleted.']);
    }
}
