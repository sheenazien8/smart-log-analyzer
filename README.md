# Smart Log Analyzer

A comprehensive Laravel package that uses AI/ML techniques to analyze application logs, identify patterns, detect anomalies, and provide actionable insights through a web dashboard and automated alerts.

## Features

- **Intelligent Log Parsing**: Automatically parse Laravel's default log format and structured logs
- **Pattern Recognition**: Group similar errors using advanced text similarity algorithms
- **Anomaly Detection**: Identify unusual patterns and sudden spikes in error rates
- **Real-time Monitoring**: Watch for new log entries as they're written
- **Web Dashboard**: Beautiful, responsive interface with charts and metrics
- **Automated Alerts**: Email notifications for critical issues with intelligent throttling
- **API Access**: RESTful API for integration with external tools
- **Background Processing**: Efficient queue-based log processing

## Requirements

- PHP 8.1+
- Laravel 9.x, 10.x, or 11.x
- **Database**: MySQL 5.7+, PostgreSQL 12+, or SQLite 3.25+
- Queue worker (Redis, database, or other Laravel-supported drivers)

### Database Compatibility
The package automatically detects your database driver and uses appropriate SQL syntax:
- **MySQL**: Uses `DATE_FORMAT()` and MySQL-specific functions
- **PostgreSQL**: Uses `TO_CHAR()` and PostgreSQL-specific functions  
- **SQLite**: Uses `strftime()` and SQLite-specific functions

## Installation

1. Install the package via Composer:

```bash
composer require sheenazien8/smart-log-analyzer
```

2. Publish and run the migrations:

```bash
php artisan vendor:publish --provider="SmartLogAnalyzer\SmartLogAnalyzerServiceProvider"
php artisan migrate
```

3. Install the package:

```bash
php artisan smart-log:install
```

4. Configure your log paths in `config/smart-log-analyzer.php`

5. Start queue workers:

```bash
php artisan queue:work
```

6. Schedule the analysis command in your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('smart-log:analyze --incremental --detect-anomalies --process-alerts')
             ->everyFiveMinutes();
}
```

## Configuration

The main configuration file is published to `config/smart-log-analyzer.php`. Key settings include:

### Log Paths
```php
'log_paths' => [
    storage_path('logs/laravel.log'),
    storage_path('logs'),
],
```

### Pattern Recognition
```php
'pattern_recognition' => [
    'similarity_threshold' => 0.8,
    'min_occurrences' => 3,
    'time_window' => 3600, // seconds
],
```

### Anomaly Detection
```php
'anomaly_detection' => [
    'enabled' => true,
    'spike_threshold' => 5.0,
    'minimum_baseline_hours' => 24,
],
```

### Email Alerts
```php
'alerts' => [
    'enabled' => true,
    'email' => [
        'recipients' => ['dev@example.com'],
        'throttle_minutes' => 60,
    ],
],
```

## Usage

### Web Dashboard

Access the dashboard at `/smart-log-analyzer` (configurable). The dashboard provides:

- **Overview**: Summary statistics and trends
- **Error Patterns**: Grouped similar errors with occurrence counts
- **Anomalies**: Detected unusual patterns and spikes
- **Raw Logs**: Searchable and filterable log entries

### Artisan Commands

#### Install the package
```bash
php artisan smart-log:install
```

#### Analyze logs manually
```bash
# Analyze all configured log files
php artisan smart-log:analyze

# Analyze specific file
php artisan smart-log:analyze --file=/path/to/log/file

# Incremental analysis (only new entries)
php artisan smart-log:analyze --incremental

# Include anomaly detection and alert processing
php artisan smart-log:analyze --detect-anomalies --process-alerts
```

#### Test database compatibility
```bash
# Test database-specific functions
php artisan smart-log:test-database
```

### API Endpoints

The package provides RESTful API endpoints:

```bash
GET /api/smart-log-analyzer/stats          # Dashboard statistics
GET /api/smart-log-analyzer/patterns       # Error patterns
GET /api/smart-log-analyzer/anomalies      # Detected anomalies
GET /api/smart-log-analyzer/logs           # Raw log entries
```

## How It Works

### 1. Log Parsing
The package parses Laravel log files using regex patterns to extract:
- Timestamp and log level
- Channel and message content
- Exception classes and stack traces
- File paths and line numbers

### 2. Pattern Recognition
Similar errors are grouped using multiple similarity algorithms:
- **Levenshtein Distance**: Character-level similarity
- **Cosine Similarity**: Word-based similarity using TF-IDF
- **Jaccard Similarity**: Set-based similarity

Messages are normalized by replacing dynamic values (numbers, UUIDs, IPs) with placeholders.

### 3. Anomaly Detection
The system detects several types of anomalies:
- **Error Rate Spikes**: Sudden increases in error frequency
- **Volume Anomalies**: Unusual changes in total log volume
- **Pattern Anomalies**: Existing patterns showing unusual behavior
- **New Critical Patterns**: First occurrence of critical errors

### 4. Alert System
Configurable alert rules trigger notifications based on:
- **Threshold Rules**: When metrics exceed defined limits
- **Anomaly Rules**: When anomalies are detected
- **Pattern Rules**: When specific error patterns occur

## Advanced Features

### Custom Pattern Recognition
You can extend pattern recognition by implementing custom similarity algorithms:

```php
use SmartLogAnalyzer\Services\PatternAnalyzer;

class CustomPatternAnalyzer extends PatternAnalyzer
{
    protected function calculateCustomSimilarity(string $message1, string $message2): float
    {
        // Your custom similarity logic
        return $similarity;
    }
}
```

### Custom Alert Channels
Add support for additional notification channels:

```php
use SmartLogAnalyzer\Jobs\SendAlertJob;

class CustomAlertJob extends SendAlertJob
{
    protected function sendCustomAlert(): void
    {
        // Your custom alert logic
    }
}
```

### Real-time Log Monitoring
Enable real-time monitoring for immediate processing:

```php
use SmartLogAnalyzer\Services\LogParser;

$logParser = app(LogParser::class);
$logParser->watchLogFile('/path/to/log', function ($entry) {
    // Process new log entry immediately
});
```

## Performance Considerations

### Memory Usage
- Configure `processing.memory_limit` for large log files
- Use `processing.batch_size` to control memory consumption
- Enable `storage.compress_old_data` for long-term storage

### Queue Configuration
- Use Redis or database queues for better performance
- Configure multiple queue workers for parallel processing
- Set appropriate `processing.timeout` values

### Caching
- Enable caching for improved dashboard performance
- Configure cache TTL based on your needs
- Use Redis for better cache performance

## Troubleshooting

### Common Issues

**MySQL index name too long error**
If you encounter "Identifier name is too long" errors during migration:
```bash
php artisan smart-log:fix-indexes
```

**PostgreSQL function errors**
If you see "function does not exist" errors, the package should automatically handle this. Test compatibility:
```bash
php artisan smart-log:test-database
```

**Queue jobs failing**
- Check queue worker is running: `php artisan queue:work`
- Verify log file permissions are readable
- Check memory limits in configuration

**Dashboard not loading**
- Ensure migrations have been run
- Check web server configuration
- Verify middleware configuration

**No patterns detected**
- Check log paths configuration
- Verify log files contain parseable entries
- Lower similarity threshold if needed

**Alerts not sending**
- Verify email configuration
- Check alert rule conditions
- Ensure queue workers are processing alert jobs

**Migration issues**
If migrations fail, try:
```bash
php artisan migrate:fresh
php artisan smart-log:install --skip-migration
```

### Debug Mode
Enable verbose logging by setting `LOG_LEVEL=debug` in your `.env` file.

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email security@smartloganalyzer.com instead of using the issue tracker.

## License

The Smart Log Analyzer package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Support

- Documentation: [https://docs.smartloganalyzer.com](https://docs.smartloganalyzer.com)
- Issues: [GitHub Issues](https://github.com/smartloganalyzer/smart-log-analyzer/issues)
- Discussions: [GitHub Discussions](https://github.com/smartloganalyzer/smart-log-analyzer/discussions)
