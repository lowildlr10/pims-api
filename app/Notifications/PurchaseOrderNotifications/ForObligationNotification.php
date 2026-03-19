<?php

namespace App\Notifications\PurchaseOrderNotifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ForObligationNotification extends Notification implements ShouldQueue
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
            'title' => 'Purchase Order For Obligation',
            'message' => "<q>Purchase Order - {$this->po->po_no}</q> is pending obligation.",
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'po-for-obligation';
    }
}
