<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

class TargetObjective extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_record_id',
        'user_id',
        'description',
        'quantity',
        'actual',
        'unit',
        'priority',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (TargetObjective $model) {
            if (empty($model->user_id) && Auth::check()) {
                $model->user_id = Auth::id();
            }
        });
    }

    protected function casts(): array
    {
        return [];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isMarshallMade(): bool
    {
        return true;
    }

    public function setActualAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['actual'] = null;
            return;
        }

        if (! is_numeric($value)) {
            throw new \InvalidArgumentException('The actual field must be numeric.');
        }

        $this->attributes['actual'] = $value + 0;
    }

    public function getActualAttribute($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? $value + 0 : $value;
    }

    public function getQuantityWithUnitAttribute(): string
    {
        return $this->quantity.($this->unit ? ' '.$this->unit : '');
    }

    public function getActualWithUnitAttribute(): string
    {
        if ($this->actual === null || $this->actual === '') {
            return '—';
        }

        return $this->actual.($this->unit ? ' '.$this->unit : '');
    }

    public function planRecord(): BelongsTo
    {
        return $this->belongsTo(PlanRecord::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class, 'target_objective_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'Hit' => 'badge badge-hit',
            'Missed' => 'badge badge-missed',
            'Void' => 'badge badge-void',
            default => 'badge badge-unevaluated',
        };
    }

    public function getPriorityBadgeClassAttribute(): string
    {
        return match (strtolower($this->priority ?? 'low')) {
            'critical', 'urgent' => 'badge badge-priority-critical',
            'high' => 'badge badge-priority-high',
            'normal', 'medium' => 'badge badge-priority-normal',
            default => 'badge badge-priority-low',
        };
    }
}
