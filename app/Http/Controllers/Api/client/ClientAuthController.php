<?php

namespace App\Http\Controllers\Api\client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use App\Mail\ForgotPasswordOtpMail;
use App\Mail\ClientRegisteredMail;
use App\Mail\AdminNewClientMail;
use App\Models\AdminNotification;
use App\Models\Setting;
class ClientAuthController extends Controller
{
    /**
     * Client Register API 
     */
    public function register(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'company_name' => $request->company_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Ensure Client role exists and assign it
        $role = Role::firstOrCreate(['name' => 'Client']);
        $user->assignRole($role);

        // Send email to client
        try {
            $template = \App\Models\EmailTemplate::where('slug', 'register')->first();
            if ($template) {
                if ($template->status === 'active') {
                    $imageUrl = (!empty($template->images) && isset($template->images[0])) ? url($template->images[0]) : '';
                    $globalCompanyName = Setting::where('key', 'company_name')->value('value') ?? 'Tidcraft';
                    Mail::to($user->email)->send(new \App\Mail\DynamicEmail($template, [
                        '{name}' => $user->name,
                        '{email}' => $user->email,
                        '{company_name}' => !empty($user->company_name) ? $user->company_name : $globalCompanyName,
                        '{image}' => $imageUrl,
                        '{login_url}' => url('/login'),
                    ]));
                }
            } else {
                Mail::to($user->email)->send(new ClientRegisteredMail($user));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send registration email to client: ' . $e->getMessage());
        }

        // Send email to admin
        try {
            $adminEmail = Setting::where('key', 'company_email')->value('value') ?? 'admin@example.com';
            Mail::to($adminEmail)->send(new AdminNewClientMail($user));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send registration email to admin: ' . $e->getMessage());
        }

        // Create Admin Notification
        try {
            AdminNotification::create([
                'type' => 'new_client',
                'title' => 'New Client Registered',
                'message' => 'A new client has registered: ' . $user->name . ' (' . $user->email . ')',
                'related_id' => $user->id,
                'client_name' => $user->name,
                'is_read' => false,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create admin notification: ' . $e->getMessage());
        }

        $token = $user->createToken('client-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Client Login API
     */
    public function login(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid login credentials.'
            ], 401);
        }

        $user = Auth::user();

        // Ensure the user has the Client role
        if (!$user->hasRole('Client')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Only Clients can login here.'
            ], 403);
        }

        // Generate token
        $token = $user->createToken('client-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Client Logout API
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get Client Profile API
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        // Convert profile_image to full url if needed
        if (isset($user->profile_image) && $user->profile_image && !str_starts_with($user->profile_image, 'http')) {
            $user->profile_image = asset($user->profile_image);
        }

        $purchases = \App\Models\Tenant::with(['product', 'plan', 'domains', 'payments', 'subscriptions'])
            ->where(function($query) use ($user) {
                $query->where('create_by', $user->id)
                      ->orWhere('client_id', $user->id);
            })
            ->orderBy('create_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'contact' => $user->contact ?? null,
                'company_name' => $user->company_name ?? null,
                'profile_image' => $user->profile_image ?? null,
                'purchases' => $purchases->isEmpty() ? null : $purchases,
            ]
        ]);
    }

    /**
     * Update Client Profile API
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'contact' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profiles', 'public');
            $data['profile_image'] = '/storage/' . $path;
        }

        // Update fields if provided
        if (isset($data['name'])) $user->name = $data['name'];
        if (isset($data['email'])) $user->email = $data['email'];
        if (isset($data['contact'])) $user->contact = $data['contact'];
        if (isset($data['company_name'])) $user->company_name = $data['company_name'];
        if (isset($data['address'])) $user->address = $data['address'];
        if (isset($data['profile_image'])) $user->profile_image = $data['profile_image'];

        $user->save();

        // Convert profile_image to full url for response
        if (isset($user->profile_image) && $user->profile_image && !str_starts_with($user->profile_image, 'http')) {
            $user->profile_image = asset($user->profile_image);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully.',
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'contact' => $user->contact ?? null,
                'company_name' => $user->company_name ?? null,
                'address' => $user->address ?? null,
                'profile_image' => $user->profile_image ?? null,
            ]
        ]);
    }

    /**
     * Change Password API
     */
    public function changePassword(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password does not match.'
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully.'
        ]);
    }

    /**
     * Forgot Password API (Send OTP)
     */
    public function forgotPassword(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Generate 6-digit OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));
        
        // Save OTP and expiration (15 minutes from now)
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(15);
        $user->save();

        // Send OTP via Email
        try {
            $slugs = ['forgot_password', 'forgot-password', 'Forgot_Password'];
            $template = \App\Models\EmailTemplate::whereIn('slug', $slugs)->first();
            if ($template) {
                if ($template->status === 'active') {
                    $imageUrl = (!empty($template->images) && isset($template->images[0])) ? url($template->images[0]) : '';
                    Mail::to($user->email)->send(new \App\Mail\DynamicEmail($template, [
                        '{name}' => $user->name,
                        '{email}' => $user->email,
                        '{otp}' => $otp,
                        '{otp_1}' => $otp[0],
                        '{otp_2}' => $otp[1],
                        '{otp_3}' => $otp[2],
                        '{otp_4}' => $otp[3],
                        '{otp_5}' => $otp[4],
                        '{otp_6}' => $otp[5],
                        '{image}' => $imageUrl,
                    ]));
                }
            } else {
                Mail::to($user->email)->send(new ForgotPasswordOtpMail($otp));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send forgot password email: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success', 
            'message' => 'An OTP has been sent to your email address.'
        ]);
    }

    /**
     * Verify OTP API
     */
    public function verifyOtp(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user->otp || $user->otp !== $request->otp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid OTP.'
            ], 400);
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP has expired.'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'OTP verified successfully. You may now reset your password.'
        ]);
    }
    
    /**
     * Reset Password API (Using OTP)
     */
    public function resetPassword(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            \Illuminate\Support\Facades\Log::info('Reset Password Validation Failed. Request Data:', $request->all());
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Verify OTP again
        if (!$user->otp || $user->otp !== $request->otp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid OTP.'
            ], 400);
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP has expired.'
            ], 400);
        }

        // Reset password and clear OTP
        $user->password = Hash::make($request->password);
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        return response()->json([
            'status' => 'success', 
            'message' => 'Password has been successfully reset.'
        ]);
    }
}
