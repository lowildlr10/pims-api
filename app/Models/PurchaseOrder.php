<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrder extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_request_id',
        'po_no',
        'po_date',
        'mode_procurement_id',
        'supplier_id',
        'place_delivery_id',
        'delivery_date',
        'delivery_term_id',
        'payment_term_id',
        'total_amount_words',
        'total_amount',
        'sig_approval_id',
        'document_type',
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
            'total_amount' => 'float'
        ];
    }

    /**
     * The purchase order that has one supplier.
     */
    public function supplier(): HasOne
    {
        return $this->hasOne(Supplier::class, 'id', 'supplier_id');
    }

    /**
     * The purchase order that has one mode procurement.
     */
    public function mode_procurement(): HasOne
    {
        return $this->hasOne(ProcurementMode::class, 'id', 'mode_procurement_id');
    }

    /**
     * The purchase order that has many items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * The purchase order that has one place of delivery.
     */
    public function place_delivery(): HasOne
    {
        return $this->hasOne(Location::class, 'id', 'place_delivery_id');
    }

    /**
     * The purchase order that has one delivery term.
     */
    public function delivery_term(): HasOne
    {
        return $this->hasOne(DeliveryTerm::class, 'id', 'delivery_term_id');
    }

    /**
     * The purchase order that has one payment term.
     */
    public function payment_term(): HasOne
    {
        return $this->hasOne(PaymentTerm::class, 'id', 'payment_term_id');
    }

    /**
     * The purchase order that has one approval signatory.
     */
    public function signatory_approval(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_approval_id');
    }

    /**
     * The purhcase request that belongs to purchase request.
     */
    public function purchase_request(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * The purchase order that has many inspection acceptance report.
     */
    public function inspection_acceptance_report(): HasMany
    {
        return $this->hasMany(InspectionAcceptanceReport::class);
    }

    /**
     * The purchase order that has many inventory supplies.
     */
    public function supplies(): HasMany
    {
        return $this->hasMany(InventorySupply::class);
    }

    /**
     * The purchase order that has many inventory issuances.
     */
    public function issuances(): HasMany
    {
        return $this->hasMany(InventoryIssuance::class);
    }

    /**
     * The purchase order that has one obligation request.
     */
    public function obligation_request(): HasOne
    {
        return $this->hasOne(ObligationRequest::class, 'purchase_order_id');
    }

    /**
     * The purchase order that has one disbursement voucher.
     */
    public function disbursement_voucher(): HasOne
    {
        return $this->hasOne(ObligationRequest::class, 'purchase_order_id');
    }
}
