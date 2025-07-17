<?php

namespace App\Notifications\PurchaseRequestNofications;

use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private PurchaseRequest $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(PurchaseRequest $data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'id' => $this->data->id,
            'href' => "/procurement/pr?search={$this->data->id}",
            'title' => 'Purchase Request Cancelled',
            'message' => "<q>Purchase Request - {$this->data->pr_no}</q> has been cancelled and will no longer proceed.",
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'pr-cancelled';
    }
}
