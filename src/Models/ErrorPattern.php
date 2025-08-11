<?php

namespace SmartLogAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class ErrorPattern extends Model
{
    protected $table = 'smart_log_error_patterns';

    protected $fillable = [
        'pattern_hash',
        'pattern_signature',
        'error_type',
        'severity',
        'occurrence_count',
        'first_seen',
        'last_seen',
        'sample_context',
        'suggested_solution',
        'is_resolved',
        'resolved_at',
    ];

    protected $casts = [
        'sample_context' => 'array',
        'first_seen' => 'datetime',
        'last_seen' => 'datetime',
        'resolved_at' => 'datetime',
        'is_resolved' => 'boolean',
        'occurrence_count' => 'integer',
    ];

    public function logEntries(): HasMany
    {
        return $this->hasMany(LogEntry::class, 'hash', 'pattern_hash');
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeRecentlyActive($query, int $hours = 24)
    {
        return $query->where('last_seen', '>=', now()->subHours($hours));
    }

    public function scopeFrequent($query, int $minOccurrences = 10)
    {
        return $query->where('occurrence_count', '>=', $minOccurrences);
    }

    public function scopeByErrorType($query, string $errorType)
    {
        return $query->where('error_type', $errorType);
    }

    public function incrementOccurrence(): void
    {
        $this->increment('occurrence_count');
        $this->update(['last_seen' => now()]);
    }

    public function markAsResolved(): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);
    }

    public function markAsUnresolved(): void
    {
        $this->update([
            'is_resolved' => false,
            'resolved_at' => null,
        ]);
    }

    public function getFrequencyRateAttribute(): float
    {
        if (!$this->first_seen || !$this->last_seen) {
            return 0.0;
        }

        $hoursDiff = $this->first_seen->diffInHours($this->last_seen);
        
        if ($hoursDiff === 0) {
            return (float) $this->occurrence_count;
        }

        return round($this->occurrence_count / $hoursDiff, 2);
    }

    public function getTrendAttribute(): string
    {
        $recentEntries = $this->logEntries()
            ->where('logged_at', '>=', now()->subHours(24))
            ->count();

        $previousEntries = $this->logEntries()
            ->whereBetween('logged_at', [now()->subHours(48), now()->subHours(24)])
            ->count();

        if ($previousEntries === 0) {
            return $recentEntries > 0 ? 'increasing' : 'stable';
        }

        $changeRatio = $recentEntries / $previousEntries;

        if ($changeRatio > 1.5) {
            return 'increasing';
        } elseif ($changeRatio < 0.5) {
            return 'decreasing';
        }

        return 'stable';
    }

    public function getSuggestedSolutionAttribute($value): ?string
    {
        if ($value) {
            return $value;
        }

        return $this->generateSuggestedSolution();
    }

    private function generateSuggestedSolution(): ?string
    {
        $commonSolutions = [
            'PDOException' => 'Check database connection settings and ensure the database server is running.',
            'QueryException' => 'Review the SQL query for syntax errors or missing table/column references.',
            'ModelNotFoundException' => 'Verify that the requested model exists in the database.',
            'ValidationException' => 'Check input validation rules and ensure all required fields are provided.',
            'AuthenticationException' => 'Verify user credentials and authentication configuration.',
            'AuthorizationException' => 'Check user permissions and authorization policies.',
            'FileNotFoundException' => 'Ensure the file exists at the specified path and check file permissions.',
            'MethodNotAllowedHttpException' => 'Verify the HTTP method matches the route definition.',
            'NotFoundHttpException' => 'Check route definitions and ensure the URL is correct.',
        ];

        foreach ($commonSolutions as $exceptionType => $solution) {
            if (str_contains($this->pattern_signature, $exceptionType)) {
                return $solution;
            }
        }

        return null;
    }
}