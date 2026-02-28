<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\LogRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function __construct(protected LogRepository $logRepository) {}

    public function getAll(string $userId, int $limit): LengthAwarePaginator
    {
        $user = User::find($userId);

        return $user->notifications()
            ->latest()
            ->paginate($limit);
    }

    public function markAsRead(string $userId, string $notificationId): ?DatabaseNotification
    {
        $user = User::find($userId);
        $notification = $user->notifications()->find($notificationId);

        if (! $notification) {
            return null;
        }

        $notification->markAsRead();

        return $notification;
    }

    public function markAllRead(string $userId): Collection
    {
        $user = User::find($userId);
        $unreadNotifications = $user->unreadNotifications;

        if ($unreadNotifications->isNotEmpty()) {
            $unreadNotifications->each->markAsRead();
        }

        return $unreadNotifications;
    }

    public function deleteAll(string $userId): Collection
    {
        $user = User::find($userId);
        $notifications = $user->notifications;

        if ($notifications->isNotEmpty()) {
            $notifications->each->delete();
        }

        return $notifications;
    }

    public function logError(string $message, \Throwable $th, ?string $notificationId = null): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_id' => $notificationId,
            'log_module' => 'notification',
        ], isError: true);
    }
}
