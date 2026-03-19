<?php

namespace App\Notifications\DisbursementVoucherNotifications;

use App\Models\DisbursementVoucher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ForPaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private DisbursementVoucher $dv;

    public function __construct(DisbursementVoucher $dv)
    {
        $this->dv = $dv;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'id' => $this->dv->id,
            'href' => "/procurement/dv?search={$this->dv->id}",
            'title' => 'Disbursement Voucher For Payment',
            'message' => 'A disbursement voucher is ready for payment processing.',
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'dv-for-payment';
    }
}
