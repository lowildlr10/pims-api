<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Supply extends Model
{
    use HasUuids;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['available'];

    /**
     * Determine if the total available quantity of a supply.
     */
    protected function available(): Attribute
    {
        return new Attribute(
            get: fn () => $this->quantity,
        );
    }

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

    /**
     * The supply that has one unit of issue.
     */
    public function unit_issue(): HasOne
    {
        return $this->hasOne(UnitIssue::class, 'id', 'unit_issue_id');
    }

    /**
     * The supply that has one item classification.
     */
    public function item_classification(): HasOne
    {
        return $this->hasOne(ItemClassification::class, 'id', 'item_classification_id');
    }
}
