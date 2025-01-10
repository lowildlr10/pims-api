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
        'department_section'
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

    protected function departmentSection(): Attribute
    {
        $departmentName = !empty($this->department)
            ? (strlen($this->department->department_name) > 35
                ? substr($this->department->department_name, 0, 35) . '...'
                : $this->department->department_name)
            : '-';

        return new Attribute(
            get: fn () => !empty($this->department)
                ? "{$this->section_name} ({$departmentName})"
                : '-',
        );
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * The section that has one head.
     */
    public function head(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'section_head_id');
    }
}
