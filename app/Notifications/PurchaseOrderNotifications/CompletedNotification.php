<?php

namespace App\Notifications\PurchaseOrderNotifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private PurchaseOrder $po;

    public function __construct(PurchaseOrder $po)
    {
        $this->po = $po;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'id' => $this->po->id,
            'href' => "/procurement/po?search={$this->po->id}",
            'title' => 'Purchase Order Completed',
            'message' => "<q>Purchase Order - {$this->po->po_no}</q> has been fully completed.",
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'po-completed';
    }
}
