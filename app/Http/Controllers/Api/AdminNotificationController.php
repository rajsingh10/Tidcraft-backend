<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AdminNotification;

class AdminNotificationController extends Controller
{
    public function index()
    {
        $notifications = AdminNotification::latest()->get();
        $unreadCount = AdminNotification::where('is_read', false)->count();

        return response()->json([
            'status' => 'success', 
            'data' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    public function unread()
    {
        $notifications = AdminNotification::where('is_read', false)->latest()->get();
        return response()->json(['status' => 'success', 'data' => $notifications]);
    }

    public function markAsRead($id)
    {
        $notification = AdminNotification::find($id);
        if (!$notification) {
            return response()->json(['status' => 'error', 'message' => 'Notification not found.'], 404);
        }

        $notification->update(['is_read' => true]);
        return response()->json(['status' => 'success', 'message' => 'Notification marked as read.']);
    }

    public function markAsUnread($id)
    {
        $notification = AdminNotification::find($id);
        if (!$notification) {
            return response()->json(['status' => 'error', 'message' => 'Notification not found.'], 404);
        }

        $notification->update(['is_read' => false]);
        return response()->json(['status' => 'success', 'message' => 'Notification marked as unread.']);
    }

    public function markAllAsRead()
    {
        AdminNotification::where('is_read', false)->update(['is_read' => true]);
        return response()->json(['status' => 'success', 'message' => 'All notifications marked as read.']);
    }
}
