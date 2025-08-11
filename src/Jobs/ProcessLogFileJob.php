<?php

namespace SmartLogAnalyzer\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SmartLogAnalyzer\Services\LogParser;
use SmartLogAnalyzer\Services\PatternAnalyzer;
use SmartLogAnalyzer\Models\LogEntry;
use Illuminate\Support\Facades\Log;

class ProcessLogFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 3;

    private string $filePath;
    private bool $isIncremental;

    public function __construct(string $filePath, bool $isIncremental = false)
    {
        $this->filePath = $filePath;
        $this->isIncremental = $isIncremental;
        
        $this->onQueue(config('smart-log-analyzer.processing.queue_connection', 'default'));
    }

    public function handle(LogParser $logParser, PatternAnalyzer $patternAnalyzer): void
    {
        try {
            Log::info('Processing log file', ['file' => $this->filePath, 'incremental' => $this->isIncremental]);

            $entries = $this->isIncremental 
                ? $logParser->parseRecentEntries($this->filePath, 1)
                : $logParser->parseLogFile($this->filePath);

            $batchSize = config('smart-log-analyzer.processing.batch_size', 1000);
            $processedCount = 0;

            foreach ($entries->chunk($batchSize) as $batch) {
                $this->processBatch($batch, $patternAnalyzer);
                $processedCount += $batch->count();
            }

            Log::info('Log file processing completed', [
                'file' => $this->filePath,
                'processed_entries' => $processedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Log file processing failed', [
                'file' => $this->filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    private function processBatch($batch, PatternAnalyzer $patternAnalyzer): void
    {
        $logEntries = [];
        
        foreach ($batch as $entryData) {
            $existingEntry = LogEntry::where('hash', $entryData['hash'])
                ->where('logged_at', $entryData['logged_at'])
                ->first();

            if (!$existingEntry) {
                $logEntries[] = $entryData;
                $patternAnalyzer->analyzeLogEntry($entryData);
            }
        }

        if (!empty($logEntries)) {
            LogEntry::insert($logEntries);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessLogFileJob failed permanently', [
            'file' => $this->filePath,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}