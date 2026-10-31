<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = $request->user()->notifications()
            ->orderBy('created_at', 'desc');

        // Filter by read status if provided
        if ($request->has('read')) {
            if ($request->boolean('read')) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Get unread count.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount(Request $request)
    {
        $count = $request->user()->notifications()
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark notification as read.
     *
     * @param  Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->find($id);

        if (! $notification) {
            return response()->json([
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json($notification);
    }

    /**
     * Mark all notifications as read.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->find($id);

        if (! $notification) {
            return response()->json([
                'message' => 'Notification not found',
            ], 404);
        }

        DB::transaction(function () use ($notification) {
            $notification->delete();
        });

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }
}