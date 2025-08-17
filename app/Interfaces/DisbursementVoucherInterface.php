<?php

namespace App\Interfaces;

use App\Models\DisbursementVoucher;

interface DisbursementVoucherInterface
{
    public function storeUpdate(array $data, ?DisbursementVoucher $disbursementVoucher);

    public function print(array $pageConfig, string $dvId);
}
