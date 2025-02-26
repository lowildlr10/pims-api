<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AbstractQuotationDetail extends Model
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
        'abstract_quotation_id',
        'aoq_item_id',
        'supplier_id',
        'brand_model',
        'unit_cost',
        'total_cost'
    ];
}
