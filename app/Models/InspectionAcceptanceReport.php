<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InspectionAcceptanceReport extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_request_id',
        'purchase_order_id',
        'supplier_id',
        'iar_no',
        'iar_date',
        'invoice_no',
        'invoice_date',
        'inspected_date',
        'inspected',
        'sig_inspection_id',
        'received_date',
        'acceptance_completed',
        'acceptance_id',
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
     * The inspection acceptance report that has one supplier.
     */
    public function supplier(): HasOne
    {
        return $this->hasOne(Supplier::class, 'id', 'supplier_id');
    }

    /**
     * The inspection acceptance report that has many items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InspectionAcceptanceReportItem::class);
    }

    /**
     * The inspection acceptance report that has one inspection signatory.
     */
    public function signatory_inspection(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_inspection_id');
    }

    /**
     * The inspection acceptance report that has one acceptance signatory.
     */
    public function acceptance(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'acceptance_id');
    }

    /**
     * The inspection acceptance report that belongs to purchase order.
     */
    public function purchase_order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * The inspection acceptance report that belongs to purchase request.
     */
    public function purchase_request(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }
}
