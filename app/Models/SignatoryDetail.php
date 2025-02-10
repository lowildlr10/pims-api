<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatoryDetail extends Model
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
        'signatory_id',
        'document',
        'signatory_type',
        'position'
    ];

    protected $appends = [
        'fullname_designation'
    ];

    public function fullnameDesignation(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes)
                => !empty($this->signatory) && !empty($this->signatory->user)
                    ? "{$this->signatory->user->fullname} ({$this->position})"
                    : "-",
        );
    }

    public function signatory(): BelongsTo
    {
        return $this->belongsTo(Signatory::class, 'signatory_id', 'id');
    }
}
