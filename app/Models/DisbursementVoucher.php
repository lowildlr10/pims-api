<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DisbursementVoucher extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_request_id',
        'purchase_order_id',
        'obligation_request_id',
        'dv_no',
        'mode_payment',
        'payee_id',
        'address',
        'office',
        'responsibility_center_id',
        'explanation',
        'total_amount',
        'accountant_certified_choices',
        'sig_accountant_id',
        'accountant_signed_date',
        'sig_treasurer_id',
        'treasurer_signed_date',
        'sig_head_id',
        'head_signed_date',
        'check_no',
        'bank_name',
        'check_date',
        'received_name',
        'received_date',
        'or_other_document',
        'jev_no',
        'jevt_date',
        'status',
        'status_timestamps'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accountant_certified_choices' => 'array',
            'status_timestamps' => 'array',
        ];
    }

    /**
     * The disbursement voucher that has one payee.
     */
    public function payee(): HasOne
    {
        return $this->hasOne(Supplier::class, 'id', 'supplier_id');
    }

    /**
     * The disbursement voucher that has one responsibility center.
     */
    public function responsibility_center(): HasOne
    {
        return $this->hasOne(ResponsibilityCenter::class, 'id', 'responsibility_center_id');
    }

    /**
     * The disbursement voucher that has one accountant signatory.
     */
    public function signatory_accountant(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_accountant_id');
    }

    /**
     * The disbursement voucher that has one treasurer signatory.
     */
    public function signatory_treasurer(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_treasurer_id');
    }

    /**
     * The disbursement voucher that has one head signatory.
     */
    public function signatory_head(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_head_id');
    }

    /**
     * The disbursement voucher that belongs to purchase order.
     */
    public function purchase_order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * The disbursement voucher that belongs to purchase request.
     */
    public function purchase_request(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }
}
