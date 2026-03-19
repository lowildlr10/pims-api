<?php

namespace App\Notifications\ObligationRequestNotifications;

use App\Models\ObligationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private ObligationRequest $obr;

    public function __construct(ObligationRequest $obr)
    {
        $this->obr = $obr;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'id' => $this->obr->id,
            'href' => "/procurement/obr?search={$this->obr->id}",
            'title' => 'Obligation Request Created',
            'message' => 'A new obligation request has been created and is pending your action.',
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'obr-created';
    }
}
