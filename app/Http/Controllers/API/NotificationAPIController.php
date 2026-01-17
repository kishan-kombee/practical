<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataTrueResource;
use App\Http\Resources\NotificationCollection;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

/*
   |--------------------------------------------------------------------------
   | Notification Controller
   |--------------------------------------------------------------------------
   |
   | This controller handles the notifications of
     index (list),
     count (read/unread/total counts),
     read (mark as read/unread) Methods.
   |
   */

class NotificationAPIController extends Controller
{
    use ApiResponseTrait;
    public function index(Request $request)
    {
        $user = $request->user();

        // Mark all notifications as read for this user
        Notification::where('user_id', $user->id)
            ->update(['is_read' => config('constants.notification.is_read.read')]);

        $query = Notification::where('user_id', $user->id);

        // Filter by read/unread status
        if ($request->has('read')) {
            if ($request->get('read') == 'true') {
                $query->where('is_read', config('constants.notification.is_read.read'));
            } else {
                // Unread: is_read = 'U' OR is_read IS NULL
                $query->where(function ($q) {
                    $q->where('is_read', config('constants.notification.is_read.unread'))
                        ->orWhereNull('is_read');
                });
            }
        }

        // Order by latest first
        $query->latest();

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $notifications = $query->paginate($perPage);

        return new NotificationCollection(NotificationResource::collection($notifications), NotificationResource::class);
    }

    public function count(Request $request)
    {
        $user = $request->user();
        $baseQuery = Notification::where('user_id', $user->id);
        $totalCount = (clone $baseQuery)->count();
        // Unread count: is_read = 'U' OR is_read IS NULL
        $unreadCount = (clone $baseQuery)->where(function ($q) {
            $q->where('is_read', config('constants.notification.is_read.unread'))
                ->orWhereNull('is_read');
        })->count();

        // Read count: is_read = 'R'
        // $readCount = (clone $baseQuery)->where('is_read', config('constants.notification.is_read.read'))->count();

        return $this->successResponse(
            [
                'unread_count' => $unreadCount,
                'total_count' => $totalCount,
            ],
            __('messages.notification.counts_retrieved_successfully')
        );
    }

    public function read(Request $request, $id)
    {
        $user = $request->user();
        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $notification) {
            return $this->notFoundResponse(__('messages.notification.not_found'));
        }

        // Get read parameter from request, default to true (mark as read)
        $markAsRead = $request->get('read', true);

        if ($markAsRead === true || $markAsRead === 'true' || $markAsRead === '1') {
            // Mark as read
            $notification->is_read = config('constants.notification.is_read.read');
            $notification->save();
            $message = __('messages.notification.marked_as_read');
        } else {
            // Mark as unread
            $notification->is_read = config('constants.notification.is_read.unread');
            $notification->save();
            $message = __('messages.notification.marked_as_unread');
        }

        return new DataTrueResource($notification, $message);
    }
}
