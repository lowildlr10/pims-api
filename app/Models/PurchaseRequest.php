<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseRequest extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'section_id',
        'pr_no',
        'pr_date',
        'sai_no',
        'sai_date',
        'alobs_no',
        'alobs_date',
        'purpose',
        'funding_source_id',
        'requested_by_id',
        'sig_cash_availability_id',
        'sig_approved_by_id',
        'status',
        'submitted_at',
        'approved_cash_available_at',
        'approved_at',
        'disapproved_at',
        'cancelled_at'
    ];

    /**
     * The purchase request that has one section.
     */
    public function section(): HasOne
    {
        return $this->hasOne(Section::class, 'id', 'funding_source_id');
    }

    /**
     * The purchase request that has one funding source.
     */
    public function fundingSource(): HasOne
    {
        return $this->hasOne(FundingSource::class, 'id', 'funding_source_id');
    }

    /**
     * The purchase request that has one requestor.
     */
    public function requestor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'requested_by_id');
    }

    /**
     * The purchase request that has one cash availability signatory.
     */
    public function signatoryCashAvailability(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_cash_availability_id');
    }

    /**
     * The purchase request that has one approval signatory.
     */
    public function signatoryApprovedBy(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_approved_by_id');
    }

    /**
     * The purchase request that has many items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }
}
