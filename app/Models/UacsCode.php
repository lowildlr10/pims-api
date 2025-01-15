<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UacsCode extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'classification_id',
        'account_title',
        'code',
        'description',
        'active'
    ];

    public function uacsClassification(): HasOne
    {
        return $this->hasOne(UacsCodeClassification::class, 'classification_id');
    }
}
