<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Notifications
 * APIs for managing user notifications
 */
class NotificationController extends Controller
{
    public function __construct(protected NotificationService $service) {}

    /**
     * List Notifications
     *
     * Retrieve a paginated list of user notifications.
     *
     * @queryParam limit int Number of items per page. Default 15.
     *
     * @response 200 {"data": [...], "meta": {...}}
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $limit = $request->get('limit', 15);

        $notifications = $this->service->getAll($user->id, $limit);

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Mark Notification as Read
     *
     * @urlParam id string required The notification UUID.
     *
     * @response 200 {"data": {"id": "uuid", "read_at": "..."}, "message": "Notification marked as read."}
     */
    public function markAsRead(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $notification = $this->service->markAsRead($user->id, $id);

            if (! $notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found or not accessible.',
                ], 404);
            }

            return response()->json([
                'data' => new NotificationResource($notification),
                'message' => 'Notification marked as read.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Failed to mark notification as read.', $th, $id);

            return response()->json([
                'message' => 'An error occurred while marking the notification as read.',
            ], 422);
        }
    }

    /**
     * Mark All Notifications as Read
     *
     * @response 200 {"data": {"count": 0}, "message": "All unread notifications marked as read."}
     */
    public function markAllRead(): JsonResponse
    {
        try {
            $user = Auth::user();
            $unreadNotifications = $this->service->markAllRead($user->id);

            if ($unreadNotifications->isEmpty()) {
                return response()->json([
                    'data' => [],
                    'message' => 'No unread notifications to mark as read.',
                ]);
            }

            return response()->json([
                'data' => ['count' => $unreadNotifications->count()],
                'message' => 'All unread notifications marked as read.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Failed to mark all notifications as read.', $th);

            return response()->json([
                'message' => 'An error occurred while marking all notifications as read.',
            ], 422);
        }
    }

    /**
     * Delete All Notifications
     *
     * @response 200 {"data": {"count": 0}, "message": "All notifications deleted successfully."}
     */
    public function deleteAll(): JsonResponse
    {
        try {
            $user = Auth::user();
            $notifications = $this->service->deleteAll($user->id);

            if ($notifications->isEmpty()) {
                return response()->json([
                    'data' => [],
                    'message' => 'No notifications to delete.',
                ]);
            }

            return response()->json([
                'data' => ['count' => $notifications->count()],
                'message' => 'All notifications deleted successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Failed to delete all notifications.', $th);

            return response()->json([
                'message' => 'An error occurred while deleting notifications.',
            ], 422);
        }
    }
}
