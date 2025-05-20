<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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
        'inventory_no',
        'sai_no',
        'sai_date',
        'document_type',
        'requested_by_id',
        'requested_date',
        'sig_approved_by_id',
        'approved_date',
        'sig_issued_by_id',
        'issued_date',
        'received_by_id',
        'received_date',
        'status',
        'status_timestamps'
    ];
}
