<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AbstractQuotationItem extends Model
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
        'pr_item_id',
        'included'
    ];

    /**
     * The abstract of quotation item that has one purchase request item.
     */
    public function pr_item(): HasOne
    {
        return $this->hasOne(PurchaseRequestItem::class, 'id', 'pr_item_id');
    }

    /**
     * The abstract of quotation iten that has many details.
     */
    public function details(): HasMany
    {
        return $this->hasMany(AbstractQuotationDetail::class, 'aoq_item_id', 'id');
    }

    /**
     * The abstract of quotation item that belongs to abstract of quoation.
     */
    public function abstract_quotation(): BelongsTo
    {
        return $this->belongsTo(AbstractQuotation::class);
    }
}
