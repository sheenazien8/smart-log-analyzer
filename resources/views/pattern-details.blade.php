@extends('smart-log-analyzer::layout')

@section('title', 'Pattern Details - Smart Log Analyzer')

@section('content')
<div class="flex justify-between items-center pt-3 pb-2 mb-6 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-3xl font-bold dark:text-gray-900 text-white">Pattern Details</h1>
    <div class="flex space-x-2">
        <a href="{{ route('smart-log-analyzer.patterns') }}" 
           class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to Patterns
        </a>
        <button type="button" 
                onclick="window.location.reload()"
                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <i class="fas fa-sync-alt mr-2"></i> Refresh
        </button>
    </div>
</div>

<!-- Pattern Overview -->
<div class="mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Pattern Overview</h2>
            <div>
                @if($pattern->is_resolved)
                    <form method="POST" action="{{ route('smart-log-analyzer.pattern-unresolve', $pattern->id) }}" class="inline">
                        @csrf
                        <button type="submit" 
                                onclick="return confirm('Mark as unresolved?')"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 transition-colors">
                            <i class="fas fa-undo mr-2"></i> Mark Unresolved
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('smart-log-analyzer.pattern-resolve', $pattern->id) }}" class="inline">
                        @csrf
                        <button type="submit" 
                                onclick="return confirm('Mark as resolved?')"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition-colors">
                            <i class="fas fa-check mr-2"></i> Mark Resolved
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div class="flex justify-between py-2">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Error Type:</span>
                        <span class="text-gray-900 dark:text-white">{{ $pattern->error_type }}</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Severity:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $pattern->severity === 'critical' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                               ($pattern->severity === 'high' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                               ($pattern->severity === 'medium' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                               'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200')) }}">
                            {{ ucfirst($pattern->severity) }}
                        </span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Status:</span>
                        @if($pattern->is_resolved)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                Resolved
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                Unresolved
                            </span>
                        @endif
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Occurrences:</span>
                        <span class="text-gray-900 dark:text-white">{{ number_format($pattern->occurrence_count) }}</span>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between py-2">
                        <span class="font-medium text-gray-700 dark:text-gray-300">First Seen:</span>
                        <span class="text-gray-900 dark:text-white">{{ $pattern->first_seen->format('Y-m-d H:i:s') }}</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Last Seen:</span>
                        <span class="text-gray-900 dark:text-white">{{ $pattern->last_seen->format('Y-m-d H:i:s') }}</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Frequency:</span>
                        <span class="text-gray-900 dark:text-white">{{ number_format($pattern->frequency_rate, 2) }} per hour</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Trend:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $pattern->trend === 'increasing' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                               ($pattern->trend === 'decreasing' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                               'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200') }}">
                            {{ ucfirst($pattern->trend) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">Pattern Signature:</h3>
                <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                    <code class="text-sm text-gray-800 dark:text-gray-200">{{ $pattern->pattern_signature }}</code>
                </div>
            </div>

            @if($pattern->suggested_solution)
            <div class="mt-6">
                <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">Suggested Solution:</h3>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <p class="text-blue-800 dark:text-blue-200">{{ $pattern->suggested_solution }}</p>
                </div>
            </div>
            @endif

            @if($pattern->sample_context && count($pattern->sample_context) > 0)
            <div class="mt-6">
                <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">Sample Context:</h3>
                <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto">
                    <pre class="text-sm text-gray-800 dark:text-gray-200"><code>{{ json_encode($pattern->sample_context, JSON_PRETTY_PRINT) }}</code></pre>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Timeline Chart -->
@if(isset($timeline) && count($timeline) > 0)
<div class="mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Occurrence Timeline</h2>
        </div>
        <div class="p-6">
            <div class="h-64">
                <canvas id="timelineChart" class="w-full h-full"></canvas>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Recent Log Entries -->
<div class="mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                Recent Log Entries ({{ $recentEntries->total() }} total)
            </h2>
        </div>
        <div class="p-6">
            @if($recentEntries->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Timestamp
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Level
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Channel
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Message
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    File
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($recentEntries as $entry)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $entry->logged_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $entry->level === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                           ($entry->level === 'warning' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                           'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200') }}">
                                        {{ $entry->level }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $entry->channel ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white max-w-md truncate">
                                    {{ Str::limit($entry->message, 100) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($entry->file_path)
                                        {{ basename($entry->file_path) }}
                                        @if($entry->line_number)
                                            :{{ $entry->line_number }}
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex justify-center mt-6">
                    {{ $recentEntries->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-list text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">No recent entries found</h3>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Similar Patterns -->
@if(isset($similarPatterns) && $similarPatterns->count() > 0)
<div class="mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Similar Patterns</h2>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Error Type
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Severity
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Occurrences
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Last Seen
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($similarPatterns as $similarPattern)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('smart-log-analyzer.pattern-details', $similarPattern->id) }}" 
                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                    {{ $similarPattern->error_type }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $similarPattern->severity === 'critical' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                       ($similarPattern->severity === 'high' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                       'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200') }}">
                                    {{ ucfirst($similarPattern->severity) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ number_format($similarPattern->occurrence_count) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $similarPattern->last_seen->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('smart-log-analyzer.pattern-details', $similarPattern->id) }}" 
                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
@if(isset($timeline) && count($timeline) > 0)
<script>
// Timeline Chart
const timelineCtx = document.getElementById('timelineChart').getContext('2d');
const timelineChart = new Chart(timelineCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode(array_keys($timeline)) !!},
        datasets: [{
            label: 'Occurrences',
            data: {!! json_encode(array_values($timeline)) !!},
            borderColor: 'rgb(220, 53, 69)',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>
@endif
@endpush
