<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportTicket;

class SupportTicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = SupportTicket::with(['tenant', 'assignee'])->latest();

        if (!$user->hasRole('SuperAdmin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('SuperAdmin');

        $validated = $request->validate([
            'tenant_id' => $isSuperAdmin ? 'required|exists:tenants,id' : 'nullable',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|string|in:Urgent,High,Normal',
            'status' => $isSuperAdmin ? 'required|string|in:In Progress,Open,Resolved' : 'nullable',
            'assignee_id' => 'nullable|exists:users,id',
            'sla_deadline' => 'nullable|date',
        ]);

        if (!$isSuperAdmin) {
            if (!$user->tenant_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your account is not assigned to a tenant. Only SuperAdmins can specify a tenant_id manually.'
                ], 403);
            }
            $validated['tenant_id'] = $user->tenant_id;
            $validated['status'] = 'Open';
        }

        if (!isset($validated['priority'])) {
            $validated['priority'] = 'Normal';
        }

        $validated['ticket_id'] = 'TCK-' . rand(1000, 9999);

        $ticket = SupportTicket::create($validated);

        if (!$isSuperAdmin) {
            \App\Models\AdminNotification::create([
                'type' => 'support_ticket',
                'title' => 'New Support Ticket',
                'message' => 'A new support ticket has been created: ' . $ticket->subject,
                'related_id' => $ticket->id,
                'client_name' => $user->name ?? 'Client',
                'is_read' => false,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket created successfully',
            'data' => $ticket->load(['tenant', 'assignee'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $query = SupportTicket::with(['tenant', 'assignee']);

        if (!$user->hasRole('SuperAdmin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $ticket = $query->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $ticket
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();
        $query = SupportTicket::query();

        if (!$user->hasRole('SuperAdmin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $ticket = $query->findOrFail($id);

        $validated = $request->validate([
            'subject' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'sometimes|string|in:Urgent,High,Normal',
            'status' => 'sometimes|string|in:In Progress,Open,Resolved',
            'assignee_id' => 'nullable|exists:users,id',
            'sla_deadline' => 'nullable|date',
        ]);

        $ticket->update($validated);

        if (!$user->hasRole('SuperAdmin')) {
            \App\Models\AdminNotification::create([
                'type' => 'support_ticket',
                'title' => 'Support Ticket Updated',
                'message' => 'Support ticket ' . ($ticket->ticket_id ?? 'TCK') . ' was updated by client.',
                'related_id' => $ticket->id,
                'client_name' => $user->name ?? 'Client',
                'is_read' => false,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket updated successfully',
            'data' => $ticket->load(['tenant', 'assignee'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $query = SupportTicket::query();

        if (!$user->hasRole('SuperAdmin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $ticket = $query->findOrFail($id);
        $ticket->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket deleted successfully'
        ]);
    }
}
