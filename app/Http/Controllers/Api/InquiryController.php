<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Inquiry;
use Illuminate\Support\Facades\Validator;

class InquiryController extends Controller
{
    public function index(Request $request)
    {
        $query = Inquiry::query();
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $inquiries = $query->get();
        return response()->json(['status' => 'success', 'data' => $inquiries]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'project_id' => 'nullable|max:255',
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
        
        // Force the default status to 'new' on creation
        $data['status'] = 'new';
        
        if (auth()->check()) {
            $data['create_by'] = auth()->id();
            $data['update_by'] = auth()->id();
        }

        $rawProjectId = $data['project_id'] ?? null;
        if (isset($data['project_id']) && !is_numeric($data['project_id'])) {
            // inquiries.project_id is unsignedBigInteger in database; store null if text was passed
            $data['project_id'] = null;
        }

        try {
            $inquiry = Inquiry::create($data);

            // Create Admin Notification
            try {
                \App\Models\AdminNotification::create([
                    'type' => 'inquiry',
                    'title' => 'New Inquiry Received',
                    'message' => 'A new inquiry has been received from ' . $inquiry->customer_name . '.',
                    'related_id' => $inquiry->id,
                    'client_name' => $inquiry->customer_name,
                    'is_read' => false,
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to create admin notification for inquiry: ' . $e->getMessage());
            }

            // Company information from settings
            $companyName = \App\Models\Setting::where('key', 'company_name')->value('value') ?? config('app.name', 'TidCraft');
            $companyEmail = \App\Models\Setting::where('key', 'company_email')->value('value') ?? config('mail.from.address');
            $companyPhone = \App\Models\Setting::where('key', 'company_phone')->value('value') ?? '';

            // Template replacement variables and objects for DynamicEmail & Blade
            $replacements = [
                'inquiry' => $inquiry,
                'companyName' => $companyName,
                'companyEmail' => $companyEmail,
                'companyPhone' => $companyPhone,
                '{name}' => $inquiry->customer_name,
                '{{name}}' => $inquiry->customer_name,
                '{customer_name}' => $inquiry->customer_name,
                '{{customer_name}}' => $inquiry->customer_name,
                '{email}' => $inquiry->email,
                '{{email}}' => $inquiry->email,
                '{phone}' => $inquiry->phone ?? 'N/A',
                '{{phone}}' => $inquiry->phone ?? 'N/A',
                '{project_id}' => $rawProjectId ?? $inquiry->project_id ?? 'N/A',
                '{{project_id}}' => $rawProjectId ?? $inquiry->project_id ?? 'N/A',
                '{service}' => $rawProjectId ?? $inquiry->project_id ?? 'N/A',
                '{{service}}' => $rawProjectId ?? $inquiry->project_id ?? 'N/A',
                '{description}' => $inquiry->description ?? 'N/A',
                '{{description}}' => $inquiry->description ?? 'N/A',
                '{message}' => $inquiry->description ?? 'N/A',
                '{{message}}' => $inquiry->description ?? 'N/A',
                '{inquiry_id}' => $inquiry->id,
                '{{inquiry_id}}' => $inquiry->id,
                '{company_name}' => $companyName,
                '{{company_name}}' => $companyName,
                '{company_email}' => $companyEmail,
                '{{company_email}}' => $companyEmail,
                '{company_phone}' => $companyPhone,
                '{{company_phone}}' => $companyPhone,
            ];

            // 1. Send Email to Admin
            try {
                $adminEmail = \App\Models\Setting::where('key', 'company_email')->value('value');
                if (!$adminEmail) {
                    $superAdmin = \App\Models\User::role('SuperAdmin')->first();
                    $adminEmail = $superAdmin ? $superAdmin->email : null;
                }
                if (!$adminEmail) {
                    $adminEmail = \App\Models\Setting::where('key', 'mail_from_address')->value('value');
                }
                if (!$adminEmail) {
                    $adminEmail = config('mail.from.address');
                }

                if ($adminEmail) {
                    $adminTemplate = \App\Models\EmailTemplate::whereIn('slug', ['Admin_Inquiry', 'admin_inquiry', 'admin-inquiry', 'new-inquiry-admin'])
                        ->where('status', 'active')
                        ->first();

                    if ($adminTemplate) {
                        \Illuminate\Support\Facades\Mail::to($adminEmail)->send(new \App\Mail\DynamicEmail($adminTemplate, $replacements));
                    } else {
                        \Illuminate\Support\Facades\Mail::to($adminEmail)->send(new \App\Mail\AdminInquiryNotification($inquiry));
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send admin inquiry notification email: ' . $e->getMessage());
            }

            // 2. Send Confirmation Email to Client / Customer
            try {
                if (!empty($inquiry->email)) {
                    $clientTemplate = \App\Models\EmailTemplate::whereIn('slug', ['Your_Inquiry_Has_Been_Received', 'your_inquiry_has_been_received', 'client-inquiry', 'client_inquiry', 'inquiry-received', 'inquiry_confirmation'])
                        ->where('status', 'active')
                        ->first();

                    if ($clientTemplate) {
                        \Illuminate\Support\Facades\Mail::to($inquiry->email)->send(new \App\Mail\DynamicEmail($clientTemplate, $replacements));
                    } else {
                        \Illuminate\Support\Facades\Mail::to($inquiry->email)->send(new \App\Mail\ClientInquiryConfirmation($inquiry));
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send client inquiry confirmation email: ' . $e->getMessage());
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
            'status' => 'nullable|in:new,in_review,resolved',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (auth()->check()) {
            $data['update_by'] = auth()->id();
        }

        $inquiry->update($data);
        
        if (!auth()->check() || !auth()->user()->hasRole('SuperAdmin')) {
            \App\Models\AdminNotification::create([
                'type' => 'inquiry',
                'title' => 'Inquiry Updated',
                'message' => 'Inquiry from ' . $inquiry->customer_name . ' has been updated.',
                'related_id' => $inquiry->id,
                'client_name' => $inquiry->customer_name,
                'is_read' => false,
            ]);
        }

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

    // Status change API
    public function changeStatus(Request $request, string $id)
    {
        $inquiry = Inquiry::find($id);
        if (!$inquiry) {
            return response()->json(['status' => 'error', 'message' => 'Inquiry not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:new,in_review,resolved',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $inquiry->update([
            'status' => $request->status,
            'update_by' => auth()->id(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Inquiry status updated.', 'data' => $inquiry]);
    }
}
