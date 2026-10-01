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
            $tenantIds = \App\Models\Tenant::where('create_by', $user->id)->pluck('id');
            $query->where(function($q) use ($tenantIds, $user) {
                $q->whereIn('tenant_id', $tenantIds)
                  ->orWhere('create_by', $user->id);
            });
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
            $tenantId = $request->input('tenant_id');
            
            // Auto-assign the user's first tenant if none is provided
            if (!$tenantId) {
                $firstTenant = \App\Models\Tenant::where('create_by', $user->id)->first();
                if ($firstTenant) {
                    $tenantId = $firstTenant->id;
                }
            } else {
                $ownsTenant = \App\Models\Tenant::where('id', $tenantId)->where('create_by', $user->id)->exists();
                if (!$ownsTenant) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to create a ticket for this tenant.'
                    ], 403);
                }
            }

            $validated['tenant_id'] = $tenantId;
            $validated['status'] = 'Open';
            $validated['create_by'] = $user->id;
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

            try {
                $adminEmail = \App\Models\Setting::where('key', 'company_email')->value('value');
                if (!$adminEmail) {
                    $superAdmin = \App\Models\User::role('SuperAdmin')->first();
                    $adminEmail = $superAdmin ? $superAdmin->email : null;
                }
                if (!$adminEmail) {
                    $adminEmail = config('mail.from.address') ?? 'admin@example.com';
                }
                
                if ($adminEmail) {
                    \Illuminate\Support\Facades\Mail::to($adminEmail)->send(new \App\Mail\AdminSupportTicketMail($ticket, $user));
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send admin support ticket email: ' . $e->getMessage());
            }
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
            $tenantIds = \App\Models\Tenant::where('create_by', $user->id)->pluck('id');
            $query->where(function($q) use ($tenantIds, $user) {
                $q->whereIn('tenant_id', $tenantIds)
                  ->orWhere('create_by', $user->id);
            });
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
            $tenantIds = \App\Models\Tenant::where('create_by', $user->id)->pluck('id');
            $query->where(function($q) use ($tenantIds, $user) {
                $q->whereIn('tenant_id', $tenantIds)
                  ->orWhere('create_by', $user->id);
            });
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

        $originalStatus = $ticket->status;
        $ticket->update($validated);

        if (isset($validated['status']) && $validated['status'] === 'Resolved' && $originalStatus !== 'Resolved') {
            try {
                $ticket->load('tenant.client');
                $client = $ticket->tenant->client ?? null;
                
                if (!$client && $ticket->tenant && $ticket->tenant->create_by) {
                    $client = \App\Models\User::find($ticket->tenant->create_by);
                }
                
                if (!$client && $ticket->create_by) {
                    $client = \App\Models\User::find($ticket->create_by);
                }

                if ($client && $client->email) {
                    \Illuminate\Support\Facades\Mail::to($client->email)->send(new \App\Mail\TicketResolvedMail($ticket, $client));
                } else {
                    \Illuminate\Support\Facades\Log::warning('Could not find client to send ticket resolved email for ticket ID: ' . $ticket->id);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send ticket resolved email: ' . $e->getMessage());
            }
        }

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
            $tenantIds = \App\Models\Tenant::where('create_by', $user->id)->pluck('id');
            $query->where(function($q) use ($tenantIds, $user) {
                $q->whereIn('tenant_id', $tenantIds)
                  ->orWhere('create_by', $user->id);
            });
        }

        $ticket = $query->findOrFail($id);
        $ticket->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket deleted successfully'
        ]);
    }

    /**
     * Update the status of the specified resource.
     */
    public function changeStatus(Request $request, string $id)
    {
        $user = $request->user();
        $query = SupportTicket::query();

        if (!$user->hasRole('SuperAdmin')) {
            $tenantIds = \App\Models\Tenant::where('create_by', $user->id)->pluck('id');
            $query->where(function($q) use ($tenantIds, $user) {
                $q->whereIn('tenant_id', $tenantIds)
                  ->orWhere('create_by', $user->id);
            });
        }

        $ticket = $query->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:In Progress,Open,Resolved',
        ]);

        $originalStatus = $ticket->status;
        $ticket->update(['status' => $validated['status']]);

        if ($validated['status'] === 'Resolved' && $originalStatus !== 'Resolved') {
            try {
                $ticket->load('tenant.client');
                $client = $ticket->tenant->client ?? null;
                
                if (!$client && $ticket->tenant && $ticket->tenant->create_by) {
                    $client = \App\Models\User::find($ticket->tenant->create_by);
                }
                
                if (!$client && $ticket->create_by) {
                    $client = \App\Models\User::find($ticket->create_by);
                }

                if ($client && $client->email) {
                    \Illuminate\Support\Facades\Mail::to($client->email)->send(new \App\Mail\TicketResolvedMail($ticket, $client));
                } else {
                    \Illuminate\Support\Facades\Log::warning('Could not find client to send ticket resolved email for ticket ID: ' . $ticket->id);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send ticket resolved email: ' . $e->getMessage());
            }
        }

        if (!$user->hasRole('SuperAdmin')) {
            \App\Models\AdminNotification::create([
                'type' => 'support_ticket',
                'title' => 'Support Ticket Status Updated',
                'message' => 'Support ticket ' . ($ticket->ticket_id ?? 'TCK') . ' status was updated to ' . $validated['status'] . '.',
                'related_id' => $ticket->id,
                'client_name' => $user->name ?? 'Client',
                'is_read' => false,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket status updated successfully',
            'data' => $ticket->load(['tenant', 'assignee'])
        ]);
    }
}
