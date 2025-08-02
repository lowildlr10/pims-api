<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'theme_colors' => 'array',
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_name',
        'address',
        'region',
        'province',
        'municipality',
        'company_type',
        'company_head_id',
        'favicon',
        'company_logo',
        'bagong_pilipinas_logo',
        'login_background',
        'theme_colors',
    ];

    /**
     * The department that has one head.
     */
    public function head(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'company_head_id');
    }
}
