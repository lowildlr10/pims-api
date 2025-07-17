<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RequestQuotationItem extends Model
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
        'request_quotation_id',
        'pr_item_id',
        'supplier_id',
        'brand_model',
        'unit_cost',
        'total_cost',
        'included',
    ];

    protected $appends = [
        'unit_cost_formatted',
        'total_cost_formatted',
    ];

    protected function unitCostFormatted(): Attribute
    {
        return new Attribute(
            get: fn () => '₱'.number_format($this->unit_cost, 2)
        );
    }

    protected function totalCostFormatted(): Attribute
    {
        return new Attribute(
            get: fn () => '₱'.number_format($this->total_cost, 2)
        );
    }

    /**
     * The request quotation item that has one supplier.
     */
    public function supplier(): HasOne
    {
        return $this->hasOne(Supplier::class, 'id', 'supplier_id');
    }

    /**
     * The request quotation item that has one purchase request item.
     */
    public function pr_item(): HasOne
    {
        return $this->hasOne(PurchaseRequestItem::class, 'id', 'pr_item_id');
    }

    /**
     * The request quoation item that belongs to request quoation.
     */
    public function request_quotation(): BelongsTo
    {
        return $this->belongsTo(RequestQuotation::class);
    }
}
