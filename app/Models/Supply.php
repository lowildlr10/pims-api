<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Supply extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_order_id',
        'po_item_id',
        'item_sequence',
        'sku',
        'upc',
        'name',
        'description',
        'item_classification_id',
        'unit_issue_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'required_document'
    ];
}
