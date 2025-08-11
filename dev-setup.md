# Development Setup Guide

## Quick Setup for Development

### 1. Create Test Laravel Application

```bash
# Create a new Laravel project
composer create-project laravel/laravel smart-log-test
cd smart-log-test
```

### 2. Configure Local Package Development

Add to `composer.json` in your test Laravel app:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../smart-log-analyzer"
        }
    ],
    "require": {
        "sheenazien8/smart-log-analyzer": "@dev"
    }
}
```

### 3. Install the Package

```bash
# Install dependencies
composer install

# Install the package
composer require sheenazien8/smart-log-analyzer:@dev

# Publish package assets
php artisan vendor:publish --provider="SmartLogAnalyzer\SmartLogAnalyzerServiceProvider"

# Or publish specific components:
# php artisan vendor:publish --tag=smart-log-analyzer-config
# php artisan vendor:publish --tag=smart-log-analyzer-migrations
# php artisan vendor:publish --tag=smart-log-analyzer-assets

# Run the installation
php artisan smart-log:install --force
```

### 4. Configure Environment

Update your `.env` file:

```env
# Database
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Queue (use sync for development)
QUEUE_CONNECTION=sync

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug

# Smart Log Analyzer
SMART_LOG_REAL_TIME=true
SMART_LOG_ALERTS_ENABLED=true
```

### 5. Create Database

```bash
# Create SQLite database file
touch database/database.sqlite

# Run migrations
php artisan migrate
```

### 6. Generate Test Data

Create a command to generate test logs:

```bash
php artisan make:command GenerateTestLogs
```

Add this content to `app/Console/Commands/GenerateTestLogs.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateTestLogs extends Command
{
    protected $signature = 'test:generate-logs {--count=50}';
    protected $description = 'Generate test log entries';

    public function handle()
    {
        $count = $this->option('count');
        
        $levels = ['error', 'warning', 'info', 'debug'];
        $errors = [
            'Database connection failed',
            'File not found: /path/to/file.php',
            'Invalid user credentials',
            'Memory limit exceeded',
            'Undefined variable: $user',
            'Class not found: App\Models\User',
            'SQLSTATE[42S02]: Base table or view not found',
            'Call to undefined method',
            'Maximum execution time exceeded',
            'Permission denied for file access',
        ];
        
        for ($i = 0; $i < $count; $i++) {
            $level = $levels[array_rand($levels)];
            $message = $errors[array_rand($errors)];
            
            Log::log($level, $message, [
                'user_id' => rand(1, 100),
                'ip' => '192.168.1.' . rand(1, 255),
                'timestamp' => now()->toISOString(),
            ]);
        }
        
        $this->info("Generated {$count} test log entries");
    }
}
```

### 7. Development Workflow

```bash
# Generate test logs
php artisan test:generate-logs --count=100

# Analyze logs
php artisan smart-log:analyze --force

# Start development server
php artisan serve

# Access dashboard
open http://localhost:8000/smart-log-analyzer
```

### 8. Troubleshooting

#### Common Issues:

**"Class not found" errors:**
```bash
composer dump-autoload
```

**Migration errors:**
```bash
php artisan migrate:fresh
php artisan smart-log:install --force
```

**No data in dashboard:**
```bash
# Generate test data and analyze
php artisan test:generate-logs --count=100
php artisan smart-log:analyze --force
```

**Permission errors:**
```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

### 9. Development Commands

```bash
# Install package in development mode
composer require sheenazien8/smart-log-analyzer:@dev

# Reinstall after changes
php artisan smart-log:install --force

# Generate and analyze test data
php artisan test:generate-logs --count=50
php artisan smart-log:analyze --force

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### 10. Package Development

When making changes to the package:

1. Make your changes in the package directory
2. No need to reinstall if only changing PHP files (autoloaded)
3. For config changes: `php artisan config:clear`
4. For view changes: `php artisan view:clear`
5. For migration changes: `php artisan migrate:fresh`

### 11. Testing API Endpoints

```bash
# Get dashboard stats
curl http://localhost:8000/api/smart-log-analyzer/stats

# Get error patterns
curl http://localhost:8000/api/smart-log-analyzer/patterns

# Get anomalies
curl http://localhost:8000/api/smart-log-analyzer/anomalies
```
