<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart Log Analyzer')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('vendor/smart-log-analyzer/css/dashboard.css') }}" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#4f46e5',
                        danger: '#dc2626',
                        warning: '#f59e0b',
                        success: '#10b981',
                        info: '#06b6d4',
                        secondary: '#6b7280'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <!-- Top Navigation -->
    <nav class="bg-gray-900 dark:bg-gray-800 text-white shadow-lg">
        <div class="max-w-full mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="{{ route('smart-log-analyzer.dashboard') }}" class="flex items-center text-white hover:text-gray-300 transition-colors">
                        <i class="fas fa-chart-line mr-2"></i>
                        <span class="font-semibold text-lg">Smart Log Analyzer</span>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Dark Mode Toggle -->
                    <button id="darkModeToggle" class="flex items-center p-2 text-gray-300 hover:text-white transition-colors rounded-md">
                        <i id="darkModeIcon" class="fas fa-moon"></i>
                    </button>
                    
                    <!-- Time Range Dropdown -->
                    <div class="relative">
                        <div class="dropdown">
                            <button class="flex items-center text-white hover:text-gray-300 px-3 py-2 rounded-md text-sm font-medium transition-colors" onclick="toggleDropdown()">
                                <i class="fas fa-clock mr-1"></i>Time Range
                                <i class="fas fa-chevron-down ml-1"></i>
                            </button>
                            <div id="timeDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg z-50">
                                <div class="py-1">
                                    <a href="?range=1h" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Last Hour</a>
                                    <a href="?range=6h" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Last 6 Hours</a>
                                    <a href="?range=24h" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Last 24 Hours</a>
                                    <a href="?range=7d" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Last 7 Days</a>
                                    <a href="?range=30d" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Last 30 Days</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex h-screen pt-16">
        <!-- Sidebar -->
        <nav class="fixed top-16 left-0 w-64 h-full bg-white dark:bg-gray-800 shadow-lg border-r border-gray-200 dark:border-gray-700 overflow-y-auto">
            <div class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('smart-log-analyzer.dashboard') }}" 
                           class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('smart-log-analyzer.dashboard') ? 'bg-primary text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('smart-log-analyzer.patterns') }}" 
                           class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('smart-log-analyzer.patterns*') ? 'bg-primary text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            <i class="fas fa-project-diagram mr-3"></i>Error Patterns
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('smart-log-analyzer.anomalies') }}" 
                           class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('smart-log-analyzer.anomalies') ? 'bg-primary text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            <i class="fas fa-exclamation-triangle mr-3"></i>Anomalies
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('smart-log-analyzer.logs') }}" 
                           class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('smart-log-analyzer.logs') ? 'bg-primary text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            <i class="fas fa-list mr-3"></i>Raw Logs
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 ml-64 p-6 overflow-y-auto">
            @if(session('success'))
                <div class="bg-green-100 dark:bg-green-900/20 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-4 py-3 rounded mb-4 relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                    <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 dark:bg-red-900/20 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-4 relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                    <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script src="{{ asset('vendor/smart-log-analyzer/js/dashboard.js') }}"></script>
    <script>
        // Dark Mode Toggle
        function initDarkMode() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const darkModeIcon = document.getElementById('darkModeIcon');
            const html = document.documentElement;
            
            // Check for saved theme preference or default to 'light'
            const savedTheme = localStorage.getItem('theme') || 'light';
            
            // Apply the saved theme
            if (savedTheme === 'dark') {
                html.classList.add('dark');
                darkModeIcon.className = 'fas fa-sun';
            } else {
                html.classList.remove('dark');
                darkModeIcon.className = 'fas fa-moon';
            }
            
            // Toggle dark mode
            darkModeToggle.addEventListener('click', function() {
                if (html.classList.contains('dark')) {
                    html.classList.remove('dark');
                    darkModeIcon.className = 'fas fa-moon';
                    localStorage.setItem('theme', 'light');
                } else {
                    html.classList.add('dark');
                    darkModeIcon.className = 'fas fa-sun';
                    localStorage.setItem('theme', 'dark');
                }
            });
        }

        // Dropdown functionality
        function toggleDropdown() {
            const dropdown = document.getElementById('timeDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('timeDropdown');
            const button = event.target.closest('.dropdown button');
            
            if (!button && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Initialize dark mode when DOM is loaded
        document.addEventListener('DOMContentLoaded', initDarkMode);
    </script>
    @stack('scripts')
</body>
</html>