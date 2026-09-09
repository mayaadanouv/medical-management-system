<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function getUserNotifications(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->get();
        return response()->json([
            'status' => 'success',
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? null,
                    'message' => $notification->data['message'] ?? null,
                    'type' => $notification->data['type'] ?? null,
                    'url' => $notification->data['url'] ?? null,
                    'is_read' => !is_null($notification->read_at),
                    'created_at' => $notification->created_at->toDateTimeString(),
                ];
            }),
        ], 200);
    }
}
