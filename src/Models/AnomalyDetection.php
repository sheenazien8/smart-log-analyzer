<?php

namespace SmartLogAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AnomalyDetection extends Model
{
    protected $table = 'smart_log_anomaly_detections';

    protected $fillable = [
        'anomaly_type',
        'metric',
        'baseline_value',
        'detected_value',
        'deviation_score',
        'detection_time',
        'period_start',
        'period_end',
        'metadata',
        'status',
    ];

    protected $casts = [
        'baseline_value' => 'decimal:4',
        'detected_value' => 'decimal:4',
        'deviation_score' => 'decimal:4',
        'detection_time' => 'datetime',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'metadata' => 'array',
    ];

    public function scopeByType($query, string $type)
    {
        return $query->where('anomaly_type', $type);
    }

    public function scopeByMetric($query, string $metric)
    {
        return $query->where('metric', $metric);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeIgnored($query)
    {
        return $query->where('status', 'ignored');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('detection_time', '>=', now()->subHours($hours));
    }

    public function scopeHighDeviation($query, float $minScore = 3.0)
    {
        return $query->where('deviation_score', '>=', $minScore);
    }

    public function markAsResolved(): void
    {
        $this->update(['status' => 'resolved']);
    }

    public function markAsIgnored(): void
    {
        $this->update(['status' => 'ignored']);
    }

    public function getSeverityAttribute(): string
    {
        $score = $this->deviation_score;

        if ($score >= 5.0) {
            return 'critical';
        } elseif ($score >= 3.0) {
            return 'high';
        } elseif ($score >= 2.0) {
            return 'medium';
        }

        return 'low';
    }

    public function getChangePercentageAttribute(): float
    {
        if ($this->baseline_value == 0) {
            return $this->detected_value > 0 ? 100.0 : 0.0;
        }

        return round((($this->detected_value - $this->baseline_value) / $this->baseline_value) * 100, 2);
    }

    public function getDescriptionAttribute(): string
    {
        $changeType = $this->detected_value > $this->baseline_value ? 'increase' : 'decrease';
        $percentage = abs($this->change_percentage);

        return sprintf(
            '%s anomaly detected: %s %s by %.1f%% (from %.2f to %.2f)',
            ucfirst($this->anomaly_type),
            $this->metric,
            $changeType,
            $percentage,
            $this->baseline_value,
            $this->detected_value
        );
    }

    public function getDurationAttribute(): string
    {
        $minutes = $this->period_start->diffInMinutes($this->period_end);
        
        if ($minutes < 60) {
            return "{$minutes} minutes";
        }
        
        $hours = round($minutes / 60, 1);
        return "{$hours} hours";
    }
}