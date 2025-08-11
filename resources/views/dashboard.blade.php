@extends('smart-log-analyzer::layout')

@section('title', 'Dashboard - Smart Log Analyzer')

@section('content')
<div class="flex justify-between items-center py-3 mb-6 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
    <div class="flex space-x-2">
        <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
            <i class="fas fa-sync-alt mr-2"></i> Refresh
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border-l-4 border-primary">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-list text-2xl text-gray-400 dark:text-gray-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-xs font-medium text-primary uppercase tracking-wide">Total Logs</dt>
                        <dd class="text-lg font-bold text-gray-900 dark:text-white" data-stat="total_logs">{{ number_format($stats['total_logs']) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border-l-4 border-red-500">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-2xl text-gray-400 dark:text-gray-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wide">Total Errors</dt>
                        <dd class="text-lg font-bold text-gray-900 dark:text-white" data-stat="total_errors">{{ number_format($stats['total_errors']) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border-l-4 border-yellow-500">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-project-diagram text-2xl text-gray-400 dark:text-gray-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-xs font-medium text-yellow-600 dark:text-yellow-400 uppercase tracking-wide">Unique Patterns</dt>
                        <dd class="text-lg font-bold text-gray-900 dark:text-white" data-stat="unique_patterns">{{ number_format($stats['unique_patterns']) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border-l-4 border-blue-500">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-2xl text-gray-400 dark:text-gray-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">Active Anomalies</dt>
                        <dd class="text-lg font-bold text-gray-900 dark:text-white" data-stat="active_anomalies">{{ number_format($stats['active_anomalies']) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Error Timeline</h3>
                <div class="mt-2">
                    <canvas id="errorTimelineChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Severity Distribution</h3>
                <div class="mt-2">
                    <canvas id="severityChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Patterns and Anomalies -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Recent Error Patterns</h3>
            @if($recentPatterns->count() > 0)
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Error Type</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Severity</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Count</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Seen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($recentPatterns as $pattern)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-3 py-2 whitespace-nowrap text-sm">
                                    <a href="{{ route('smart-log-analyzer.pattern-details', $pattern->id) }}" class="text-primary hover:text-primary-dark">
                                        {{ $pattern->error_type }}
                                    </a>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $pattern->severity === 'critical' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ($pattern->severity === 'high' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200') }}">
                                        {{ ucfirst($pattern->severity) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $pattern->occurrence_count }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $pattern->last_seen->diffForHumans() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">No recent error patterns found.</p>
            @endif
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Recent Anomalies</h3>
            @if($anomalies->count() > 0)
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Metric</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Score</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Detected</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($anomalies as $anomaly)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $anomaly->anomaly_type)) }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $anomaly->metric }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $anomaly->severity === 'critical' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ($anomaly->severity === 'high' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200') }}">
                                        {{ number_format($anomaly->deviation_score, 1) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $anomaly->detection_time->diffForHumans() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">No recent anomalies detected.</p>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Error Timeline Chart
const timelineCtx = document.getElementById('errorTimelineChart').getContext('2d');
const timelineChart = new Chart(timelineCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode(array_keys($charts['error_timeline'] ?? [])) !!},
        datasets: [
            {
                label: 'Errors',
                data: {!! json_encode(array_column($charts['error_timeline'] ?? [], 'error')) !!},
                borderColor: 'rgb(220, 53, 69)',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.1
            },
            {
                label: 'Warnings',
                data: {!! json_encode(array_column($charts['error_timeline'] ?? [], 'warning')) !!},
                borderColor: 'rgb(255, 193, 7)',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                tension: 0.1
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Severity Distribution Chart
const severityCtx = document.getElementById('severityChart').getContext('2d');
const severityChart = new Chart(severityCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode(array_keys($charts['severity_distribution'] ?? [])) !!},
        datasets: [{
            data: {!! json_encode(array_values($charts['severity_distribution'] ?? [])) !!},
            backgroundColor: [
                'rgb(220, 53, 69)',
                'rgb(255, 193, 7)',
                'rgb(13, 202, 240)',
                'rgb(25, 135, 84)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>
@endpush
