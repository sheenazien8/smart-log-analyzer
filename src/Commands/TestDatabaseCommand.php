<?php

namespace SmartLogAnalyzer\Commands;

use Illuminate\Console\Command;
use SmartLogAnalyzer\Services\DatabaseService;
use Illuminate\Support\Facades\DB;

class TestDatabaseCommand extends Command
{
    protected $signature = 'smart-log:test-database';
    protected $description = 'Test database compatibility functions';

    public function handle(): int
    {
        $this->info('Testing Smart Log Analyzer database compatibility...');
        
        $driver = DB::connection()->getDriverName();
        $this->info("Database driver: {$driver}");

        // Test date formatting
        $this->info('Testing date formatting functions:');
        
        $hourlyFormat = DatabaseService::getHourlyDateFormat('logged_at');
        $this->line("Hourly format: {$hourlyFormat}");
        
        $dailyFormat = DatabaseService::getDailyDateFormat('logged_at');
        $this->line("Daily format: {$dailyFormat}");
        
        $monthlyFormat = DatabaseService::getMonthlyDateFormat('logged_at');
        $this->line("Monthly format: {$monthlyFormat}");

        // Test CASE WHEN
        $this->info('Testing CASE WHEN expressions:');
        
        $severityCase = DatabaseService::getDeviationScoreSeverityCase();
        $this->line("Severity case: {$severityCase}");

        // Test date truncation
        $this->info('Testing date truncation:');
        
        $hourTrunc = DatabaseService::getDateTruncExpression('logged_at', 'hour');
        $this->line("Hour truncation: {$hourTrunc}");
        
        $dayTrunc = DatabaseService::getDateTruncExpression('logged_at', 'day');
        $this->line("Day truncation: {$dayTrunc}");

        // Test interval expressions
        $this->info('Testing interval expressions:');
        
        $hourInterval = DatabaseService::getIntervalExpression(1, 'hour');
        $this->line("1 hour interval: {$hourInterval}");
        
        $dayInterval = DatabaseService::getIntervalExpression(7, 'day');
        $this->line("7 day interval: {$dayInterval}");

        // Test random function
        $randomFunc = DatabaseService::getRandomFunction();
        $this->line("Random function: {$randomFunc}");

        $this->info('Database compatibility test completed successfully!');
        return 0;
    }
}