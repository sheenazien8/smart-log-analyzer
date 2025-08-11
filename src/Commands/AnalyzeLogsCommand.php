<?php

namespace SmartLogAnalyzer\Commands;

use Illuminate\Console\Command;
use SmartLogAnalyzer\Services\LogParser;
use SmartLogAnalyzer\Services\AnomalyDetector;
use SmartLogAnalyzer\Services\AlertManager;
use SmartLogAnalyzer\Jobs\ProcessLogFileJob;
use Illuminate\Support\Facades\Cache;

class AnalyzeLogsCommand extends Command
{
    protected $signature = 'smart-log:analyze 
                           {--file= : Specific log file to analyze}
                           {--incremental : Only analyze new entries since last run}
                           {--detect-anomalies : Run anomaly detection}
                           {--process-alerts : Process alert rules}
                           {--force : Force analysis even if recently run}';

    protected $description = 'Analyze log files for patterns and anomalies';

    public function handle(
        LogParser $logParser,
        AnomalyDetector $anomalyDetector,
        AlertManager $alertManager
    ): int {
        $this->info('Starting log analysis...');

        if (!$this->shouldRun()) {
            $this->info('Analysis skipped - recently run. Use --force to override.');
            return 0;
        }

        $this->analyzeLogFiles($logParser);

        if ($this->option('detect-anomalies')) {
            $this->detectAnomalies($anomalyDetector);
        }

        if ($this->option('process-alerts')) {
            $this->processAlerts($alertManager);
        }

        $this->updateLastRunTime();
        $this->info('Log analysis completed successfully!');

        return 0;
    }

    private function shouldRun(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        $lastRun = Cache::get('smart_log_analyzer_last_run');
        
        if (!$lastRun) {
            return true;
        }

        $minInterval = config('smart-log-analyzer.processing.min_interval', 300); // 5 minutes
        return now()->diffInSeconds($lastRun) >= $minInterval;
    }

    private function analyzeLogFiles(LogParser $logParser): void
    {
        $specificFile = $this->option('file');
        $isIncremental = $this->option('incremental');

        if ($specificFile) {
            $this->info("Analyzing specific file: {$specificFile}");
            $this->analyzeFile($specificFile, $isIncremental);
            return;
        }

        $logPaths = config('smart-log-analyzer.log_paths', []);
        
        if (empty($logPaths)) {
            $this->warn('No log paths configured.');
            return;
        }

        $logFiles = $logParser->getLogFiles($logPaths);
        
        if ($logFiles->isEmpty()) {
            $this->warn('No log files found.');
            return;
        }

        $this->info("Found {$logFiles->count()} log files to analyze.");

        $bar = $this->output->createProgressBar($logFiles->count());
        $bar->start();

        foreach ($logFiles as $logFile) {
            $this->analyzeFile($logFile, $isIncremental);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function analyzeFile(string $filePath, bool $isIncremental): void
    {
        try {
            if (config('smart-log-analyzer.processing.use_queue', true)) {
                ProcessLogFileJob::dispatch($filePath, $isIncremental);
            } else {
                ProcessLogFileJob::dispatchSync($filePath, $isIncremental);
            }
        } catch (\Exception $e) {
            $this->error("Failed to analyze {$filePath}: " . $e->getMessage());
        }
    }

    private function detectAnomalies(AnomalyDetector $anomalyDetector): void
    {
        $this->info('Detecting anomalies...');

        try {
            $anomalies = $anomalyDetector->detectAnomalies();
            
            if ($anomalies->isEmpty()) {
                $this->info('No anomalies detected.');
            } else {
                $this->info("Detected {$anomalies->count()} anomalies.");
                
                if ($this->output->isVerbose()) {
                    foreach ($anomalies as $anomaly) {
                        $this->line("- {$anomaly->anomaly_type}: {$anomaly->metric} (score: {$anomaly->deviation_score})");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('Anomaly detection failed: ' . $e->getMessage());
        }
    }

    private function processAlerts(AlertManager $alertManager): void
    {
        $this->info('Processing alert rules...');

        try {
            $alertManager->processAlerts();
            $this->info('Alert processing completed.');
        } catch (\Exception $e) {
            $this->error('Alert processing failed: ' . $e->getMessage());
        }
    }

    private function updateLastRunTime(): void
    {
        Cache::put('smart_log_analyzer_last_run', now(), now()->addDay());
    }
}