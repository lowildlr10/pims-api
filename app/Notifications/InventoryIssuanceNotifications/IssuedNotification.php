<?php

namespace App\Notifications\InventoryIssuanceNotifications;

use App\Models\InventoryIssuance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class IssuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private InventoryIssuance $issuance;

    public function __construct(InventoryIssuance $issuance)
    {
        $this->issuance = $issuance;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'id' => $this->issuance->id,
            'href' => "/inventories/issuances?search={$this->issuance->id}",
            'title' => 'Inventory Issuance Issued',
            'message' => "Inventory issuance <q>#{$this->issuance->inventory_no}</q> has been issued.",
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'inv-issuance-issued';
    }
}
