<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryIssuanceItem extends Model
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
        'inventory_issuance_id',
        'inventory_supply_id',
        'stock_no',
        'description',
        'inventory_item_no',
        'property_no',
        'quantity',
        'estimated_useful_life',
        'acquired_date',
        'unit_cost',
        'total_cost'
    ];

    /**
     * The inventory issuance item that belongs to inventory issuance.
     */
    public function issuance(): BelongsTo
    {
        return $this->belongsTo(InventoryIssuance::class, 'inventory_issuance_id', 'id');
    }

    /**
     * The inventory issuance item that belongs to inventory supply.
     */
    public function supply(): BelongsTo
    {
        return $this->belongsTo(InventorySupply::class, 'inventory_supply_id', 'id');
    }
}
