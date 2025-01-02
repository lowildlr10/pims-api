<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Section extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'section_name',
        'department_id',
        'section_head_id',
        'active'
    ];

    protected $appends = [
        'headfullname',
    ];

    public function headfullname(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes)
                => !empty($this->head)
                    ? $this->head->fullname
                    : "-",
        );
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The section that has one head.
     */
    public function head(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'section_head_id');
    }
}
