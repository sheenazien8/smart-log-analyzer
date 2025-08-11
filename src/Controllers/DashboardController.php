<?php

namespace SmartLogAnalyzer\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SmartLogAnalyzer\Models\LogEntry;
use SmartLogAnalyzer\Models\ErrorPattern;
use SmartLogAnalyzer\Models\AnomalyDetection;
use SmartLogAnalyzer\Services\PatternAnalyzer;
use SmartLogAnalyzer\Services\AnomalyDetector;
use SmartLogAnalyzer\Services\DatabaseService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private PatternAnalyzer $patternAnalyzer;
    private AnomalyDetector $anomalyDetector;

    public function __construct(PatternAnalyzer $patternAnalyzer, AnomalyDetector $anomalyDetector)
    {
        $this->patternAnalyzer = $patternAnalyzer;
        $this->anomalyDetector = $anomalyDetector;
    }

    public function index(Request $request)
    {
        $timeRange = $request->get('range', '24h');
        $startTime = $this->getStartTimeFromRange($timeRange);

        $stats = $this->getDashboardStats($startTime);
        $charts = $this->getChartData($startTime);
        $recentPatterns = $this->getRecentErrorPatterns();
        $anomalies = $this->getRecentAnomalies();

        return view('smart-log-analyzer::dashboard', compact(
            'stats',
            'charts',
            'recentPatterns',
            'anomalies',
            'timeRange'
        ));
    }

    public function patterns(Request $request)
    {
        $query = ErrorPattern::with('logEntries')
            ->orderBy('last_seen', 'desc');

        if ($request->filled('severity')) {
            $query->where('severity', $request->get('severity'));
        }

        if ($request->filled('status')) {
            $status = $request->get('status');
            if ($status === 'resolved') {
                $query->where('is_resolved', true);
            } elseif ($status === 'unresolved') {
                $query->where('is_resolved', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('pattern_signature', 'like', "%{$search}%")
                  ->orWhere('error_type', 'like', "%{$search}%");
            });
        }

        $patterns = $query->paginate(25);
        $groupedPatterns = $this->patternAnalyzer->groupSimilarPatterns();

        return view('smart-log-analyzer::patterns', compact('patterns', 'groupedPatterns'));
    }

    public function patternDetails(Request $request, int $id)
    {
        $pattern = ErrorPattern::with('logEntries')->findOrFail($id);
        
        $timeRange = $request->get('range', '7d');
        $startTime = $this->getStartTimeFromRange($timeRange);
        
        $recentEntries = $pattern->logEntries()
            ->where('logged_at', '>=', $startTime)
            ->orderBy('logged_at', 'desc')
            ->paginate(50);

        $timeline = $this->getPatternTimeline($pattern, $startTime);
        $similarPatterns = $this->findSimilarPatterns($pattern);

        return view('smart-log-analyzer::pattern-details', compact(
            'pattern',
            'recentEntries',
            'timeline',
            'similarPatterns',
            'timeRange'
        ));
    }

    public function anomalies(Request $request)
    {
        $query = AnomalyDetection::orderBy('detection_time', 'desc');

        if ($request->filled('type')) {
            $query->where('anomaly_type', $request->get('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('severity')) {
            $severity = $request->get('severity');
            $query->where(function ($q) use ($severity) {
                switch ($severity) {
                    case 'critical':
                        $q->where('deviation_score', '>=', 5.0);
                        break;
                    case 'high':
                        $q->where('deviation_score', '>=', 3.0)->where('deviation_score', '<', 5.0);
                        break;
                    case 'medium':
                        $q->where('deviation_score', '>=', 2.0)->where('deviation_score', '<', 3.0);
                        break;
                    case 'low':
                        $q->where('deviation_score', '<', 2.0);
                        break;
                }
            });
        }

        $anomalies = $query->paginate(25);
        $stats = $this->anomalyDetector->getAnomalyStatistics();

        return view('smart-log-analyzer::anomalies', compact('anomalies', 'stats'));
    }

    public function logs(Request $request)
    {
        $query = LogEntry::orderBy('logged_at', 'desc');

        if ($request->filled('level')) {
            $query->where('level', $request->get('level'));
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->get('channel'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('exception_class', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->where('logged_at', '>=', Carbon::parse($request->get('date_from')));
        }

        if ($request->filled('date_to')) {
            $query->where('logged_at', '<=', Carbon::parse($request->get('date_to')));
        }

        $logs = $query->paginate(50);
        $levels = LogEntry::distinct()->pluck('level');
        $channels = LogEntry::distinct()->pluck('channel');

        return view('smart-log-analyzer::logs', compact('logs', 'levels', 'channels'));
    }

    public function resolvePattern(Request $request, int $id)
    {
        $pattern = ErrorPattern::findOrFail($id);
        $pattern->markAsResolved();

        return redirect()->back()->with('success', 'Pattern marked as resolved.');
    }

    public function unresolvePattern(Request $request, int $id)
    {
        $pattern = ErrorPattern::findOrFail($id);
        $pattern->markAsUnresolved();

        return redirect()->back()->with('success', 'Pattern marked as unresolved.');
    }

    public function resolveAnomaly(Request $request, int $id)
    {
        $anomaly = AnomalyDetection::findOrFail($id);
        $anomaly->markAsResolved();

        return redirect()->back()->with('success', 'Anomaly marked as resolved.');
    }

    public function ignoreAnomaly(Request $request, int $id)
    {
        $anomaly = AnomalyDetection::findOrFail($id);
        $anomaly->markAsIgnored();

        return redirect()->back()->with('success', 'Anomaly ignored.');
    }

    private function getDashboardStats(Carbon $startTime): array
    {
        return [
            'total_logs' => LogEntry::where('logged_at', '>=', $startTime)->count(),
            'total_errors' => LogEntry::errors()->where('logged_at', '>=', $startTime)->count(),
            'total_warnings' => LogEntry::warnings()->where('logged_at', '>=', $startTime)->count(),
            'unique_patterns' => ErrorPattern::where('last_seen', '>=', $startTime)->count(),
            'unresolved_patterns' => ErrorPattern::unresolved()->where('last_seen', '>=', $startTime)->count(),
            'active_anomalies' => AnomalyDetection::active()->where('detection_time', '>=', $startTime)->count(),
            'critical_patterns' => ErrorPattern::bySeverity('critical')->where('last_seen', '>=', $startTime)->count(),
            'new_patterns' => ErrorPattern::where('first_seen', '>=', $startTime)->count(),
        ];
    }

    private function getChartData(Carbon $startTime): array
    {
        $hours = $startTime->diffInHours(now());
        $interval = $hours > 48 ? 'hour' : ($hours > 7 * 24 ? 'day' : 'hour');

        return [
            'error_timeline' => $this->getErrorTimeline($startTime, $interval),
            'severity_distribution' => $this->getSeverityDistribution($startTime),
            'top_error_types' => $this->getTopErrorTypes($startTime),
            'pattern_trends' => $this->getPatternTrends($startTime),
        ];
    }

    private function getErrorTimeline(Carbon $startTime, string $interval): array
    {
        $dateExpression = $interval === 'hour' 
            ? DatabaseService::getHourlyDateFormat('logged_at')
            : DatabaseService::getDailyDateFormat('logged_at');
        
        $timeline = LogEntry::selectRaw("
                {$dateExpression} as period,
                level,
                COUNT(*) as count
            ")
            ->where('logged_at', '>=', $startTime)
            ->groupBy('period', 'level')
            ->orderBy('period')
            ->get()
            ->groupBy('period');

        return $timeline->map(function ($entries) {
            return $entries->pluck('count', 'level')->toArray();
        })->toArray();
    }

    private function getSeverityDistribution(Carbon $startTime): array
    {
        return ErrorPattern::selectRaw('severity, COUNT(*) as count')
            ->where('last_seen', '>=', $startTime)
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();
    }

    private function getTopErrorTypes(Carbon $startTime): array
    {
        return ErrorPattern::selectRaw('error_type, SUM(occurrence_count) as total_count')
            ->where('last_seen', '>=', $startTime)
            ->groupBy('error_type')
            ->orderBy('total_count', 'desc')
            ->limit(10)
            ->pluck('total_count', 'error_type')
            ->toArray();
    }

    private function getPatternTrends(Carbon $startTime): array
    {
        return $this->patternAnalyzer->identifyTrends();
    }

    private function getRecentErrorPatterns(): \Illuminate\Database\Eloquent\Collection
    {
        return ErrorPattern::unresolved()
            ->where('last_seen', '>=', now()->subHours(24))
            ->orderBy('occurrence_count', 'desc')
            ->limit(10)
            ->get();
    }

    private function getRecentAnomalies(): \Illuminate\Database\Eloquent\Collection
    {
        return AnomalyDetection::active()
            ->where('detection_time', '>=', now()->subHours(24))
            ->orderBy('deviation_score', 'desc')
            ->limit(10)
            ->get();
    }

    private function getPatternTimeline(ErrorPattern $pattern, Carbon $startTime): array
    {
        $dateExpression = DatabaseService::getHourlyDateFormat('logged_at');
        
        return $pattern->logEntries()
            ->selectRaw("{$dateExpression} as hour, COUNT(*) as count")
            ->where('logged_at', '>=', $startTime)
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    }

    private function findSimilarPatterns(ErrorPattern $pattern): \Illuminate\Database\Eloquent\Collection
    {
        return ErrorPattern::where('id', '!=', $pattern->id)
            ->where('error_type', $pattern->error_type)
            ->where('severity', $pattern->severity)
            ->orderBy('occurrence_count', 'desc')
            ->limit(5)
            ->get();
    }

    private function getStartTimeFromRange(string $range): Carbon
    {
        return match ($range) {
            '1h' => now()->subHour(),
            '6h' => now()->subHours(6),
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subDay(),
        };
    }
}
