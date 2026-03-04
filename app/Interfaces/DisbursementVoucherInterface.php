<?php

namespace App\Interfaces;

use App\Models\DisbursementVoucher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DisbursementVoucherInterface
{
    public function getAll(array $filters, ?string $userId = null): LengthAwarePaginator;

    public function getById(string $id): ?DisbursementVoucher;

    public function storeUpdate(array $data, ?DisbursementVoucher $disbursementVoucher = null): DisbursementVoucher;

    public function print(array $pageConfig, string $dvId);
}
