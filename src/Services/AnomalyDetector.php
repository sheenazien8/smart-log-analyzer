<?php

namespace SmartLogAnalyzer\Services;

use SmartLogAnalyzer\Models\LogEntry;
use SmartLogAnalyzer\Models\ErrorPattern;
use SmartLogAnalyzer\Models\AnomalyDetection;
use SmartLogAnalyzer\Services\DatabaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnomalyDetector
{
    private bool $enabled;
    private float $spikeThreshold;
    private int $minimumBaselineHours;
    private int $checkInterval;

    public function __construct()
    {
        $config = config('smart-log-analyzer.anomaly_detection');
        $this->enabled = $config['enabled'] ?? true;
        $this->spikeThreshold = $config['spike_threshold'] ?? 5.0;
        $this->minimumBaselineHours = $config['minimum_baseline_hours'] ?? 24;
        $this->checkInterval = $config['check_interval'] ?? 300;
    }

    public function detectAnomalies(): Collection
    {
        if (!$this->enabled) {
            return collect();
        }

        $anomalies = collect();

        $anomalies = $anomalies->merge($this->detectErrorRateSpikes());
        $anomalies = $anomalies->merge($this->detectPatternAnomalies());
        $anomalies = $anomalies->merge($this->detectVolumeAnomalies());
        $anomalies = $anomalies->merge($this->detectNewErrorPatterns());

        return $anomalies;
    }

    public function detectErrorRateSpikes(): Collection
    {
        $anomalies = collect();
        $currentHour = now()->startOfHour();
        
        $levels = ['emergency', 'alert', 'critical', 'error', 'warning'];

        foreach ($levels as $level) {
            $currentRate = $this->getErrorRateForPeriod($level, $currentHour, $currentHour->copy()->addHour());
            $baselineRate = $this->getBaselineErrorRate($level, $currentHour);

            if ($baselineRate > 0 && $currentRate / $baselineRate >= $this->spikeThreshold) {
                $anomaly = $this->createAnomalyDetection([
                    'anomaly_type' => 'spike',
                    'metric' => "error_rate_{$level}",
                    'baseline_value' => $baselineRate,
                    'detected_value' => $currentRate,
                    'deviation_score' => $currentRate / $baselineRate,
                    'detection_time' => now(),
                    'period_start' => $currentHour,
                    'period_end' => $currentHour->copy()->addHour(),
                    'metadata' => [
                        'level' => $level,
                        'threshold_multiplier' => $this->spikeThreshold,
                    ],
                ]);

                $anomalies->push($anomaly);
            }
        }

        return $anomalies;
    }

    public function detectPatternAnomalies(): Collection
    {
        $anomalies = collect();
        $recentPatterns = ErrorPattern::where('last_seen', '>=', now()->subHours(1))->get();

        foreach ($recentPatterns as $pattern) {
            $recentRate = $this->getPatternRateForPeriod($pattern, now()->subHour(), now());
            $baselineRate = $this->getBaselinePatternRate($pattern);

            if ($baselineRate > 0 && $recentRate / $baselineRate >= $this->spikeThreshold) {
                $anomaly = $this->createAnomalyDetection([
                    'anomaly_type' => 'pattern_spike',
                    'metric' => "pattern_{$pattern->id}",
                    'baseline_value' => $baselineRate,
                    'detected_value' => $recentRate,
                    'deviation_score' => $recentRate / $baselineRate,
                    'detection_time' => now(),
                    'period_start' => now()->subHour(),
                    'period_end' => now(),
                    'metadata' => [
                        'pattern_id' => $pattern->id,
                        'pattern_hash' => $pattern->pattern_hash,
                        'error_type' => $pattern->error_type,
                        'severity' => $pattern->severity,
                    ],
                ]);

                $anomalies->push($anomaly);
            }
        }

        return $anomalies;
    }

    public function detectVolumeAnomalies(): Collection
    {
        $anomalies = collect();
        $currentHour = now()->startOfHour();
        
        $currentVolume = LogEntry::whereBetween('logged_at', [
            $currentHour,
            $currentHour->copy()->addHour()
        ])->count();

        $baselineVolume = $this->getBaselineLogVolume($currentHour);

        if ($baselineVolume > 0) {
            $ratio = $currentVolume / $baselineVolume;
            
            if ($ratio >= $this->spikeThreshold) {
                $anomaly = $this->createAnomalyDetection([
                    'anomaly_type' => 'volume_spike',
                    'metric' => 'total_log_volume',
                    'baseline_value' => $baselineVolume,
                    'detected_value' => $currentVolume,
                    'deviation_score' => $ratio,
                    'detection_time' => now(),
                    'period_start' => $currentHour,
                    'period_end' => $currentHour->copy()->addHour(),
                    'metadata' => [
                        'threshold_multiplier' => $this->spikeThreshold,
                    ],
                ]);

                $anomalies->push($anomaly);
            } elseif ($ratio <= 0.2 && $baselineVolume > 10) {
                $anomaly = $this->createAnomalyDetection([
                    'anomaly_type' => 'volume_drop',
                    'metric' => 'total_log_volume',
                    'baseline_value' => $baselineVolume,
                    'detected_value' => $currentVolume,
                    'deviation_score' => $baselineVolume / max($currentVolume, 1),
                    'detection_time' => now(),
                    'period_start' => $currentHour,
                    'period_end' => $currentHour->copy()->addHour(),
                    'metadata' => [
                        'drop_threshold' => 0.2,
                    ],
                ]);

                $anomalies->push($anomaly);
            }
        }

        return $anomalies;
    }

    public function detectNewErrorPatterns(): Collection
    {
        $anomalies = collect();
        $newPatterns = ErrorPattern::where('first_seen', '>=', now()->subHours(1))
            ->where('severity', 'critical')
            ->get();

        foreach ($newPatterns as $pattern) {
            $anomaly = $this->createAnomalyDetection([
                'anomaly_type' => 'new_critical_pattern',
                'metric' => "new_pattern_{$pattern->error_type}",
                'baseline_value' => 0,
                'detected_value' => $pattern->occurrence_count,
                'deviation_score' => $pattern->occurrence_count,
                'detection_time' => now(),
                'period_start' => $pattern->first_seen,
                'period_end' => $pattern->last_seen,
                'metadata' => [
                    'pattern_id' => $pattern->id,
                    'pattern_hash' => $pattern->pattern_hash,
                    'error_type' => $pattern->error_type,
                    'severity' => $pattern->severity,
                ],
            ]);

            $anomalies->push($anomaly);
        }

        return $anomalies;
    }

    public function calculateDeviationScore(float $currentValue, float $baselineValue): float
    {
        if ($baselineValue == 0) {
            return $currentValue > 0 ? 10.0 : 0.0;
        }

        return abs($currentValue - $baselineValue) / $baselineValue;
    }

    public function isAnomalous(float $deviationScore): bool
    {
        return $deviationScore >= 2.0;
    }

    private function getErrorRateForPeriod(string $level, Carbon $start, Carbon $end): float
    {
        $count = LogEntry::where('level', $level)
            ->whereBetween('logged_at', [$start, $end])
            ->count();

        $hours = $start->diffInHours($end);
        return $hours > 0 ? $count / $hours : $count;
    }

    private function getBaselineErrorRate(string $level, Carbon $currentTime): float
    {
        $cacheKey = "baseline_error_rate_{$level}_{$currentTime->format('Y-m-d-H')}";

        return Cache::remember($cacheKey, 3600, function () use ($level, $currentTime) {
            $startTime = $currentTime->copy()->subHours($this->minimumBaselineHours);
            $endTime = $currentTime->copy()->subHour();

            if ($startTime->gte($endTime)) {
                return 0.0;
            }

            $count = LogEntry::where('level', $level)
                ->whereBetween('logged_at', [$startTime, $endTime])
                ->count();

            $hours = $startTime->diffInHours($endTime);
            return $hours > 0 ? $count / $hours : 0.0;
        });
    }

    private function getPatternRateForPeriod(ErrorPattern $pattern, Carbon $start, Carbon $end): float
    {
        $count = $pattern->logEntries()
            ->whereBetween('logged_at', [$start, $end])
            ->count();

        $hours = $start->diffInHours($end);
        return $hours > 0 ? $count / $hours : $count;
    }

    private function getBaselinePatternRate(ErrorPattern $pattern): float
    {
        $cacheKey = "baseline_pattern_rate_{$pattern->id}";

        return Cache::remember($cacheKey, 1800, function () use ($pattern) {
            $endTime = now()->subHour();
            $startTime = $endTime->copy()->subHours($this->minimumBaselineHours);

            $count = $pattern->logEntries()
                ->whereBetween('logged_at', [$startTime, $endTime])
                ->count();

            $hours = $startTime->diffInHours($endTime);
            return $hours > 0 ? $count / $hours : 0.0;
        });
    }

    private function getBaselineLogVolume(Carbon $currentTime): float
    {
        $cacheKey = "baseline_log_volume_{$currentTime->format('Y-m-d-H')}";

        return Cache::remember($cacheKey, 3600, function () use ($currentTime) {
            $startTime = $currentTime->copy()->subHours($this->minimumBaselineHours);
            $endTime = $currentTime->copy()->subHour();

            $count = LogEntry::whereBetween('logged_at', [$startTime, $endTime])->count();
            $hours = $startTime->diffInHours($endTime);
            
            return $hours > 0 ? $count / $hours : 0.0;
        });
    }

    private function createAnomalyDetection(array $data): AnomalyDetection
    {
        $existingAnomaly = AnomalyDetection::where('anomaly_type', $data['anomaly_type'])
            ->where('metric', $data['metric'])
            ->where('detection_time', '>=', now()->subMinutes($this->checkInterval / 60))
            ->where('status', 'active')
            ->first();

        if ($existingAnomaly) {
            $existingAnomaly->update([
                'detected_value' => $data['detected_value'],
                'deviation_score' => $data['deviation_score'],
                'detection_time' => $data['detection_time'],
                'metadata' => array_merge($existingAnomaly->metadata ?? [], $data['metadata'] ?? []),
            ]);

            return $existingAnomaly;
        }

        return AnomalyDetection::create($data);
    }

    public function getAnomalyStatistics(): array
    {
        $stats = [
            'total_anomalies' => AnomalyDetection::count(),
            'active_anomalies' => AnomalyDetection::active()->count(),
            'resolved_anomalies' => AnomalyDetection::resolved()->count(),
            'recent_anomalies' => AnomalyDetection::recent(24)->count(),
        ];

        $stats['anomalies_by_type'] = AnomalyDetection::selectRaw('anomaly_type, COUNT(*) as count')
            ->groupBy('anomaly_type')
            ->pluck('count', 'anomaly_type')
            ->toArray();

        $severityCase = DatabaseService::getDeviationScoreSeverityCase();
        
        $stats['anomalies_by_severity'] = AnomalyDetection::selectRaw("
                {$severityCase} as severity,
                COUNT(*) as count
            ")
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        return $stats;
    }
}