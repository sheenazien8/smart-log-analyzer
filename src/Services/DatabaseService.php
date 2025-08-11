<?php

namespace SmartLogAnalyzer\Services;

use Illuminate\Support\Facades\DB;

class DatabaseService
{
    public static function getDateFormatExpression(string $column, string $format): string
    {
        $driver = DB::connection()->getDriverName();

        switch ($driver) {
            case 'mysql':
                return "DATE_FORMAT({$column}, '{$format}')";
            
            case 'pgsql':
                return self::convertMySQLFormatToPostgreSQL($column, $format);
            
            case 'sqlite':
                return self::convertMySQLFormatToSQLite($column, $format);
            
            default:
                // Fallback to PostgreSQL format
                return self::convertMySQLFormatToPostgreSQL($column, $format);
        }
    }

    private static function convertMySQLFormatToPostgreSQL(string $column, string $format): string
    {
        // Convert MySQL DATE_FORMAT to PostgreSQL TO_CHAR
        $postgresFormat = str_replace([
            '%Y',  // 4-digit year
            '%m',  // Month (01-12)
            '%d',  // Day (01-31)
            '%H',  // Hour (00-23)
            '%i',  // Minutes (00-59)
            '%s',  // Seconds (00-59)
        ], [
            'YYYY',
            'MM',
            'DD',
            'HH24',
            'MI',
            'SS',
        ], $format);

        return "TO_CHAR({$column}, '{$postgresFormat}')";
    }

    private static function convertMySQLFormatToSQLite(string $column, string $format): string
    {
        // Convert MySQL DATE_FORMAT to SQLite strftime
        $sqliteFormat = str_replace([
            '%Y',  // 4-digit year
            '%m',  // Month (01-12)
            '%d',  // Day (01-31)
            '%H',  // Hour (00-23)
            '%i',  // Minutes (00-59)
            '%s',  // Seconds (00-59)
        ], [
            '%Y',
            '%m',
            '%d',
            '%H',
            '%M',
            '%S',
        ], $format);

        return "strftime('{$sqliteFormat}', {$column})";
    }

    public static function getHourlyDateFormat(string $column): string
    {
        return self::getDateFormatExpression($column, '%Y-%m-%d %H:00:00');
    }

    public static function getDailyDateFormat(string $column): string
    {
        return self::getDateFormatExpression($column, '%Y-%m-%d');
    }

    public static function getMonthlyDateFormat(string $column): string
    {
        return self::getDateFormatExpression($column, '%Y-%m');
    }

    public static function getCaseWhenExpression(array $conditions, string $defaultValue = 'NULL'): string
    {
        $caseStatement = 'CASE ';
        
        foreach ($conditions as $condition => $value) {
            $caseStatement .= "WHEN {$condition} THEN '{$value}' ";
        }
        
        $caseStatement .= "ELSE {$defaultValue} END";
        
        return $caseStatement;
    }

    public static function getDeviationScoreSeverityCase(): string
    {
        return self::getCaseWhenExpression([
            'deviation_score >= 5.0' => 'critical',
            'deviation_score >= 3.0' => 'high',
            'deviation_score >= 2.0' => 'medium',
        ], "'low'");
    }

    public static function getRandomFunction(): string
    {
        $driver = DB::connection()->getDriverName();

        switch ($driver) {
            case 'mysql':
                return 'RAND()';
            case 'pgsql':
                return 'RANDOM()';
            case 'sqlite':
                return 'RANDOM()';
            default:
                return 'RANDOM()';
        }
    }

    public static function getDateTruncExpression(string $column, string $precision): string
    {
        $driver = DB::connection()->getDriverName();

        switch ($driver) {
            case 'mysql':
                switch ($precision) {
                    case 'hour':
                        return "DATE_FORMAT({$column}, '%Y-%m-%d %H:00:00')";
                    case 'day':
                        return "DATE_FORMAT({$column}, '%Y-%m-%d')";
                    case 'month':
                        return "DATE_FORMAT({$column}, '%Y-%m')";
                    default:
                        return $column;
                }
            
            case 'pgsql':
                return "DATE_TRUNC('{$precision}', {$column})";
            
            case 'sqlite':
                switch ($precision) {
                    case 'hour':
                        return "strftime('%Y-%m-%d %H:00:00', {$column})";
                    case 'day':
                        return "strftime('%Y-%m-%d', {$column})";
                    case 'month':
                        return "strftime('%Y-%m', {$column})";
                    default:
                        return $column;
                }
            
            default:
                return "DATE_TRUNC('{$precision}', {$column})";
        }
    }

    public static function getIntervalExpression(int $amount, string $unit): string
    {
        $driver = DB::connection()->getDriverName();

        switch ($driver) {
            case 'mysql':
                return "INTERVAL {$amount} {$unit}";
            
            case 'pgsql':
                return "INTERVAL '{$amount} {$unit}'";
            
            case 'sqlite':
                switch ($unit) {
                    case 'HOUR':
                    case 'hour':
                        return "'{$amount} hours'";
                    case 'DAY':
                    case 'day':
                        return "'{$amount} days'";
                    case 'MONTH':
                    case 'month':
                        return "'{$amount} months'";
                    default:
                        return "'{$amount} {$unit}'";
                }
            
            default:
                return "INTERVAL '{$amount} {$unit}'";
        }
    }
}