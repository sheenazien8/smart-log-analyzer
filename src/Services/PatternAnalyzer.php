<?php

namespace SmartLogAnalyzer\Services;

use SmartLogAnalyzer\Models\LogEntry;
use SmartLogAnalyzer\Models\ErrorPattern;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PatternAnalyzer
{
    private float $similarityThreshold;
    private int $minOccurrences;
    private int $timeWindow;

    public function __construct()
    {
        $this->similarityThreshold = config('smart-log-analyzer.pattern_recognition.similarity_threshold', 0.8);
        $this->minOccurrences = config('smart-log-analyzer.pattern_recognition.min_occurrences', 3);
        $this->timeWindow = config('smart-log-analyzer.pattern_recognition.time_window', 3600);
    }

    public function analyzeLogEntry(array $logEntryData): ?ErrorPattern
    {
        $hash = $logEntryData['hash'];
        
        $existingPattern = ErrorPattern::where('pattern_hash', $hash)->first();
        
        if ($existingPattern) {
            $existingPattern->incrementOccurrence();
            return $existingPattern;
        }

        $similarPattern = $this->findSimilarPattern($logEntryData);
        
        if ($similarPattern) {
            $this->mergeWithPattern($similarPattern, $logEntryData);
            return $similarPattern;
        }

        return $this->createNewPattern($logEntryData);
    }

    public function findSimilarPattern(array $logEntryData): ?ErrorPattern
    {
        $cacheKey = "similar_patterns_{$logEntryData['level']}_{$logEntryData['exception_class']}";
        
        $recentPatterns = Cache::remember($cacheKey, 300, function () use ($logEntryData) {
            return ErrorPattern::where('severity', $this->mapLevelToSeverity($logEntryData['level']))
                ->where('last_seen', '>=', now()->subSeconds($this->timeWindow))
                ->when($logEntryData['exception_class'], function ($query, $exceptionClass) {
                    return $query->where('error_type', $exceptionClass);
                })
                ->get();
        });

        foreach ($recentPatterns as $pattern) {
            $similarity = $this->calculateSimilarity($logEntryData['message'], $pattern->pattern_signature);
            
            if ($similarity >= $this->similarityThreshold) {
                return $pattern;
            }
        }

        return null;
    }

    public function calculateSimilarity(string $message1, string $message2): float
    {
        $normalizedMessage1 = $this->normalizeMessage($message1);
        $normalizedMessage2 = $this->normalizeMessage($message2);

        $levenshteinSimilarity = $this->levenshteinSimilarity($normalizedMessage1, $normalizedMessage2);
        $cosineSimilarity = $this->cosineSimilarity($normalizedMessage1, $normalizedMessage2);
        $jaccardSimilarity = $this->jaccardSimilarity($normalizedMessage1, $normalizedMessage2);

        return ($levenshteinSimilarity + $cosineSimilarity + $jaccardSimilarity) / 3;
    }

    public function groupSimilarPatterns(): Collection
    {
        $patterns = ErrorPattern::unresolved()
            ->where('occurrence_count', '>=', $this->minOccurrences)
            ->orderBy('last_seen', 'desc')
            ->get();

        $groups = collect();
        $processed = collect();

        foreach ($patterns as $pattern) {
            if ($processed->contains($pattern->id)) {
                continue;
            }

            $group = collect([$pattern]);
            $processed->push($pattern->id);

            foreach ($patterns as $otherPattern) {
                if ($processed->contains($otherPattern->id) || $pattern->id === $otherPattern->id) {
                    continue;
                }

                $similarity = $this->calculateSimilarity($pattern->pattern_signature, $otherPattern->pattern_signature);
                
                if ($similarity >= $this->similarityThreshold) {
                    $group->push($otherPattern);
                    $processed->push($otherPattern->id);
                }
            }

            if ($group->count() > 1) {
                $groups->push([
                    'primary_pattern' => $pattern,
                    'similar_patterns' => $group->slice(1),
                    'total_occurrences' => $group->sum('occurrence_count'),
                    'average_similarity' => $this->calculateGroupSimilarity($group),
                ]);
            }
        }

        return $groups;
    }

    public function identifyTrends(): array
    {
        $trends = [
            'increasing' => [],
            'decreasing' => [],
            'new' => [],
            'resolved' => [],
        ];

        $patterns = ErrorPattern::where('last_seen', '>=', now()->subDays(7))->get();

        foreach ($patterns as $pattern) {
            $trend = $this->calculatePatternTrend($pattern);
            
            if (isset($trends[$trend])) {
                $trends[$trend][] = $pattern;
            }
        }

        return $trends;
    }

    public function detectAnomalousPatterns(): Collection
    {
        $anomalousPatterns = collect();
        $patterns = ErrorPattern::where('last_seen', '>=', now()->subHours(24))->get();

        foreach ($patterns as $pattern) {
            $recentRate = $this->calculateRecentErrorRate($pattern);
            $historicalRate = $this->calculateHistoricalErrorRate($pattern);

            if ($historicalRate > 0 && $recentRate / $historicalRate > 3.0) {
                $anomalousPatterns->push([
                    'pattern' => $pattern,
                    'recent_rate' => $recentRate,
                    'historical_rate' => $historicalRate,
                    'anomaly_score' => $recentRate / $historicalRate,
                ]);
            }
        }

        return $anomalousPatterns->sortByDesc('anomaly_score');
    }

    private function normalizeMessage(string $message): string
    {
        $message = strtolower($message);
        $message = preg_replace('/\d+/', 'NUMBER', $message);
        $message = preg_replace('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/', 'UUID', $message);
        $message = preg_replace('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', 'IP', $message);
        $message = preg_replace('/[^\w\s]/', ' ', $message);
        $message = preg_replace('/\s+/', ' ', $message);
        
        return trim($message);
    }

    private function levenshteinSimilarity(string $str1, string $str2): float
    {
        $maxLength = max(strlen($str1), strlen($str2));
        
        if ($maxLength === 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);
        return 1 - ($distance / $maxLength);
    }

    private function cosineSimilarity(string $str1, string $str2): float
    {
        $words1 = array_count_values(explode(' ', $str1));
        $words2 = array_count_values(explode(' ', $str2));

        $allWords = array_unique(array_merge(array_keys($words1), array_keys($words2)));

        $vector1 = [];
        $vector2 = [];

        foreach ($allWords as $word) {
            $vector1[] = $words1[$word] ?? 0;
            $vector2[] = $words2[$word] ?? 0;
        }

        $dotProduct = array_sum(array_map(function ($a, $b) {
            return $a * $b;
        }, $vector1, $vector2));

        $magnitude1 = sqrt(array_sum(array_map(function ($a) {
            return $a * $a;
        }, $vector1)));

        $magnitude2 = sqrt(array_sum(array_map(function ($a) {
            return $a * $a;
        }, $vector2)));

        if ($magnitude1 === 0.0 || $magnitude2 === 0.0) {
            return 0.0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    private function jaccardSimilarity(string $str1, string $str2): float
    {
        $words1 = array_unique(explode(' ', $str1));
        $words2 = array_unique(explode(' ', $str2));

        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));

        if (count($union) === 0) {
            return 1.0;
        }

        return count($intersection) / count($union);
    }

    private function createNewPattern(array $logEntryData): ErrorPattern
    {
        return ErrorPattern::create([
            'pattern_hash' => $logEntryData['hash'],
            'pattern_signature' => $logEntryData['message'],
            'error_type' => $logEntryData['exception_class'] ?? 'Unknown',
            'severity' => $this->mapLevelToSeverity($logEntryData['level']),
            'occurrence_count' => 1,
            'first_seen' => $logEntryData['logged_at'],
            'last_seen' => $logEntryData['logged_at'],
            'sample_context' => $logEntryData['context'] ?? [],
        ]);
    }

    private function mergeWithPattern(ErrorPattern $pattern, array $logEntryData): void
    {
        $pattern->incrementOccurrence();
        
        if (empty($pattern->sample_context) && !empty($logEntryData['context'])) {
            $pattern->update(['sample_context' => $logEntryData['context']]);
        }
    }

    private function mapLevelToSeverity(string $level): string
    {
        $severityMap = [
            'emergency' => 'critical',
            'alert' => 'critical',
            'critical' => 'critical',
            'error' => 'high',
            'warning' => 'medium',
            'notice' => 'low',
            'info' => 'low',
            'debug' => 'low',
        ];

        return $severityMap[$level] ?? 'low';
    }

    private function calculatePatternTrend(ErrorPattern $pattern): string
    {
        if ($pattern->first_seen->gte(now()->subDays(1))) {
            return 'new';
        }

        if ($pattern->is_resolved) {
            return 'resolved';
        }

        $recentCount = $pattern->logEntries()
            ->where('logged_at', '>=', now()->subDays(1))
            ->count();

        $previousCount = $pattern->logEntries()
            ->whereBetween('logged_at', [now()->subDays(2), now()->subDays(1)])
            ->count();

        if ($previousCount === 0) {
            return $recentCount > 0 ? 'increasing' : 'stable';
        }

        $changeRatio = $recentCount / $previousCount;

        if ($changeRatio > 1.5) {
            return 'increasing';
        } elseif ($changeRatio < 0.5) {
            return 'decreasing';
        }

        return 'stable';
    }

    private function calculateRecentErrorRate(ErrorPattern $pattern): float
    {
        $recentCount = $pattern->logEntries()
            ->where('logged_at', '>=', now()->subHours(1))
            ->count();

        return $recentCount / 1.0; // per hour
    }

    private function calculateHistoricalErrorRate(ErrorPattern $pattern): float
    {
        $historicalCount = $pattern->logEntries()
            ->whereBetween('logged_at', [now()->subDays(7), now()->subHours(1)])
            ->count();

        $hours = now()->subDays(7)->diffInHours(now()->subHours(1));
        
        return $hours > 0 ? $historicalCount / $hours : 0.0;
    }

    private function calculateGroupSimilarity(Collection $group): float
    {
        if ($group->count() < 2) {
            return 1.0;
        }

        $similarities = [];
        $patterns = $group->toArray();

        for ($i = 0; $i < count($patterns); $i++) {
            for ($j = $i + 1; $j < count($patterns); $j++) {
                $similarities[] = $this->calculateSimilarity(
                    $patterns[$i]['pattern_signature'],
                    $patterns[$j]['pattern_signature']
                );
            }
        }

        return array_sum($similarities) / count($similarities);
    }
}