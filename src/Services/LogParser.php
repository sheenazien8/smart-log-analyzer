<?php

namespace SmartLogAnalyzer\Services;

use SmartLogAnalyzer\Models\LogEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;

class LogParser
{
    private const LARAVEL_LOG_PATTERN = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+)$/m';
    private const STACK_TRACE_PATTERN = '/^#\d+/m';

    public function parseLogFile(string $filePath): Collection
    {
        if (!File::exists($filePath)) {
            throw new \InvalidArgumentException("Log file not found: {$filePath}");
        }

        $content = File::get($filePath);
        return $this->parseLogContent($content);
    }

    public function parseLogContent(string $content): Collection
    {
        $entries = collect();
        $lines = explode("\n", $content);
        $currentEntry = null;
        $stackTrace = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            if ($this->isLogEntryStart($line)) {
                if ($currentEntry) {
                    $entries->push($this->createLogEntry($currentEntry, $stackTrace));
                    $stackTrace = [];
                }
                $currentEntry = $this->parseLogLine($line);
            } elseif ($currentEntry && $this->isStackTraceLine($line)) {
                $stackTrace[] = $line;
            } elseif ($currentEntry) {
                $currentEntry['message'] .= "\n" . $line;
            }
        }

        if ($currentEntry) {
            $entries->push($this->createLogEntry($currentEntry, $stackTrace));
        }

        return $entries;
    }

    public function parseRecentEntries(string $filePath, int $hours = 1): Collection
    {
        if (!File::exists($filePath)) {
            return collect();
        }

        $cutoffTime = now()->subHours($hours);
        $entries = $this->parseLogFile($filePath);

        return $entries->filter(function ($entry) use ($cutoffTime) {
            return $entry['logged_at']->gte($cutoffTime);
        });
    }

    public function watchLogFile(string $filePath, callable $callback): void
    {
        if (!File::exists($filePath)) {
            throw new \InvalidArgumentException("Log file not found: {$filePath}");
        }

        $lastSize = filesize($filePath);
        $handle = fopen($filePath, 'r');
        fseek($handle, $lastSize);

        while (true) {
            clearstatcache();
            $currentSize = filesize($filePath);

            if ($currentSize > $lastSize) {
                $newContent = fread($handle, $currentSize - $lastSize);
                $newEntries = $this->parseLogContent($newContent);
                
                foreach ($newEntries as $entry) {
                    $callback($entry);
                }

                $lastSize = $currentSize;
            }

            sleep(1);
        }

        fclose($handle);
    }

    private function isLogEntryStart(string $line): bool
    {
        return preg_match(self::LARAVEL_LOG_PATTERN, $line) === 1;
    }

    private function isStackTraceLine(string $line): bool
    {
        return preg_match(self::STACK_TRACE_PATTERN, $line) === 1 || 
               str_starts_with($line, '   at ') ||
               str_starts_with($line, 'Stack trace:');
    }

    private function parseLogLine(string $line): array
    {
        if (!preg_match(self::LARAVEL_LOG_PATTERN, $line, $matches)) {
            return [
                'logged_at' => now(),
                'level' => 'info',
                'channel' => 'unknown',
                'message' => $line,
                'context' => [],
            ];
        }

        [, $timestamp, $channel, $level, $message] = $matches;

        $context = $this->extractContext($message);
        $cleanMessage = $this->cleanMessage($message);

        return [
            'logged_at' => Carbon::createFromFormat('Y-m-d H:i:s', $timestamp),
            'level' => strtolower($level),
            'channel' => strtolower($channel),
            'message' => $cleanMessage,
            'context' => $context,
        ];
    }

    private function extractContext(string $message): array
    {
        $context = [];

        if (preg_match('/\{([^}]+)\}$/', $message, $matches)) {
            $contextString = $matches[1];
            
            if (preg_match_all('/"([^"]+)":([^,}]+)/', $contextString, $contextMatches, PREG_SET_ORDER)) {
                foreach ($contextMatches as $match) {
                    $key = $match[1];
                    $value = trim($match[2], ' "');
                    $context[$key] = $value;
                }
            }
        }

        $filePath = $this->extractFilePath($message);
        if ($filePath) {
            $context['file'] = $filePath['file'];
            $context['line'] = $filePath['line'];
        }

        $exceptionClass = $this->extractExceptionClass($message);
        if ($exceptionClass) {
            $context['exception'] = $exceptionClass;
        }

        return $context;
    }

    private function extractFilePath(string $message): ?array
    {
        if (preg_match('/in ([\/\w\-\.]+):(\d+)/', $message, $matches)) {
            return [
                'file' => $matches[1],
                'line' => (int) $matches[2],
            ];
        }

        return null;
    }

    private function extractExceptionClass(string $message): ?string
    {
        if (preg_match('/([A-Z][a-zA-Z0-9_\\\\]*Exception)/', $message, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function cleanMessage(string $message): string
    {
        $message = preg_replace('/\{[^}]+\}$/', '', $message);
        $message = preg_replace('/in [\/\w\-\.]+:\d+/', '', $message);
        
        return trim($message);
    }

    private function createLogEntry(array $entryData, array $stackTrace): array
    {
        $filePath = $entryData['context']['file'] ?? null;
        $lineNumber = $entryData['context']['line'] ?? null;
        $exceptionClass = $entryData['context']['exception'] ?? null;

        $hash = LogEntry::generateHash(
            $entryData['message'],
            $exceptionClass,
            $filePath
        );

        return [
            'level' => $entryData['level'],
            'message' => $entryData['message'],
            'context' => $entryData['context'],
            'channel' => $entryData['channel'],
            'file_path' => $filePath,
            'line_number' => $lineNumber,
            'exception_class' => $exceptionClass,
            'stack_trace' => !empty($stackTrace) ? implode("\n", $stackTrace) : null,
            'hash' => $hash,
            'logged_at' => $entryData['logged_at'],
        ];
    }

    public function getLogFiles(array $paths): Collection
    {
        $logFiles = collect();

        foreach ($paths as $path) {
            if (File::isFile($path)) {
                $logFiles->push($path);
            } elseif (File::isDirectory($path)) {
                $files = File::glob($path . '/*.log');
                $logFiles = $logFiles->merge($files);
            }
        }

        return $logFiles->filter(function ($file) {
            return File::exists($file) && File::isReadable($file);
        });
    }

    public function getLogFileStats(string $filePath): array
    {
        if (!File::exists($filePath)) {
            return [];
        }

        $size = File::size($filePath);
        $lastModified = File::lastModified($filePath);
        $lineCount = count(file($filePath));

        return [
            'path' => $filePath,
            'size' => $size,
            'size_human' => $this->formatBytes($size),
            'last_modified' => Carbon::createFromTimestamp($lastModified),
            'line_count' => $lineCount,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}