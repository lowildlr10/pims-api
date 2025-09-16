<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    private LogRepository $logRepository;

    public function __construct(LogRepository $logRepository) {
        $this->logRepository = $logRepository;
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $limit = $request->get('limit', default: 15);

        $notifications = $user->notifications()
            ->latest()
            ->paginate($limit);

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function markAsRead(string $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $notification = $user->notifications()->find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found or not accessible.',
                ], 404);
            }

            $notification->markAsRead();

            return response()->json([
                'data' => [
                    'data' => [
                        'id' => $notification->id,
                        'read_at' => $notification->read_at,
                    ],
                    'message' => 'Notification marked as read.',
                ]
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => 'Failed to mark notification as read.',
                'details' => $th->getMessage(),
                'log_id' => $id,
                'log_module' => 'notification'
            ], isError: true);

            return response()->json([
                'message' => 'An error occurred while marking the notification as read.',
            ], 422);
        }
    }

    public function markAllRead(): JsonResponse
    {
        try {
            $user = Auth::user();

            $unreadNotifications = $user->unreadNotifications;

            if ($unreadNotifications->isEmpty()) {
                return response()->json([
                    'data' => [
                        'data' => [],
                        'message' => 'No unread notifications to mark as read.',
                    ]
                ]);
            }

            $unreadNotifications->each->markAsRead();

            return response()->json([
                'data' => [
                    'data' => [
                        'count' => $unreadNotifications->count(),
                    ],
                    'message' => 'All unread notifications marked as read.',
                ]
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => 'Failed to mark all notifications as read.',
                'details' => $th->getMessage(),
                'log_module' => 'notification'
            ], isError: true);

            return response()->json([
                'message' => 'An error occurred while marking all notifications as read.',
            ], 422);
        }
    }

    public function deleteAll(): JsonResponse
    {
        try {
            $user = Auth::user();

            $notifications = $user->notifications;

            if ($notifications->isEmpty()) {
                return response()->json([
                    'data' => [
                        'data' => [],
                        'message' => 'No notifications to delete.',
                    ]
                ]);
            }

            $deletedCount = $notifications->count();
            $notifications->each->delete();

            return response()->json([
                'data' => [
                    'data' => [
                        'count' => $deletedCount,
                    ],
                    'message' => 'All notifications deleted successfully.',
                ]
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => 'Failed to delete all notifications.',
                'details' => $th->getMessage(),
                'log_module' => 'notification'
            ], isError: true);

            return response()->json([
                'message' => 'An error occurred while deleting notifications.',
            ], 422);
        }
    }

}
