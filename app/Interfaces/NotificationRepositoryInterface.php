<?php

namespace App\Interfaces;

use App\Enums\NotificationType;

interface NotificationRepositoryInterface
{
    public function notify(NotificationType $notificationType, array $data): void ;
}
