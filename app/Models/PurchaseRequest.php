<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'department_id',
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
        'rfq_batch',
        'status',
        'status_timestamps',
        'total_estimated_cost',
    ];

    protected $appends = [
        'total_estimated_cost_formatted',
    ];

    protected function totalEstimatedCostFormatted(): Attribute
    {
        return new Attribute(
            get: fn () => '₱'.number_format($this->total_estimated_cost, 2)
        );
    }

    /**
     * The purchase request that has one department.
     */
    public function department(): HasOne
    {
        return $this->hasOne(Department::class, 'id', 'department_id');
    }

    /**
     * The purchase request that has one section.
     */
    public function section(): HasOne
    {
        return $this->hasOne(Section::class, 'id', 'section_id');
    }

    /**
     * The purchase request that has one funding source.
     */
    public function funding_source(): HasOne
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
    public function signatory_cash_available(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_cash_availability_id');
    }

    /**
     * The purchase request that has one approval signatory.
     */
    public function signatory_approval(): HasOne
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

    /**
     * The purchase request that has many RFQs.
     */
    public function rfqs(): HasMany
    {
        return $this->hasMany(RequestQuotation::class);
    }

    /**
     * The purchase request that has many AOQs.
     */
    public function aoqs(): HasMany
    {
        return $this->hasMany(AbstractQuotation::class);
    }

    /**
     * The purchase request that has many POs.
     */
    public function pos(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
