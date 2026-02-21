<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (DatabaseNotification $databaseNotification): array => [
                'id' => $databaseNotification->id,
                'type' => $databaseNotification->type,
                'data' => $databaseNotification->data,
                'read_at' => $databaseNotification->read_at?->toISOString(),
                'created_at' => $databaseNotification->created_at->toISOString(),
            ]);

        $unreadCount = $request->user()->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->find($id);

        if (! $notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
