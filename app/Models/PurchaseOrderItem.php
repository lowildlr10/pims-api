<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrderItem extends Model
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
        'purchase_order_id',
        'pr_item_id',
        'description',
        'brand_model',
        'unit_cost',
        'total_cost',
    ];

    /**
     * The purchase order item that has one purchase request item.
     */
    public function pr_item(): HasOne
    {
        return $this->hasOne(PurchaseRequestItem::class, 'id', 'pr_item_id');
    }

    /**
     * The purchase order item that belongs to purchase order.
     */
    public function purchase_order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
