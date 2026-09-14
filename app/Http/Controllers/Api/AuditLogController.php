<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AuditLog;

class AuditLogController extends Controller
{
    /**
     * Retrieve audit logs with filtering and pagination.
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user:id,name,email')->orderBy('id', 'desc');

        // Filter by Date Range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by User
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by Event Type
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Filter by IP Address
        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%' . $request->ip_address . '%');
        }

        // Global Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $logs
        ]);
    }

    /**
     * Delete multiple audit logs by their IDs.
     */
    public function destroyBulk(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:audit_logs,id'
        ]);

        AuditLog::whereIn('id', $request->ids)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Selected audit logs deleted successfully.'
        ]);
    }

    /**
     * Delete all audit logs.
     */
    public function destroyAll()
    {
        AuditLog::truncate();

        return response()->json([
            'status' => 'success',
            'message' => 'All audit logs deleted successfully.'
        ]);
    }
}
