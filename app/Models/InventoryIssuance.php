<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InventoryIssuance extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_order_id',
        'document_type',
        'responsibility_center_id',
        'inventory_no',
        'inventory_date',
        'sai_no',
        'sai_date',
        'requested_by_id',
        'requested_date',
        'sig_approved_by_id',
        'approved_date',
        'sig_issued_by_id',
        'issued_date',
        'received_by_id',
        'received_date',
        'status',
        'status_timestamps',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_timestamps' => 'array',
        ];
    }

    /**
     * The inventory issuance that has one responsibility center.
     */
    public function responsibility_center(): HasOne
    {
        return $this->hasOne(ResponsibilityCenter::class, 'id', 'responsibility_center_id');
    }

    /**
     * The inventory issuance that belongs to purchase order.
     */
    public function purchase_order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * The inventory issuance that has many items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryIssuanceItem::class);
    }

    /**
     * The inventory issuance that has one requestor.
     */
    public function requestor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'requested_by_id');
    }

    /**
     * The inventory issuance that has one approval signatory.
     */
    public function signatory_approval(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_approved_by_id');
    }

    /**
     * The inventory issuance that has one issuer signatory.
     */
    public function signatory_issuer(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_issued_by_id');
    }

    /**
     * The inventory issuance that has one recipient.
     */
    public function recipient(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'received_by_id');
    }
}
