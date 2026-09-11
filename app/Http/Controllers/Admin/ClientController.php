<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\AuditLogger;

class ClientController extends Controller
{
    /**
     * Display a listing of the clients.
     */
    public function index()
    {
        $clients = User::all();
        $clients->transform(function($client) {
            if ($client->profile_image && !str_starts_with($client->profile_image, 'http')) {
                $client->profile_image = asset($client->profile_image);
            }
            return $client;
        });

        return response()->json([
            'status' => 'success',
            'data' => $clients
        ]);
    }

    /**
     * Store a newly created client in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'nullable|string|max:255',
            'owner_name' => 'required|string|max:255',
            'email_address' => 'required|string|email|max:255|unique:users,email',
            'phone_number' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'status' => 'nullable|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle file upload manually if present, otherwise just keep as string if it is a string URL
        $businessImage = null;
        if ($request->hasFile('business_image')) {
            $path = $request->file('business_image')->store('profiles', 'public');
            $businessImage = '/storage/' . $path;
        } else if ($request->has('business_image') && is_string($request->business_image)) {
            $businessImage = $request->business_image;
        }

        $client = User::create([
            'company_name' => $request->business_name,
            'name' => $request->owner_name,
            'email' => $request->email_address,
            'phone_number' => $request->phone_number,
            'profile_image' => $businessImage,
            'password' => Hash::make($request->password),
            'status' => $request->status ?? 'active',
        ]);

        if ($client->profile_image && !str_starts_with($client->profile_image, 'http')) {
            $client->profile_image = asset($client->profile_image);
        }

        AuditLogger::log('Client Created', 'Insert', "A new client ({$client->name}) was created.");

        return response()->json([
            'status' => 'success',
            'message' => 'Client created successfully',
            'data' => $client
        ], 201);
    }

    /**
     * Display the specified client.
     */
    public function show($id)
    {
        $client = User::find($id);

        if (!$client) {
            return response()->json([
                'status' => 'error',
                'message' => 'Client not found'
            ], 404);
        }

        if ($client->profile_image && !str_starts_with($client->profile_image, 'http')) {
            $client->profile_image = asset($client->profile_image);
        }

        return response()->json([
            'status' => 'success',
            'data' => $client
        ]);
    }

    /**
     * Update the specified client in storage.
     */
    public function update(Request $request, $id)
    {
        $client = User::find($id);

        if (!$client) {
            return response()->json([
                'status' => 'error',
                'message' => 'Client not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'business_name' => 'nullable|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'email_address' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($client->id)],
            'phone_number' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
            'status' => 'nullable|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('business_name')) $client->company_name = $request->business_name;
        if ($request->has('owner_name')) $client->name = $request->owner_name;
        if ($request->has('email_address')) $client->email = $request->email_address;
        if ($request->has('phone_number')) $client->phone_number = $request->phone_number;
        
        if ($request->hasFile('business_image')) {
            $path = $request->file('business_image')->store('profiles', 'public');
            $client->profile_image = '/storage/' . $path;
        } else if ($request->has('business_image') && is_string($request->business_image)) {
            $client->profile_image = $request->business_image;
        }
        if ($request->has('password')) $client->password = Hash::make($request->password);
        if ($request->has('status')) $client->status = $request->status;

        $client->save();

        if ($client->profile_image && !str_starts_with($client->profile_image, 'http')) {
            $client->profile_image = asset($client->profile_image);
        }

        AuditLogger::log('Client Updated', 'Update', "Client ({$client->name}) was updated.");

        return response()->json([
            'status' => 'success',
            'message' => 'Client updated successfully',
            'data' => $client
        ]);
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy($id)
    {
        $client = User::find($id);

        if (!$client) {
            return response()->json([
                'status' => 'error',
                'message' => 'Client not found'
            ], 404);
        }

        $clientName = $client->name;
        $client->delete();

        AuditLogger::log('Client Deleted', 'Delete', "Client ({$clientName}) was deleted.");

        return response()->json([
            'status' => 'success',
            'message' => 'Client deleted successfully'
        ]);
    }
}
