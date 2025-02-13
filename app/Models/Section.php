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
        'division_id',
        'section_head_id',
        'active'
    ];

    // protected $appends = [
    //     'headfullname',
    //     'division_section'
    // ];

    // public function headfullname(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn ($value, $attributes)
    //             => !empty($this->head)
    //                 ? $this->head->fullname
    //                 : "-",
    //     );
    // }

    // protected function divisionSection(): Attribute
    // {
    //     $divisionName = !empty($this->division)
    //         ? (strlen($this->division->division_name) > 35
    //             ? substr($this->division->division_name, 0, 35) . '...'
    //             : $this->division->division_name)
    //         : '-';

    //     return new Attribute(
    //         get: fn () => !empty($this->division)
    //             ? "{$this->section_name} ({$divisionName})"
    //             : '-',
    //     );
    // }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    /**
     * The section that has one head.
     */
    public function head(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'section_head_id');
    }
}
