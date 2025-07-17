<?php

namespace App\Notifications\PurchaseRequestNofications;

use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PendingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private PurchaseRequest $pr;

    /**
     * Create a new notification instance.
     */
    public function __construct(PurchaseRequest $pr)
    {
        $this->pr = $pr;
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
            'id' => $this->pr->id,
            'href' => "/procurement/pr?search={$this->pr->id}",
            'title' => 'Purchase Request Pending Approval',
            'message' => "<q>Purchase Request - {$this->pr->pr_no}</q> has been submitted and is awaiting approval.",
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'pr-pending';
    }
}
