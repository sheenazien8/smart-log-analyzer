@extends('smart-log-analyzer::layout')

@section('title', 'Raw Logs - Smart Log Analyzer')

@section('content')
<div class="flex justify-between items-center py-3 mb-6 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Raw Logs</h1>
    <div class="flex space-x-2">
        <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors" onclick="window.location.reload()">
            <i class="fas fa-sync-alt mr-2"></i> Refresh
        </button>
    </div>
</div>

<!-- Filters -->
<div class="mb-6">
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label for="level" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Level</label>
                    <select name="level" id="level" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                        <option value="">All Levels</option>
                        @if(isset($levels))
                            @foreach($levels as $level)
                                <option value="{{ $level }}" {{ request('level') === $level ? 'selected' : '' }}>
                                    {{ ucfirst($level) }}
                                </option>
                            @endforeach
                        @else
                            <option value="emergency" {{ request('level') === 'emergency' ? 'selected' : '' }}>Emergency</option>
                            <option value="alert" {{ request('level') === 'alert' ? 'selected' : '' }}>Alert</option>
                            <option value="critical" {{ request('level') === 'critical' ? 'selected' : '' }}>Critical</option>
                            <option value="error" {{ request('level') === 'error' ? 'selected' : '' }}>Error</option>
                            <option value="warning" {{ request('level') === 'warning' ? 'selected' : '' }}>Warning</option>
                            <option value="notice" {{ request('level') === 'notice' ? 'selected' : '' }}>Notice</option>
                            <option value="info" {{ request('level') === 'info' ? 'selected' : '' }}>Info</option>
                            <option value="debug" {{ request('level') === 'debug' ? 'selected' : '' }}>Debug</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label for="channel" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Channel</label>
                    <select name="channel" id="channel" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                        <option value="">All Channels</option>
                        @if(isset($channels))
                            @foreach($channels as $channel)
                                @if($channel)
                                    <option value="{{ $channel }}" {{ request('channel') === $channel ? 'selected' : '' }}>
                                        {{ ucfirst($channel) }}
                                    </option>
                                @endif
                            @endforeach
                        @endif
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Date</label>
                    <input type="datetime-local" name="date_from" id="date_from" class="mt-1 focus:ring-primary focus:border-primary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md" 
                           value="{{ request('date_from') }}">
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Date</label>
                    <input type="datetime-local" name="date_to" id="date_to" class="mt-1 focus:ring-primary focus:border-primary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md" 
                           value="{{ request('date_to') }}">
                </div>
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <input type="text" name="search" id="search" class="mt-1 focus:ring-primary focus:border-primary block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md" 
                           placeholder="Search messages..." value="{{ request('search') }}">
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

<!-- Logs Table -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
            Log Entries ({{ $logs->total() }} total)
        </h3>
        @if($logs->count() > 0)
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 dark:ring-gray-600 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-36">Timestamp</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-20">Level</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-20">Channel</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Message</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32">File</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-16">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($logs as $log)
                        <tr class="log-entry hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" data-level="{{ $log->level }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <div>{{ $log->logged_at->format('Y-m-d') }}</div>
                                <div>{{ $log->logged_at->format('H:i:s') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ 
                                    in_array($log->level, ['emergency', 'alert', 'critical', 'error']) ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                    ($log->level === 'warning' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                    ($log->level === 'info' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300')) 
                                }}">
                                    {{ strtoupper($log->level) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $log->channel ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                <div class="log-message">
                                    <span class="message-preview text-sm text-gray-900 dark:text-white">{{ Str::limit($log->message, 120) }}</span>
                                    @if(strlen($log->message) > 120)
                                        <button class="text-primary hover:text-primary-dark text-xs ml-1 toggle-message" type="button">
                                            Show more
                                        </button>
                                        <div class="message-full hidden mt-2">
                                            <pre class="text-xs bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 p-2 rounded overflow-x-auto"><code>{{ $log->message }}</code></pre>
                                        </div>
                                    @endif
                                </div>
                                
                                @if($log->exception_class)
                                    <div class="mt-1">
                                        <span class="text-xs text-red-600 dark:text-red-400">
                                            <i class="fas fa-bug"></i> {{ $log->exception_class }}
                                        </span>
                                    </div>
                                @endif

                                @if($log->context && count($log->context) > 0)
                                    <button class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-xs mt-1 toggle-context" type="button">
                                        <i class="fas fa-info-circle"></i> Context
                                    </button>
                                    <div class="context-data hidden mt-2">
                                        <div class="bg-gray-100 dark:bg-gray-900 p-2 rounded">
                                            <pre class="text-xs text-gray-800 dark:text-gray-200 overflow-x-auto"><code>{{ json_encode($log->context, JSON_PRETTY_PRINT) }}</code></pre>
                                        </div>
                                    </div>
                                @endif

                                @if($log->stack_trace)
                                    <button class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300 text-xs mt-1 toggle-stack" type="button">
                                        <i class="fas fa-list"></i> Stack Trace
                                    </button>
                                    <div class="stack-trace hidden mt-2">
                                        <div class="bg-gray-900 dark:bg-black text-green-400 dark:text-green-300 p-2 rounded">
                                            <pre class="text-xs overflow-x-auto"><code>{{ $log->stack_trace }}</code></pre>
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if($log->file_path)
                                    <div>{{ basename($log->file_path) }}</div>
                                    @if($log->line_number)
                                        <div class="text-xs">Line {{ $log->line_number }}</div>
                                    @endif
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                @if($log->hash)
                                    <button class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300" 
                                            onclick="showSimilarLogs('{{ $log->hash }}')" 
                                            title="Find similar logs">
                                        <i class="fas fa-search"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-center mt-6">
                {{ $logs->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No log entries found</h3>
                <p class="text-gray-500 dark:text-gray-400">Try adjusting your filters or check if logs are being processed.</p>
            </div>
        @endif
    </div>
</div>

<!-- Quick Stats -->
@if($logs->count() > 0)
<div class="mt-8">
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Quick Statistics</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 text-center">
                <div class="border-r border-gray-200 dark:border-gray-700 last:border-r-0">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $logs->where('level', 'error')->count() + $logs->where('level', 'critical')->count() + $logs->where('level', 'emergency')->count() + $logs->where('level', 'alert')->count() }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Errors</div>
                </div>
                <div class="border-r border-gray-200 dark:border-gray-700 last:border-r-0">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $logs->where('level', 'warning')->count() }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Warnings</div>
                </div>
                <div class="border-r border-gray-200 dark:border-gray-700 last:border-r-0">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $logs->where('level', 'info')->count() }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Info</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $logs->where('level', 'debug')->count() }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Debug</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
// Toggle message expansion
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('toggle-message') || e.target.parentElement.classList.contains('toggle-message')) {
        const button = e.target.classList.contains('toggle-message') ? e.target : e.target.parentElement;
        const preview = button.parentElement.querySelector('.message-preview');
        const full = button.parentElement.querySelector('.message-full');
        
        if (full.classList.contains('hidden')) {
            preview.classList.add('hidden');
            full.classList.remove('hidden');
            button.innerHTML = 'Show less';
        } else {
            preview.classList.remove('hidden');
            full.classList.add('hidden');
            button.innerHTML = 'Show more';
        }
    }
});

// Toggle context expansion
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('toggle-context') || e.target.parentElement.classList.contains('toggle-context')) {
        const button = e.target.classList.contains('toggle-context') ? e.target : e.target.parentElement;
        const context = button.parentElement.querySelector('.context-data');
        
        if (context.classList.contains('hidden')) {
            context.classList.remove('hidden');
            button.innerHTML = '<i class="fas fa-info-circle"></i> Hide Context';
        } else {
            context.classList.add('hidden');
            button.innerHTML = '<i class="fas fa-info-circle"></i> Context';
        }
    }
});

// Toggle stack trace expansion
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('toggle-stack') || e.target.parentElement.classList.contains('toggle-stack')) {
        const button = e.target.classList.contains('toggle-stack') ? e.target : e.target.parentElement;
        const stack = button.parentElement.querySelector('.stack-trace');
        
        if (stack.classList.contains('hidden')) {
            stack.classList.remove('hidden');
            button.innerHTML = '<i class="fas fa-list"></i> Hide Stack Trace';
        } else {
            stack.classList.add('hidden');
            button.innerHTML = '<i class="fas fa-list"></i> Stack Trace';
        }
    }
});

// Show similar logs function
function showSimilarLogs(hash) {
    // Redirect to patterns page filtered by this hash
    window.location.href = '{{ route("smart-log-analyzer.patterns") }}?search=' + hash;
}

// Auto-refresh functionality
let autoRefreshInterval;
function toggleAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
        document.querySelector('.auto-refresh-btn').innerHTML = '<i class="fas fa-play"></i> Auto Refresh';
    } else {
        autoRefreshInterval = setInterval(() => {
            window.location.reload();
        }, 30000); // 30 seconds
        document.querySelector('.auto-refresh-btn').innerHTML = '<i class="fas fa-pause"></i> Stop Auto Refresh';
    }
}
</script>
@endpush