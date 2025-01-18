<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FundingSource extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'location_id',
        'total_cost',
        'active'
    ];

    protected $appends = [
        'total_cost_formatted'
    ];

    protected function totalCostFormatted(): Attribute
    {
        return new Attribute(
            get: fn () => '₱' . number_format($this->total_cost, 2)
        );
    }

    public function location(): HasOne
    {
        return $this->hasOne(Location::class, 'id', 'location_id');
    }
}
