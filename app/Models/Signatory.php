<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Signatory extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'active'
    ];

    public function signatoryDetails(): HasMany
    {
        return $this->hasMany(SignatoryDetail::class, 'signatory_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'user_id');
    }
}
