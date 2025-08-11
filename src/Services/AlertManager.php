<?php

namespace SmartLogAnalyzer\Services;

use SmartLogAnalyzer\Models\AlertRule;
use SmartLogAnalyzer\Models\ErrorPattern;
use SmartLogAnalyzer\Models\AnomalyDetection;
use SmartLogAnalyzer\Jobs\SendAlertJob;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AlertManager
{
    private bool $alertsEnabled;
    private array $emailConfig;
    private array $severityLevels;
    private array $thresholds;

    public function __construct()
    {
        $config = config('smart-log-analyzer.alerts');
        $this->alertsEnabled = $config['enabled'] ?? true;
        $this->emailConfig = $config['email'] ?? [];
        $this->severityLevels = $config['severity_levels'] ?? [];
        $this->thresholds = $config['thresholds'] ?? [];
    }

    public function processAlerts(): void
    {
        if (!$this->alertsEnabled) {
            return;
        }

        $this->checkThresholdAlerts();
        $this->checkAnomalyAlerts();
        $this->checkPatternAlerts();
    }

    public function checkThresholdAlerts(): void
    {
        $rules = AlertRule::active()
            ->where('trigger_type', 'threshold')
            ->get();

        foreach ($rules as $rule) {
            if (!$rule->canTrigger()) {
                continue;
            }

            $data = $this->gatherThresholdData($rule);
            
            if ($rule->evaluateConditions($data)) {
                $this->triggerAlert($rule, $data);
            }
        }
    }

    public function checkAnomalyAlerts(): void
    {
        $rules = AlertRule::active()
            ->where('trigger_type', 'anomaly')
            ->get();

        $recentAnomalies = AnomalyDetection::active()
            ->where('detection_time', '>=', now()->subMinutes(15))
            ->get();

        foreach ($rules as $rule) {
            if (!$rule->canTrigger()) {
                continue;
            }

            foreach ($recentAnomalies as $anomaly) {
                $data = [
                    'anomaly_id' => $anomaly->id,
                    'anomaly_type' => $anomaly->anomaly_type,
                    'metric' => $anomaly->metric,
                    'deviation_score' => $anomaly->deviation_score,
                    'baseline_value' => $anomaly->baseline_value,
                    'detected_value' => $anomaly->detected_value,
                ];

                if ($rule->evaluateConditions($data)) {
                    $this->triggerAlert($rule, $data);
                    break;
                }
            }
        }
    }

    public function checkPatternAlerts(): void
    {
        $rules = AlertRule::active()
            ->where('trigger_type', 'pattern')
            ->get();

        foreach ($rules as $rule) {
            if (!$rule->canTrigger()) {
                continue;
            }

            $data = $this->gatherPatternData($rule);
            
            if ($rule->evaluateConditions($data)) {
                $this->triggerAlert($rule, $data);
            }
        }
    }

    public function triggerAlert(AlertRule $rule, array $data): void
    {
        try {
            $rule->trigger();

            $alertData = [
                'rule' => $rule,
                'data' => $data,
                'timestamp' => now(),
            ];

            foreach ($rule->notification_channels as $channel) {
                switch ($channel) {
                    case 'email':
                        $this->sendEmailAlert($alertData);
                        break;
                    case 'slack':
                        $this->sendSlackAlert($alertData);
                        break;
                    case 'webhook':
                        $this->sendWebhookAlert($alertData);
                        break;
                }
            }

            Log::info('Alert triggered', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'trigger_type' => $rule->trigger_type,
                'severity' => $rule->severity,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to trigger alert', [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }

    public function createDefaultAlertRules(): void
    {
        $defaultRules = [
            [
                'name' => 'Critical Error Threshold',
                'description' => 'Alert when critical errors exceed threshold',
                'trigger_type' => 'threshold',
                'conditions' => [
                    'metric' => 'critical_errors_per_hour',
                    'operator' => '>',
                    'threshold' => $this->thresholds['critical'] ?? 1,
                    'time_window' => 3600,
                ],
                'severity' => 'critical',
                'throttle_minutes' => 30,
                'notification_channels' => ['email'],
                'recipients' => $this->emailConfig['recipients'] ?? [],
            ],
            [
                'name' => 'High Error Rate',
                'description' => 'Alert when error rate is unusually high',
                'trigger_type' => 'threshold',
                'conditions' => [
                    'metric' => 'errors_per_hour',
                    'operator' => '>',
                    'threshold' => $this->thresholds['high'] ?? 10,
                    'time_window' => 3600,
                ],
                'severity' => 'high',
                'throttle_minutes' => 60,
                'notification_channels' => ['email'],
                'recipients' => $this->emailConfig['recipients'] ?? [],
            ],
            [
                'name' => 'Anomaly Detection',
                'description' => 'Alert on detected anomalies',
                'trigger_type' => 'anomaly',
                'conditions' => [
                    'deviation_threshold' => 3.0,
                ],
                'severity' => 'medium',
                'throttle_minutes' => 45,
                'notification_channels' => ['email'],
                'recipients' => $this->emailConfig['recipients'] ?? [],
            ],
        ];

        foreach ($defaultRules as $ruleData) {
            AlertRule::firstOrCreate(
                ['name' => $ruleData['name']],
                $ruleData
            );
        }
    }

    public function sendTestAlert(AlertRule $rule): bool
    {
        try {
            $testData = [
                'rule' => $rule,
                'data' => [
                    'test' => true,
                    'message' => 'This is a test alert to verify configuration.',
                ],
                'timestamp' => now(),
            ];

            $this->sendEmailAlert($testData);
            return true;

        } catch (\Exception $e) {
            Log::error('Test alert failed', [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function gatherThresholdData(AlertRule $rule): array
    {
        $conditions = $rule->conditions;
        $metric = $conditions['metric'] ?? '';
        $timeWindow = $conditions['time_window'] ?? 3600;
        
        $startTime = now()->subSeconds($timeWindow);
        $endTime = now();

        $data = [];

        switch ($metric) {
            case 'critical_errors_per_hour':
                $data[$metric] = $this->getCriticalErrorsCount($startTime, $endTime) / ($timeWindow / 3600);
                break;
            case 'errors_per_hour':
                $data[$metric] = $this->getErrorsCount($startTime, $endTime) / ($timeWindow / 3600);
                break;
            case 'warnings_per_hour':
                $data[$metric] = $this->getWarningsCount($startTime, $endTime) / ($timeWindow / 3600);
                break;
            case 'total_logs_per_hour':
                $data[$metric] = $this->getTotalLogsCount($startTime, $endTime) / ($timeWindow / 3600);
                break;
        }

        return $data;
    }

    private function gatherPatternData(AlertRule $rule): array
    {
        $conditions = $rule->conditions;
        $patternHash = $conditions['pattern_hash'] ?? null;
        $timeWindow = $conditions['time_window'] ?? 3600;

        if (!$patternHash) {
            return [];
        }

        $pattern = ErrorPattern::where('pattern_hash', $patternHash)->first();
        
        if (!$pattern) {
            return [];
        }

        $recentCount = $pattern->logEntries()
            ->where('logged_at', '>=', now()->subSeconds($timeWindow))
            ->count();

        return [
            'pattern_id' => $pattern->id,
            'pattern_hash' => $patternHash,
            'recent_occurrences' => $recentCount,
            'total_occurrences' => $pattern->occurrence_count,
        ];
    }

    private function sendEmailAlert(array $alertData): void
    {
        $recipients = $alertData['rule']->recipients;
        
        if (empty($recipients)) {
            return;
        }

        SendAlertJob::dispatch($alertData, 'email');
    }

    private function sendSlackAlert(array $alertData): void
    {
        SendAlertJob::dispatch($alertData, 'slack');
    }

    private function sendWebhookAlert(array $alertData): void
    {
        SendAlertJob::dispatch($alertData, 'webhook');
    }

    private function getCriticalErrorsCount($startTime, $endTime): int
    {
        $criticalLevels = $this->severityLevels['critical'] ?? ['emergency', 'alert', 'critical'];
        
        return \SmartLogAnalyzer\Models\LogEntry::whereIn('level', $criticalLevels)
            ->whereBetween('logged_at', [$startTime, $endTime])
            ->count();
    }

    private function getErrorsCount($startTime, $endTime): int
    {
        $errorLevels = array_merge(
            $this->severityLevels['critical'] ?? [],
            $this->severityLevels['high'] ?? []
        );
        
        return \SmartLogAnalyzer\Models\LogEntry::whereIn('level', $errorLevels)
            ->whereBetween('logged_at', [$startTime, $endTime])
            ->count();
    }

    private function getWarningsCount($startTime, $endTime): int
    {
        $warningLevels = $this->severityLevels['medium'] ?? ['warning'];
        
        return \SmartLogAnalyzer\Models\LogEntry::whereIn('level', $warningLevels)
            ->whereBetween('logged_at', [$startTime, $endTime])
            ->count();
    }

    private function getTotalLogsCount($startTime, $endTime): int
    {
        return \SmartLogAnalyzer\Models\LogEntry::whereBetween('logged_at', [$startTime, $endTime])
            ->count();
    }

    public function getAlertStatistics(): array
    {
        return [
            'total_rules' => AlertRule::count(),
            'active_rules' => AlertRule::active()->count(),
            'triggered_today' => AlertRule::where('last_triggered', '>=', now()->startOfDay())->count(),
            'most_triggered' => AlertRule::orderBy('trigger_count', 'desc')->limit(5)->get(),
            'rules_by_severity' => AlertRule::selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray(),
        ];
    }
}