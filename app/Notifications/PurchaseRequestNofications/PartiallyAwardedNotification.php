<?php

namespace App\Notifications\PurchaseRequestNofications;

use App\Models\AbstractQuotation;
use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PartiallyAwardedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private PurchaseRequest $pr;

    private AbstractQuotation $aoq;

    /**
     * Create a new notification instance.
     */
    public function __construct(PurchaseRequest $pr, AbstractQuotation $aoq)
    {
        $this->pr = $pr;
        $this->aoq = $aoq;
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
            'title' => 'Partially Awarded',
            'message' => "<q>Purchase Request - {$this->pr->pr_no}</q> with ".
                "<q>Abstract - {$this->aoq->abstract_no}</q> has been partially ".
                "awarded. Remaining items are pending further action.",
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'pr-partially-awarded';
    }
}
