<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ObligationRequestFpp extends Model
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
        'obligation_request_id',
        'fpp_id',
    ];

    /**
     * The obligation request FPP that has one FPP.
     */
    public function fpp(): HasOne
    {
        return $this->hasOne(FunctionProgramProject::class, 'id', 'fpp_id');
    }
}
