<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens,HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'middlename',
        'lastname',
        'sex',
        'department_id',
        'section_id',
        'position_id',
        'designation_id',
        'username',
        'email',
        'phone',
        'password',
        'avatar',
        'signature',
        'restricted',
        'allow_signature',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'restricted' => 'boolean',
        ];
    }

    protected $appends = [
        'fullname',
    ];

    public function fullname(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes)
                => !empty($attributes['middlename'])
                    ? "{$attributes['firstname']} {$attributes['lastname'][0]}. {$attributes['lastname']}"
                    : "{$attributes['firstname']} {$attributes['lastname']}",
        );
    }

    public function permissions(): array
	{
		return $this->roles
            ->pluck('permissions')
            ->flatten()
            ->map(function ($permission) {
                [$module, $scopes] = explode(':', $permission);
                return collect(explode(',', $scopes))
                    ->map(fn($scope) => "{$module}:{$scope}");
            })
            ->flatten()
            ->unique()
            ->values()
            ->toArray();
	}

    /**
     * The roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_users', 'user_id', 'role_id')
            ->using(RoleUser::class)
            ->withPivot('id');
    }

    /**
     * The position that belong to the user.
     */
    public function position()
    {
        return $this->hasOne(Position::class, 'id', 'position_id');
    }

    /**
     * The designation that belong to the user.
     */
    public function designation()
    {
        return $this->hasOne(Designation::class, 'id', 'designation_id');
    }

    /**
     * The department that belong to the user.
     */
    public function department()
    {
        return $this->hasOne(Department::class, 'id', 'department_id');
    }

    /**
     * The section that belong to the user.
     */
    public function section()
    {
        return $this->hasOne(Section::class, 'id', 'section_id');
    }
}
