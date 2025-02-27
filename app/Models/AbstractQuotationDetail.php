<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /**
     * The abstract of quotation detail that has one supplier.
     */
    public function supplier(): HasOne
    {
        return $this->hasOne(Supplier::class, 'id', 'supplier_id');
    }

    /**
     * The abstract of quotation detail that belongs to abstract of quoation.
     */
    public function abstract_quotation(): BelongsTo
    {
        return $this->belongsTo(AbstractQuotation::class);
    }

    /**
     * The abstract of quotation detail that belongs to abstract of quoation item.
     */
    public function aoq_item(): BelongsTo
    {
        return $this->belongsTo(AbstractQuotationItem::class, 'aoq_item_id');
    }
}
