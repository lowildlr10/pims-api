<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ObligationRequest extends Model
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
        'transaction_type',
        'funding',
        'payee_type',
        'payee_id',
        'obr_no',
        'office',
        'address',
        'responsibility_center_id',
        'particulars',
        'total_amount',
        'compliance_status',
        'sig_head_id',
        'head_signed_date',
        'sig_budget_id',
        'budget_signed_date',
        'disapproved_reason',
        'status',
        'status_timestamps',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => \App\Enums\ObligationRequestStatus::class,
            'transaction_type' => \App\Enums\TransactionType::class,
            'funding' => 'array',
            'compliance_status' => 'array',
            'status_timestamps' => 'array',
            'head_signed_date' => 'datetime',
            'budget_signed_date' => 'datetime',
        ];
    }

    /**
     * The obligation request that has one payee.
     */
    public function payee(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The obligation request that has one payee.
     */
    public function responsibility_center(): HasOne
    {
        return $this->hasOne(ResponsibilityCenter::class, 'id', 'responsibility_center_id');
    }

    /**
     * The obligation request that has one head signatory.
     */
    public function signatory_budget(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_budget_id');
    }

    /**
     * The obligation request that has one head signatory.
     */
    public function signatory_head(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_head_id');
    }

    /**
     * The obligation request that belongs to purchase order.
     */
    public function purchase_order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * The obligation request that belongs to purchase request.
     */
    public function purchase_request(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * The obligation request that has many fpps.
     */
    public function fpps(): HasMany
    {
        return $this->hasMany(ObligationRequestFpp::class);
    }

    /**
     * The obligation request that has many accounts.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(ObligationRequestAccount::class);
    }
}
