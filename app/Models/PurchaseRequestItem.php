<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseRequestItem extends Model
{
    use HasUuids;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_request_id',
        'item_sequence',
        'quantity',
        'unit_issue_id',
        'description',
        'stock_no',
        'estimated_unit_cost',
        'estimated_cost'
    ];

    protected $appends = [
        'estimated_unit_cost_formatted',
        'estimated_cost_formatted'
    ];

    protected function estimatedUnitCostFormatted(): Attribute
    {
        return new Attribute(
            get: fn () => '₱' . number_format($this->estimated_unit_cost, 2)
        );
    }

    protected function estimatedCostFormatted(): Attribute
    {
        return new Attribute(
            get: fn () => '₱' . number_format($this->estimated_cost, 2)
        );
    }

    /**
     * The purchase request item that belongs to purchase request.
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * The purchase request that has one requestor.
     */
    public function unitIssue(): HasOne
    {
        return $this->hasOne(UnitIssue::class, 'id', 'unit_issue_id');
    }
}
