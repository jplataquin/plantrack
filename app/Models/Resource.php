<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_record_id',
        'user_id',
        'target_objective_id',
        'description',
        'quantity',
        'actual',
        'unit',
        'target_date',
        'date_available',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (Resource $model) {
            if (empty($model->user_id) && Auth::check()) {
                $model->user_id = Auth::id();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'date_available' => 'date',
        ];
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
        if ($this->user_id) {
            $creator = $this->relationLoaded('user') ? $this->user : User::find($this->user_id);
            if ($creator) {
                return $creator->hasAnyRole(['Marshall', 'Admin']);
            }
        }

        return true;
    }

    public function planRecord(): BelongsTo
    {
        return $this->belongsTo(PlanRecord::class);
    }

    public function targetObjective(): BelongsTo
    {
        return $this->belongsTo(TargetObjective::class, 'target_objective_id');
    }

    public function for(): BelongsTo
    {
        return $this->belongsTo(TargetObjective::class, 'target_objective_id');
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
}
