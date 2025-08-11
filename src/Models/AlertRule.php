<?php

namespace SmartLogAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AlertRule extends Model
{
    protected $table = 'smart_log_alert_rules';

    protected $fillable = [
        'name',
        'description',
        'trigger_type',
        'conditions',
        'severity',
        'is_active',
        'throttle_minutes',
        'notification_channels',
        'recipients',
        'last_triggered',
        'trigger_count',
    ];

    protected $casts = [
        'conditions' => 'array',
        'notification_channels' => 'array',
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_triggered' => 'datetime',
        'trigger_count' => 'integer',
        'throttle_minutes' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTriggerType($query, string $triggerType)
    {
        return $query->where('trigger_type', $triggerType);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeReadyToTrigger($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('last_triggered')
                  ->orWhere('last_triggered', '<=', now()->subMinutes($this->throttle_minutes ?? 60));
            });
    }

    public function canTrigger(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->last_triggered) {
            return true;
        }

        return $this->last_triggered->addMinutes($this->throttle_minutes)->isPast();
    }

    public function trigger(): void
    {
        $this->update([
            'last_triggered' => now(),
            'trigger_count' => $this->trigger_count + 1,
        ]);
    }

    public function evaluateConditions(array $data): bool
    {
        $conditions = $this->conditions;

        switch ($this->trigger_type) {
            case 'threshold':
                return $this->evaluateThresholdConditions($conditions, $data);
            case 'anomaly':
                return $this->evaluateAnomalyConditions($conditions, $data);
            case 'pattern':
                return $this->evaluatePatternConditions($conditions, $data);
            default:
                return false;
        }
    }

    private function evaluateThresholdConditions(array $conditions, array $data): bool
    {
        $metric = $conditions['metric'] ?? null;
        $operator = $conditions['operator'] ?? '>';
        $threshold = $conditions['threshold'] ?? 0;
        $timeWindow = $conditions['time_window'] ?? 3600; // seconds

        if (!$metric || !isset($data[$metric])) {
            return false;
        }

        $value = $data[$metric];

        switch ($operator) {
            case '>':
                return $value > $threshold;
            case '>=':
                return $value >= $threshold;
            case '<':
                return $value < $threshold;
            case '<=':
                return $value <= $threshold;
            case '=':
            case '==':
                return $value == $threshold;
            case '!=':
                return $value != $threshold;
            default:
                return false;
        }
    }

    private function evaluateAnomalyConditions(array $conditions, array $data): bool
    {
        $deviationThreshold = $conditions['deviation_threshold'] ?? 2.0;
        $deviationScore = $data['deviation_score'] ?? 0;

        return $deviationScore >= $deviationThreshold;
    }

    private function evaluatePatternConditions(array $conditions, array $data): bool
    {
        $patternHash = $conditions['pattern_hash'] ?? null;
        $minOccurrences = $conditions['min_occurrences'] ?? 1;
        $timeWindow = $conditions['time_window'] ?? 3600;

        if (!$patternHash) {
            return false;
        }

        $pattern = ErrorPattern::where('pattern_hash', $patternHash)->first();
        
        if (!$pattern) {
            return false;
        }

        $recentOccurrences = $pattern->logEntries()
            ->where('logged_at', '>=', now()->subSeconds($timeWindow))
            ->count();

        return $recentOccurrences >= $minOccurrences;
    }

    public function getThrottleStatusAttribute(): string
    {
        if (!$this->last_triggered) {
            return 'ready';
        }

        $nextTriggerTime = $this->last_triggered->addMinutes($this->throttle_minutes);
        
        if ($nextTriggerTime->isPast()) {
            return 'ready';
        }

        return 'throttled';
    }

    public function getNextTriggerTimeAttribute(): ?Carbon
    {
        if (!$this->last_triggered) {
            return null;
        }

        return $this->last_triggered->addMinutes($this->throttle_minutes);
    }
}