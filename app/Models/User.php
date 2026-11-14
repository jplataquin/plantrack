<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'must_reset_password',
        'profile_picture',
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

    public function planRecords(): HasMany
    {
        return $this->hasMany(PlanRecord::class, 'executor_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function getProfilePictureUrlAttribute(): ?string
    {
        return $this->profile_picture ? asset('storage/'.$this->profile_picture) : null;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('Admin');
    }

    public function isMarshall(): bool
    {
        return $this->hasRole('Marshall');
    }

    public function isExecutor(): bool
    {
        return $this->hasRole('Executor');
    }

    public function hasMarshallPrivileges(): bool
    {
        return $this->hasAnyRole(['Marshall', 'Admin']);
    }

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
            'must_reset_password' => 'boolean',
        ];
    }
}
