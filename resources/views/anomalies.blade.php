@extends('smart-log-analyzer::layout')

@section('title', 'Anomalies - Smart Log Analyzer')

@section('content')
<div class="flex justify-between items-center py-3 mb-6 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Anomalies</h1>
    <div class="flex space-x-2">
        <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors" onclick="window.location.reload()">
            <i class="fas fa-sync-alt mr-2"></i> Refresh
        </button>
    </div>
</div>

<!-- Statistics Cards -->
@if(isset($stats))
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border-l-4 border-red-500">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-2xl text-gray-400 dark:text-gray-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wide">Total Anomalies</dt>
                        <dd class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_anomalies']) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border-l-4 border-yellow-500">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-clock text-2xl text-gray-400 dark:text-gray-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-xs font-medium text-yellow-600 dark:text-yellow-400 uppercase tracking-wide">Active</dt>
                        <dd class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($stats['active_anomalies']) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border-l-4 border-blue-500">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-calendar-day text-2xl text-gray-400 dark:text-gray-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">Recent (24h)</dt>
                        <dd class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($stats['recent_anomalies']) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border-l-4 border-primary">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-2xl text-gray-400 dark:text-gray-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-xs font-medium text-primary uppercase tracking-wide">Resolved</dt>
                        <dd class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($stats['resolved_anomalies']) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Filters -->
<div class="mb-6">
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                    <select name="type" id="type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                        <option value="">All Types</option>
                        <option value="spike" {{ request('type') === 'spike' ? 'selected' : '' }}>Spike</option>
                        <option value="drop" {{ request('type') === 'drop' ? 'selected' : '' }}>Drop</option>
                        <option value="volume_spike" {{ request('type') === 'volume_spike' ? 'selected' : '' }}>Volume Spike</option>
                        <option value="volume_drop" {{ request('type') === 'volume_drop' ? 'selected' : '' }}>Volume Drop</option>
                        <option value="pattern_spike" {{ request('type') === 'pattern_spike' ? 'selected' : '' }}>Pattern Spike</option>
                        <option value="new_critical_pattern" {{ request('type') === 'new_critical_pattern' ? 'selected' : '' }}>New Critical Pattern</option>
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select name="status" id="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="ignored" {{ request('status') === 'ignored' ? 'selected' : '' }}>Ignored</option>
                    </select>
                </div>
                <div>
                    <label for="severity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Severity</label>
                    <select name="severity" id="severity" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                        <option value="">All Severities</option>
                        <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical</option>
                        <option value="high" {{ request('severity') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="medium" {{ request('severity') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="low" {{ request('severity') === 'low' ? 'selected' : '' }}>Low</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Anomalies Table -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
            Detected Anomalies ({{ $anomalies->total() }} total)
        </h3>
        @if($anomalies->count() > 0)
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 dark:ring-gray-600 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Metric</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Severity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Values</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Change</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Detected</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($anomalies as $anomaly)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ ucfirst(str_replace('_', ' ', $anomaly->anomaly_type)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $anomaly->metric }}</div>
                                @if($anomaly->duration)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Duration: {{ $anomaly->duration }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $anomaly->severity === 'critical' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ($anomaly->severity === 'high' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : ($anomaly->severity === 'medium' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300')) }}">
                                    {{ ucfirst($anomaly->severity) }}
                                </span>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Score: {{ number_format($anomaly->deviation_score, 1) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <div><strong>Baseline:</strong> {{ number_format($anomaly->baseline_value, 2) }}</div>
                                <div><strong>Detected:</strong> {{ number_format($anomaly->detected_value, 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $changePercent = $anomaly->change_percentage;
                                    $changeClass = $changePercent > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
                                    $changeIcon = $changePercent > 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                                @endphp
                                <span class="{{ $changeClass }} text-sm font-medium">
                                    <i class="fas {{ $changeIcon }}"></i>
                                    {{ abs($changePercent) }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" title="{{ $anomaly->detection_time->format('Y-m-d H:i:s') }}">
                                {{ $anomaly->detection_time->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($anomaly->status === 'active')
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Active</span>
                                @elseif($anomaly->status === 'resolved')
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Resolved</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Ignored</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                @if($anomaly->status === 'active')
                                    <div class="flex space-x-2">
                                        <form method="POST" action="{{ route('smart-log-analyzer.anomaly-resolve', $anomaly->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300" 
                                                    onclick="return confirm('Mark as resolved?')" title="Resolve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('smart-log-analyzer.anomaly-ignore', $anomaly->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300" 
                                                    onclick="return confirm('Ignore this anomaly?')" title="Ignore">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-center mt-6">
                {{ $anomalies->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No anomalies found</h3>
                <p class="text-gray-500 dark:text-gray-400">Try adjusting your filters or check if anomaly detection is enabled.</p>
            </div>
        @endif
    </div>
</div>

@if(isset($stats['anomalies_by_type']) && count($stats['anomalies_by_type']) > 0)
<!-- Anomaly Type Distribution -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Anomalies by Type</h3>
            <div class="mt-2">
                <canvas id="typeChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    @if(isset($stats['anomalies_by_severity']) && count($stats['anomalies_by_severity']) > 0)
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Anomalies by Severity</h3>
            <div class="mt-2">
                <canvas id="severityChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    @endif
</div>
@endif
@endsection

@push('scripts')
@if(isset($stats['anomalies_by_type']) && count($stats['anomalies_by_type']) > 0)
<script>
// Anomaly Type Chart
const typeCtx = document.getElementById('typeChart').getContext('2d');
const typeChart = new Chart(typeCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode(array_keys($stats['anomalies_by_type'])) !!},
        datasets: [{
            data: {!! json_encode(array_values($stats['anomalies_by_type'])) !!},
            backgroundColor: [
                'rgb(220, 53, 69)',
                'rgb(255, 193, 7)',
                'rgb(13, 202, 240)',
                'rgb(25, 135, 84)',
                'rgb(108, 117, 125)',
                'rgb(111, 66, 193)'
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

@if(isset($stats['anomalies_by_severity']) && count($stats['anomalies_by_severity']) > 0)
// Severity Chart
const severityCtx = document.getElementById('severityChart').getContext('2d');
const severityChart = new Chart(severityCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode(array_keys($stats['anomalies_by_severity'])) !!},
        datasets: [{
            data: {!! json_encode(array_values($stats['anomalies_by_severity'])) !!},
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
@endif
</script>
@endif
@endpush