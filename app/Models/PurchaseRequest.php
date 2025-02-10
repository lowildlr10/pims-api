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
        'total_estimated_cost',
        'submitted_at',
        'approved_cash_available_at',
        'approved_at',
        'disapproved_at',
        'cancelled_at'
    ];

    protected $appends = [
        'total_estimated_cost_formatted',
        'section_name',
        'funding_source_title',
        'requestor_fullname',
        'cash_availability_fullname',
        'approver_fullname'
    ];

    protected function totalEstimatedCostFormatted(): Attribute
    {
        return new Attribute(
            get: fn () => '₱' . number_format($this->total_estimated_cost, 2)
        );
    }

    protected function sectionName(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes)
                => !empty($this->section)
                    ? $this->section->section_name
                    : "-",
        );
    }

    protected function fundingSourceTitle(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes)
                => !empty($this->fundingSource)
                    ? $this->fundingSource->title
                    : "-",
        );
    }

    protected function requestorFullname(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes)
                => !empty($this->requestor)
                    ? $this->requestor->fullname
                    : "-",
        );
    }

    protected function cashAvailabilityFullname(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes)
                => !empty($this->signatoryCashAvailability)
                    ? $this->signatoryCashAvailability->fullname
                    : "-",
        );
    }

    protected function approverFullname(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes)
                => !empty($this->signatoryApprovedBy)
                    ? $this->signatoryApprovedBy->fullname
                    : "-",
        );
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
