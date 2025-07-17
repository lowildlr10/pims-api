<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InspectionAcceptanceReportItem extends Model
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
        'inspection_acceptance_report_id',
        'pr_item_id',
        'po_item_id',
        'accepted',
    ];

    /**
     * The inspection acceptance report item that has one purchase request item.
     */
    public function pr_item(): HasOne
    {
        return $this->hasOne(PurchaseRequestItem::class, 'id', 'pr_item_id');
    }

    /**
     * The inspection acceptance report item that has one purchase order item.
     */
    public function po_item(): HasOne
    {
        return $this->hasOne(PurchaseOrderItem::class, 'id', 'po_item_id');
    }

    /**
     * The inspection acceptance report item that belongs to inspection acceptance report.
     */
    public function inspection_acceptance_report(): BelongsTo
    {
        return $this->belongsTo(InspectionAcceptanceReport::class);
    }
}
