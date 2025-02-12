<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    protected $appends = [
        'fullname'
    ];

    public function fullname(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes)
                => !empty($this->user)
                    ? $this->user->fullname
                    : "-",
        );
    }

    public function signature(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes)
                => !empty($this->user)
                    ? $this->user->signature
                    : "-",
        );
    }

    public function details(): HasMany
    {
        return $this->hasMany(SignatoryDetail::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
