<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RequestQuotation extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_request_id',
        'signed_type',
        'rfq_date',
        'rfq_no',
        'supplier_id',
        'openning_dt',
        'sig_approval_id',
        'vat_registered',
        'status',
        'grand_total_cost',
        'canvassing_at',
        'completed_at',
        'cancelled_at'
    ];

    /**
     * The request quotation that has one supplier.
     */
    public function supplier(): HasOne
    {
        return $this->hasOne(Supplier::class, 'id', 'supplier_id');
    }

    /**
     * The request quotation that has many items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(RequestQuotationItem::class);
    }

    /**
     * The request quotation that has many canvassers.
     */
    public function canvassers(): HasMany
    {
        return $this->hasMany(RequestQuotationCanvasser::class);
    }

    /**
     * The request quotation that has one approval signatory.
     */
    public function signatory_approval(): HasOne
    {
        return $this->hasOne(Signatory::class, 'id', 'sig_approval_id');
    }

    /**
     * The request quoation that belongs to purchase request.
     */
    public function purchase_request(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }
}
