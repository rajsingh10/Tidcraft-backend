<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Services\AuditLogger;

class AuthController extends Controller
{
    /**
     * Admin login API
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
            // Try to find user to log failed attempt
            $user = \App\Models\User::where('email', $request->email)->first();
            AuditLogger::log('Failed Login', 'Login Attempt Failed', 'Invalid login credentials.', null, null, $user ? $user->id : null);
            
            return response()->json([
                'message' => 'Invalid login credentials.'
            ], 401);
        }

        $user = Auth::user();

        // Check if the user has the SuperAdmin role
        if (!$user->hasRole('SuperAdmin')) {
            AuditLogger::log('Failed Login', 'Unauthorized Login Attempt', 'User lacks SuperAdmin role.', null, null, $user->id);
            return response()->json([
                'message' => 'Unauthorized. Only SuperAdmins can login.'
            ], 403);
        }

        // Generate token
        $token = $user->createToken('admin-token')->plainTextToken;

        AuditLogger::log('Admin Login', 'User Logged In', 'SuperAdmin login successful.', null, null, $user->id);

        // Prepare login details for notification
        $loginDetails = [
            'name' => $user->name,
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'date_time' => now()->toDateTimeString(),
        ];

        // Send Notification Email if setting allows
        $sendEmail = \App\Models\Setting::where('key', 'admin_login_mail_send')->value('value');
        
        // Treat as true by default, or explicitly check if it's set to true/1
        if ($sendEmail === null || $sendEmail === 'true' || $sendEmail === '1' || $sendEmail === 1 || $sendEmail === true || $sendEmail === 'on') {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\AdminLoginNotification($loginDetails));
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout API
     */
    public function logout(Request $request)
    {
        AuditLogger::log('Admin Logout', 'User Logged Out', 'Admin logout successful.');
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Change Password API
     */
    /**
     * Get Admin Profile API
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        
        // Convert profile_image to full url if needed
        if ($user->profile_image && !str_starts_with($user->profile_image, 'http')) {
            $user->profile_image = asset($user->profile_image);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'contact' => $user->contact,
                'profile_image' => $user->profile_image,
            ]
        ]);
    }

    /**
     * Update Admin Profile API
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'contact' => 'nullable|string|max:20',
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

        $oldValues = $user->only(['name', 'email', 'contact', 'profile_image']);

        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profiles', 'public');
            $data['profile_image'] = '/storage/' . $path;
        }

        // Update fields if provided
        if (isset($data['name'])) $user->name = $data['name'];
        if (isset($data['email'])) $user->email = $data['email'];
        if (isset($data['contact'])) $user->contact = $data['contact'];
        if (isset($data['profile_image'])) $user->profile_image = $data['profile_image'];

        $user->save();

        $newValues = $user->only(['name', 'email', 'contact', 'profile_image']);
        AuditLogger::log('Profile Updated', 'Admin Profile Changed', 'Admin updated their profile.', $oldValues, $newValues);

        // Convert profile_image to full url for response
        if ($user->profile_image && !str_starts_with($user->profile_image, 'http')) {
            $user->profile_image = asset($user->profile_image);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully.',
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'contact' => $user->contact,
                'profile_image' => $user->profile_image,
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

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password does not match.'
            ], 400);
        }

        $user->password = \Illuminate\Support\Facades\Hash::make($request->new_password);
        $user->save();

        AuditLogger::log('Password Changed', 'User Password Changed', 'Admin changed their password.');

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully.'
        ]);
    }
}
