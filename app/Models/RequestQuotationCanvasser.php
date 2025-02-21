<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RequestQuotationCanvasser extends Model
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
        'request_quotation_id',
        'user_id'
    ];

    /**
     * The request quoation canvasser that belongs to request quoation.
     */
    public function request_quotation(): BelongsTo
    {
        return $this->belongsTo(RequestQuotation::class);
    }

    /**
     * The request quoation canvasser that has one user.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
