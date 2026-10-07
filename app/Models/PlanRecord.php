<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'executor_id',
        'project_id',
        'status',
        'start_date',
        'end_date',
        'target_weight',
        'resource_weight',
        'risk_weight',
        'budget_weight',
    ];

    protected $attributes = [
        'target_weight' => 60.00,
        'resource_weight' => 20.00,
        'risk_weight' => 10.00,
        'budget_weight' => 10.00,
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'target_weight' => 'float',
            'resource_weight' => 'float',
            'risk_weight' => 'float',
            'budget_weight' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (PlanRecord $plan) {
            $rawTitle = $plan->getAttributes()['title'] ?? null;
            if (empty($rawTitle)) {
                $paddedId = str_pad((string) $plan->id, 4, '0', STR_PAD_LEFT);
                $plan->updateQuietly([
                    'title' => "Plan - {$paddedId}",
                ]);
            }
        });
    }

    public function getTitleAttribute(?string $value): string
    {
        if (! empty($value)) {
            return $value;
        }

        $paddedId = str_pad((string) ($this->id ?? 0), 4, '0', STR_PAD_LEFT);

        return "Plan - {$paddedId}";
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executor_id');
    }

    public function targetObjectives(): HasMany
    {
        return $this->hasMany(TargetObjective::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function riskManagements(): HasMany
    {
        return $this->hasMany(RiskManagement::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'Open' => 'badge badge-plan-open',
            'Review' => 'badge badge-plan-review',
            'Close' => 'badge badge-plan-close',
            'Void' => 'badge badge-plan-void',
            default => 'badge bg-secondary',
        };
    }

    public function hasNoComponents(): bool
    {
        return $this->targetObjectives->isEmpty()
            && $this->resources->isEmpty()
            && $this->riskManagements->isEmpty()
            && $this->budgets->isEmpty();
    }

    /**
     * Get summary counts of pending (unevaluated) components.
     */
    public function getPendingComponentsSummary(): array
    {
        $validStatuses = ['Hit', 'Missed', 'Void'];

        $pendingTargets = $this->targetObjectives()
            ->where(function ($q) use ($validStatuses) {
                $q->whereNull('status')->orWhereNotIn('status', $validStatuses);
            })->count();

        $pendingResources = $this->resources()
            ->where(function ($q) use ($validStatuses) {
                $q->whereNull('status')->orWhereNotIn('status', $validStatuses);
            })->count();

        $pendingRisks = $this->riskManagements()
            ->where(function ($q) use ($validStatuses) {
                $q->whereNull('status')->orWhereNotIn('status', $validStatuses);
            })->count();

        $pendingBudgets = $this->budgets()
            ->where(function ($q) use ($validStatuses) {
                $q->whereNull('status')->orWhereNotIn('status', $validStatuses);
            })->count();

        $total = $pendingTargets + $pendingResources + $pendingRisks + $pendingBudgets;

        return [
            'total' => $total,
            'targets' => $pendingTargets,
            'resources' => $pendingResources,
            'risks' => $pendingRisks,
            'budgets' => $pendingBudgets,
        ];
    }

    /**
     * Determine if there are any components pending evaluation.
     */
    public function hasPendingComponents(): bool
    {
        return $this->getPendingComponentsCount() > 0;
    }

    /**
     * Get the total count of pending (unevaluated) components.
     */
    public function getPendingComponentsCount(): int
    {
        return $this->getPendingComponentsSummary()['total'];
    }

    /**
     * Get a human-readable list of pending component types.
     */
    public function getPendingComponentsDescription(): string
    {
        $summary = $this->getPendingComponentsSummary();
        $parts = [];

        if ($summary['targets'] > 0) {
            $parts[] = $summary['targets'].' Target Objective'.($summary['targets'] > 1 ? 's' : '');
        }
        if ($summary['resources'] > 0) {
            $parts[] = $summary['resources'].' Resource'.($summary['resources'] > 1 ? 's' : '');
        }
        if ($summary['risks'] > 0) {
            $parts[] = $summary['risks'].' Risk Item'.($summary['risks'] > 1 ? 's' : '');
        }
        if ($summary['budgets'] > 0) {
            $parts[] = $summary['budgets'].' Budget Item'.($summary['budgets'] > 1 ? 's' : '');
        }

        return ! empty($parts) ? implode(', ', $parts) : 'None';
    }

    public function calculateComponentScore($items): float
    {
        $total = $items->count();
        $void = $items->where('status', 'Void')->count();
        $hit = $items->where('status', 'Hit')->count();

        $valid = $total - $void;

        if ($valid <= 0) {
            return 100.0;
        }

        return round(($hit / $valid) * 100, 2);
    }

    public function getTargetScoreAttribute(): float
    {
        if ($this->hasNoComponents()) {
            return 0.0;
        }

        return $this->calculateComponentScore($this->targetObjectives);
    }

    public function getResourceScoreAttribute(): float
    {
        if ($this->hasNoComponents()) {
            return 0.0;
        }

        return $this->calculateComponentScore($this->resources);
    }

    public function getRiskScoreAttribute(): float
    {
        if ($this->hasNoComponents()) {
            return 0.0;
        }

        return $this->calculateComponentScore($this->riskManagements);
    }

    public function getBudgetScoreAttribute(): float
    {
        if ($this->hasNoComponents()) {
            return 0.0;
        }

        return $this->calculateComponentScore($this->budgets);
    }

    public function getTargetWeightAttribute($value): float
    {
        return $value !== null ? (float) $value : 60.0;
    }

    public function getResourceWeightAttribute($value): float
    {
        return $value !== null ? (float) $value : 20.0;
    }

    public function getRiskWeightAttribute($value): float
    {
        return $value !== null ? (float) $value : 10.0;
    }

    public function getBudgetWeightAttribute($value): float
    {
        return $value !== null ? (float) $value : 10.0;
    }

    public function getEvaluationScoreAttribute(): float
    {
        if ($this->hasNoComponents()) {
            return 0.0;
        }

        $targetWeight = $this->target_weight;
        $resourceWeight = $this->resource_weight;
        $riskWeight = $this->risk_weight;
        $budgetWeight = $this->budget_weight;

        $finalScore = ($this->target_score * ($targetWeight / 100))
            + ($this->resource_score * ($resourceWeight / 100))
            + ($this->risk_score * ($riskWeight / 100))
            + ($this->budget_score * ($budgetWeight / 100));

        return round($finalScore, 2);
    }
}
