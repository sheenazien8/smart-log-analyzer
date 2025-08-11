<?php

namespace SmartLogAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class LogEntry extends Model
{
    protected $table = 'smart_log_entries';

    protected $fillable = [
        'level',
        'message',
        'context',
        'channel',
        'file_path',
        'line_number',
        'exception_class',
        'stack_trace',
        'hash',
        'logged_at',
    ];

    protected $casts = [
        'context' => 'array',
        'logged_at' => 'datetime',
        'line_number' => 'integer',
    ];

    public function errorPattern(): BelongsTo
    {
        return $this->belongsTo(ErrorPattern::class, 'hash', 'pattern_hash');
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeInTimeRange($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('logged_at', [$start, $end]);
    }

    public function scopeErrors($query)
    {
        return $query->whereIn('level', ['emergency', 'alert', 'critical', 'error']);
    }

    public function scopeWarnings($query)
    {
        return $query->where('level', 'warning');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('logged_at', '>=', now()->subHours($hours));
    }

    public function getSeverityAttribute(): string
    {
        $severityMap = [
            'emergency' => 'critical',
            'alert' => 'critical',
            'critical' => 'critical',
            'error' => 'high',
            'warning' => 'medium',
            'notice' => 'low',
            'info' => 'low',
            'debug' => 'low',
        ];

        return $severityMap[$this->level] ?? 'low';
    }

    public function getShortMessageAttribute(): string
    {
        return strlen($this->message) > 100 
            ? substr($this->message, 0, 100) . '...' 
            : $this->message;
    }

    public static function generateHash(string $message, ?string $exceptionClass = null, ?string $filePath = null): string
    {
        $normalizedMessage = preg_replace('/\d+/', 'X', $message);
        $normalizedMessage = preg_replace('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/', 'UUID', $normalizedMessage);
        $normalizedMessage = preg_replace('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', 'IP', $normalizedMessage);
        
        $hashInput = $normalizedMessage . '|' . ($exceptionClass ?? '') . '|' . ($filePath ?? '');
        
        return hash('sha256', $hashInput);
    }
}