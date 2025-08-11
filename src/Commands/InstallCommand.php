<?php

namespace SmartLogAnalyzer\Commands;

use Illuminate\Console\Command;
use SmartLogAnalyzer\Services\AlertManager;
use SmartLogAnalyzer\Jobs\ProcessLogFileJob;
use SmartLogAnalyzer\Services\LogParser;
use Illuminate\Support\Facades\Artisan;

class InstallCommand extends Command
{
    protected $signature = 'smart-log:install 
                           {--force : Force installation even if already installed}
                           {--skip-migration : Skip running migrations}
                           {--skip-analysis : Skip initial log analysis}';

    protected $description = 'Install and configure Smart Log Analyzer';

    public function handle(AlertManager $alertManager, LogParser $logParser): int
    {
        $this->info('Installing Smart Log Analyzer...');

        if (!$this->option('skip-migration')) {
            $this->runMigrations();
        }

        $this->publishAssets();
        $this->createDefaultAlertRules($alertManager);
        
        if (!$this->option('skip-analysis')) {
            $this->performInitialAnalysis($logParser);
        }

        $this->displayPostInstallInstructions();

        $this->info('Smart Log Analyzer installation completed successfully!');
        return 0;
    }

    private function runMigrations(): void
    {
        $this->info('Running database migrations...');
        
        try {
            // Run all migrations (including the published ones)
            Artisan::call('migrate', ['--force' => true]);
            
            $this->info('Migrations completed successfully.');
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            $this->warn('You may need to run migrations manually:');
            $this->line('php artisan migrate');
        }
    }

    private function publishAssets(): void
    {
        $this->info('Publishing configuration and assets...');

        try {
            // Publish all assets with the main tag
            Artisan::call('vendor:publish', [
                '--provider' => 'SmartLogAnalyzer\\SmartLogAnalyzerServiceProvider',
                '--tag' => 'smart-log-analyzer',
                '--force' => $this->option('force')
            ]);

            $this->info('Assets published successfully.');
        } catch (\Exception $e) {
            $this->error('Asset publishing failed: ' . $e->getMessage());
            
            // Try publishing individually if the main publish fails
            $this->warn('Attempting to publish assets individually...');
            
            try {
                Artisan::call('vendor:publish', [
                    '--provider' => 'SmartLogAnalyzer\\SmartLogAnalyzerServiceProvider',
                    '--tag' => 'smart-log-analyzer-config',
                    '--force' => $this->option('force')
                ]);

                Artisan::call('vendor:publish', [
                    '--provider' => 'SmartLogAnalyzer\\SmartLogAnalyzerServiceProvider',
                    '--tag' => 'smart-log-analyzer-migrations',
                    '--force' => $this->option('force')
                ]);

                Artisan::call('vendor:publish', [
                    '--provider' => 'SmartLogAnalyzer\\SmartLogAnalyzerServiceProvider',
                    '--tag' => 'smart-log-analyzer-assets',
                    '--force' => $this->option('force')
                ]);

                $this->info('Individual asset publishing completed.');
            } catch (\Exception $individualError) {
                $this->error('Individual asset publishing also failed: ' . $individualError->getMessage());
            }
        }
    }

    private function createDefaultAlertRules(AlertManager $alertManager): void
    {
        $this->info('Creating default alert rules...');

        try {
            $alertManager->createDefaultAlertRules();
            $this->info('Default alert rules created.');
        } catch (\Exception $e) {
            $this->error('Failed to create alert rules: ' . $e->getMessage());
        }
    }

    private function performInitialAnalysis(LogParser $logParser): void
    {
        $this->info('Starting initial log analysis...');

        $logPaths = config('smart-log-analyzer.log_paths', []);
        
        if (empty($logPaths)) {
            $this->warn('No log paths configured. Skipping initial analysis.');
            return;
        }

        $logFiles = $logParser->getLogFiles($logPaths);
        
        if ($logFiles->isEmpty()) {
            $this->warn('No log files found. Skipping initial analysis.');
            return;
        }

        $this->info("Found {$logFiles->count()} log files to analyze.");

        $bar = $this->output->createProgressBar($logFiles->count());
        $bar->start();

        foreach ($logFiles as $logFile) {
            try {
                ProcessLogFileJob::dispatch($logFile, false);
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\nFailed to queue analysis for {$logFile}: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Initial analysis jobs queued. Run queue workers to process them.');
        $this->line('php artisan queue:work');
    }

    private function displayPostInstallInstructions(): void
    {
        $this->newLine();
        $this->info('=== Post-Installation Instructions ===');
        $this->newLine();

        $this->line('1. Configure your log paths in config/smart-log-analyzer.php');
        $this->line('2. Set up email configuration for alerts');
        $this->line('3. Start queue workers to process logs:');
        $this->line('   php artisan queue:work');
        $this->newLine();

        $this->line('4. Schedule the log analysis command in your crontab:');
        $this->line('   * * * * * php artisan smart-log:analyze');
        $this->newLine();

        $routePrefix = config('smart-log-analyzer.dashboard.route_prefix', 'smart-log-analyzer');
        $appUrl = config('app.url', 'http://localhost');
        $dashboardUrl = rtrim($appUrl, '/') . '/' . ltrim($routePrefix, '/');
        $this->line("5. Access the dashboard at: {$dashboardUrl}");
        $this->newLine();

        $this->info('For more information, visit the documentation.');
    }
}