<?php

namespace SmartLogAnalyzer\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use SmartLogAnalyzer\Models\LogEntry;
use SmartLogAnalyzer\Models\ErrorPattern;
use SmartLogAnalyzer\Models\AnomalyDetection;
use SmartLogAnalyzer\Services\PatternAnalyzer;
use SmartLogAnalyzer\Services\AnomalyDetector;
use SmartLogAnalyzer\Services\DatabaseService;
use Carbon\Carbon;

class ApiController extends Controller
{
    private PatternAnalyzer $patternAnalyzer;
    private AnomalyDetector $anomalyDetector;

    public function __construct(PatternAnalyzer $patternAnalyzer, AnomalyDetector $anomalyDetector)
    {
        $this->patternAnalyzer = $patternAnalyzer;
        $this->anomalyDetector = $anomalyDetector;
    }

    public function stats(Request $request): JsonResponse
    {
        $timeRange = $request->get('range', '24h');
        $startTime = $this->getStartTimeFromRange($timeRange);

        $stats = [
            'total_logs' => LogEntry::where('logged_at', '>=', $startTime)->count(),
            'total_errors' => LogEntry::errors()->where('logged_at', '>=', $startTime)->count(),
            'total_warnings' => LogEntry::warnings()->where('logged_at', '>=', $startTime)->count(),
            'unique_patterns' => ErrorPattern::where('last_seen', '>=', $startTime)->count(),
            'unresolved_patterns' => ErrorPattern::unresolved()->where('last_seen', '>=', $startTime)->count(),
            'active_anomalies' => AnomalyDetection::active()->where('detection_time', '>=', $startTime)->count(),
            'critical_patterns' => ErrorPattern::bySeverity('critical')->where('last_seen', '>=', $startTime)->count(),
            'new_patterns' => ErrorPattern::where('first_seen', '>=', $startTime)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'time_range' => $timeRange,
            'generated_at' => now()->toISOString(),
        ]);
    }

    public function patterns(Request $request): JsonResponse
    {
        $query = ErrorPattern::query();

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

        $perPage = min($request->get('per_page', 25), 100);
        $patterns = $query->orderBy('last_seen', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $patterns->items(),
            'pagination' => [
                'current_page' => $patterns->currentPage(),
                'last_page' => $patterns->lastPage(),
                'per_page' => $patterns->perPage(),
                'total' => $patterns->total(),
            ],
        ]);
    }

    public function patternDetails(Request $request, int $id): JsonResponse
    {
        $pattern = ErrorPattern::with(['logEntries' => function ($query) {
            $query->orderBy('logged_at', 'desc')->limit(50);
        }])->find($id);

        if (!$pattern) {
            return response()->json([
                'success' => false,
                'message' => 'Pattern not found',
            ], 404);
        }

        $timeRange = $request->get('range', '7d');
        $startTime = $this->getStartTimeFromRange($timeRange);
        
        $dateExpression = DatabaseService::getHourlyDateFormat('logged_at');
        
        $timeline = $pattern->logEntries()
            ->selectRaw("{$dateExpression} as hour, COUNT(*) as count")
            ->where('logged_at', '>=', $startTime)
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'pattern' => $pattern,
                'timeline' => $timeline,
                'recent_entries' => $pattern->logEntries,
            ],
        ]);
    }

    public function anomalies(Request $request): JsonResponse
    {
        $query = AnomalyDetection::query();

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

        $perPage = min($request->get('per_page', 25), 100);
        $anomalies = $query->orderBy('detection_time', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $anomalies->items(),
            'pagination' => [
                'current_page' => $anomalies->currentPage(),
                'last_page' => $anomalies->lastPage(),
                'per_page' => $anomalies->perPage(),
                'total' => $anomalies->total(),
            ],
        ]);
    }

    public function logs(Request $request): JsonResponse
    {
        $query = LogEntry::query();

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

        $perPage = min($request->get('per_page', 50), 200);
        $logs = $query->orderBy('logged_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
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