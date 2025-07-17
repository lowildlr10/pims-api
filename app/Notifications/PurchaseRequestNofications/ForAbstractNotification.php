<?php

namespace App\Notifications\PurchaseRequestNofications;

use App\Models\PurchaseRequest;
use App\Models\RequestQuotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ForAbstractNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private PurchaseRequest $pr;

    private RequestQuotation $rfq;

    /**
     * Create a new notification instance.
     */
    public function __construct(PurchaseRequest $pr, RequestQuotation $rfq)
    {
        $this->pr = $pr;
        $this->rfq = $rfq;
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
            'title' => 'Ready for Abstract Preparation',
            'message' => "<q>Purchase Request - {$this->pr->pr_no}</q> with ".
                "<q>RFQ - {$this->rfq->rfq_no}</q> is ready for abstract preparation ".
                "based on request for quotations.",
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'pr-for-abstract';
    }
}
