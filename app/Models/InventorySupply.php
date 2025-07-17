<?php

namespace App\Models;

use App\Enums\InventoryIssuanceStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InventorySupply extends Model
{
    use HasUuids;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['available', 'reserved', 'issued', 'status'];

    /**
     * Determine if the total issued quantity of a supply.
     */
    protected function issued(): Attribute
    {
        return new Attribute(
            get: fn () => $this->issuedQuantity()
        );
    }

    /**
     * Determine if the total reserved quantity of a supply.
     */
    protected function reserved(): Attribute
    {
        return new Attribute(
            get: fn () => $this->reservedQuantity()
        );
    }

    /**
     * Determine if the total available quantity of a supply.
     */
    protected function available(): Attribute
    {
        return new Attribute(
            get: fn () => $this->availableQuantity()
        );
    }

    /**
     * Determine if the status of a supply.
     */
    protected function status(): Attribute
    {
        return new Attribute(
            get: fn () => $this->availableQuantity() > 0 ? 'in-stock' : 'out-of-stock'
        );
    }

    protected function issuedQuantity(): int
    {
        return $this->issuedItemsQuantity(status: InventoryIssuanceStatus::ISSUED);
    }

    protected function reservedQuantity(): int
    {
        return $this->issuedItemsQuantity(exclude: [InventoryIssuanceStatus::CANCELLED])
            - $this->issuedItemsQuantity(status: InventoryIssuanceStatus::ISSUED);
    }

    protected function availableQuantity(): int
    {
        return $this->quantity
            - $this->issuedItemsQuantity(exclude: [InventoryIssuanceStatus::CANCELLED]);
    }

    protected function issuedItemsQuantity(?InventoryIssuanceStatus $status = null, array $exclude = []): int
    {
        return $this->issued_items()
            ->when(
                $status,
                fn ($query) => $query->whereHas(
                    'issuance', fn ($q) => $q->where('status', $status)
                )
            )
            ->when(
                $exclude,
                fn ($query) => $query->whereHas(
                    'issuance', fn ($q) => $q->whereNotIn('status', $exclude)
                )
            )
            ->sum('quantity');
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
        'required_document',
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

    /**
     * The supply that has many issued items.
     */
    public function issued_items(): HasMany
    {
        return $this->hasMany(InventoryIssuanceItem::class);
    }

    /**
     * The supply that has many issued items.
     */
    public function issued_item(): HasMany
    {
        return $this->hasMany(InventoryIssuanceItem::class);
    }
}
