<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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
        'supply_id',
        'description',
        'property_no',
        'serial_no',
        'quantity',
        'estimated_useful_life',
        'acquired_date',
        'status',
        'status_timestamps'
    ];
}
