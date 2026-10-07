<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'status',
    ];

    public function planRecords(): HasMany
    {
        return $this->hasMany(PlanRecord::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'Active' => 'badge badge-hit',
            'Deactive' => 'badge badge-void',
            default => 'badge bg-secondary',
        };
    }
}
