<?php

// app/Http/Controllers/Api/Customer/NotificationController.php
namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user('customer');

        $notifications = $customer->notifications()->latest()->paginate(20);

        return response()->json([
            'data' => $notifications->getCollection()->map(fn ($n) => [
                'id' => $n->id,
                'data' => $n->data,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
            ]),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function unreadCount(Request $request)
    {
        $customer = $request->user('customer');

        return response()->json([
            'unread_count' => $customer->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $customer = $request->user('customer');

        $notification = $customer->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marquee comme lue.']);
    }

    public function markAllAsRead(Request $request)
    {
        $customer = $request->user('customer');

        $customer->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Toutes les notifications ont ete marquees comme lues.']);
    }
}