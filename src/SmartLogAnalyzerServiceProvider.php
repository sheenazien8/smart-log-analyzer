<?php

namespace SmartLogAnalyzer;

use Illuminate\Support\ServiceProvider;
use SmartLogAnalyzer\Commands\InstallCommand;
use SmartLogAnalyzer\Commands\AnalyzeLogsCommand;
use SmartLogAnalyzer\Commands\FixIndexesCommand;
use SmartLogAnalyzer\Commands\TestDatabaseCommand;
use SmartLogAnalyzer\Services\LogParser;
use SmartLogAnalyzer\Services\PatternAnalyzer;
use SmartLogAnalyzer\Services\AnomalyDetector;
use SmartLogAnalyzer\Services\AlertManager;
use SmartLogAnalyzer\Services\DatabaseService;

class SmartLogAnalyzerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/smart-log-analyzer.php',
            'smart-log-analyzer'
        );

        $this->app->singleton(LogParser::class);
        $this->app->singleton(PatternAnalyzer::class);
        $this->app->singleton(AnomalyDetector::class);
        $this->app->singleton(AlertManager::class);
        $this->app->singleton(DatabaseService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'smart-log-analyzer');

        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/smart-log-analyzer.php' => config_path('smart-log-analyzer.php'),
            ], 'smart-log-analyzer-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'smart-log-analyzer-migrations');

            // Publish views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/smart-log-analyzer'),
            ], 'smart-log-analyzer-views');

            // Publish assets
            $this->publishes([
                __DIR__ . '/../resources/js' => public_path('vendor/smart-log-analyzer/js'),
                __DIR__ . '/../resources/css' => public_path('vendor/smart-log-analyzer/css'),
            ], 'smart-log-analyzer-assets');

            // Publish everything with default tag
            $this->publishes([
                __DIR__ . '/../config/smart-log-analyzer.php' => config_path('smart-log-analyzer.php'),
                __DIR__ . '/../database/migrations' => database_path('migrations'),
                __DIR__ . '/../resources/views' => resource_path('views/vendor/smart-log-analyzer'),
                __DIR__ . '/../resources/js' => public_path('vendor/smart-log-analyzer/js'),
                __DIR__ . '/../resources/css' => public_path('vendor/smart-log-analyzer/css'),
            ], 'smart-log-analyzer');

            $this->commands([
                InstallCommand::class,
                AnalyzeLogsCommand::class,
                FixIndexesCommand::class,
                TestDatabaseCommand::class,
            ]);
        }
    }
}