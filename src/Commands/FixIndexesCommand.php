<?php

namespace SmartLogAnalyzer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixIndexesCommand extends Command
{
    protected $signature = 'smart-log:fix-indexes';
    protected $description = 'Fix long index names that may cause MySQL errors';

    public function handle(): int
    {
        $this->info('Fixing Smart Log Analyzer index names...');

        try {
            $this->fixLogEntriesIndexes();
            $this->fixErrorPatternsIndexes();
            $this->fixAlertRulesIndexes();
            $this->fixAnomalyDetectionsIndexes();

            $this->info('Index names fixed successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to fix indexes: ' . $e->getMessage());
            return 1;
        }
    }

    private function fixLogEntriesIndexes(): void
    {
        $table = 'smart_log_entries';
        
        if (!Schema::hasTable($table)) {
            return;
        }

        $this->info("Fixing indexes for {$table}...");

        // Drop old indexes if they exist and recreate with shorter names
        $this->dropAndRecreateIndex($table, ['level', 'logged_at'], 'sle_level_time_idx');
        $this->dropAndRecreateIndex($table, ['channel', 'logged_at'], 'sle_channel_time_idx');
        $this->dropAndRecreateIndex($table, ['hash', 'logged_at'], 'sle_hash_time_idx');
    }

    private function fixErrorPatternsIndexes(): void
    {
        $table = 'smart_log_error_patterns';
        
        if (!Schema::hasTable($table)) {
            return;
        }

        $this->info("Fixing indexes for {$table}...");

        $this->dropAndRecreateIndex($table, ['severity', 'last_seen'], 'slep_severity_time_idx');
        $this->dropAndRecreateIndex($table, ['occurrence_count', 'last_seen'], 'slep_count_time_idx');
        $this->dropAndRecreateIndex($table, ['is_resolved', 'last_seen'], 'slep_resolved_time_idx');
    }

    private function fixAlertRulesIndexes(): void
    {
        $table = 'smart_log_alert_rules';
        
        if (!Schema::hasTable($table)) {
            return;
        }

        $this->info("Fixing indexes for {$table}...");

        $this->dropAndRecreateIndex($table, ['is_active', 'trigger_type'], 'slar_active_type_idx');
        $this->dropAndRecreateIndex($table, ['severity', 'is_active'], 'slar_severity_active_idx');
    }

    private function fixAnomalyDetectionsIndexes(): void
    {
        $table = 'smart_log_anomaly_detections';
        
        if (!Schema::hasTable($table)) {
            return;
        }

        $this->info("Fixing indexes for {$table}...");

        $this->dropAndRecreateIndex($table, ['anomaly_type', 'detection_time'], 'sla_anomaly_type_time_idx');
        $this->dropAndRecreateIndex($table, ['status', 'detection_time'], 'sla_status_time_idx');
        $this->dropAndRecreateIndex($table, ['deviation_score', 'detection_time'], 'sla_deviation_time_idx');
    }

    private function dropAndRecreateIndex(string $table, array $columns, string $indexName): void
    {
        try {
            // Try to drop any existing index on these columns
            $existingIndexes = $this->getTableIndexes($table);
            
            foreach ($existingIndexes as $existingIndex) {
                if ($this->indexMatchesColumns($existingIndex, $columns)) {
                    $this->line("  Dropping existing index: {$existingIndex['name']}");
                    Schema::table($table, function ($tableSchema) use ($existingIndex) {
                        $tableSchema->dropIndex($existingIndex['name']);
                    });
                }
            }

            // Create the new index with the correct name
            $this->line("  Creating index: {$indexName}");
            Schema::table($table, function ($tableSchema) use ($columns, $indexName) {
                $tableSchema->index($columns, $indexName);
            });

        } catch (\Exception $e) {
            $this->warn("  Could not fix index {$indexName}: " . $e->getMessage());
        }
    }

    private function getTableIndexes(string $table): array
    {
        $indexes = [];
        
        try {
            $results = DB::select("SHOW INDEX FROM `{$table}`");
            
            foreach ($results as $result) {
                $indexName = $result->Key_name;
                if ($indexName === 'PRIMARY') {
                    continue;
                }
                
                if (!isset($indexes[$indexName])) {
                    $indexes[$indexName] = [
                        'name' => $indexName,
                        'columns' => []
                    ];
                }
                
                $indexes[$indexName]['columns'][] = $result->Column_name;
            }
            
        } catch (\Exception $e) {
            $this->warn("Could not get indexes for table {$table}: " . $e->getMessage());
        }

        return array_values($indexes);
    }

    private function indexMatchesColumns(array $index, array $columns): bool
    {
        return count($index['columns']) === count($columns) && 
               empty(array_diff($index['columns'], $columns));
    }
}