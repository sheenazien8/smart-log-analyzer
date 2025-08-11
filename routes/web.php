<?php

use Illuminate\Support\Facades\Route;
use SmartLogAnalyzer\Controllers\DashboardController;
use SmartLogAnalyzer\Controllers\ApiController;

$routePrefix = config('smart-log-analyzer.dashboard.route_prefix', 'smart-log-analyzer');
$middleware = config('smart-log-analyzer.dashboard.middleware', ['web']);

Route::prefix($routePrefix)
    ->middleware($middleware)
    ->name('smart-log-analyzer.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/patterns', [DashboardController::class, 'patterns'])->name('patterns');
        Route::get('/patterns/{id}', [DashboardController::class, 'patternDetails'])->name('pattern-details');
        Route::post('/patterns/{id}/resolve', [DashboardController::class, 'resolvePattern'])->name('pattern-resolve');
        Route::post('/patterns/{id}/unresolve', [DashboardController::class, 'unresolvePattern'])->name('pattern-unresolve');
        
        Route::get('/anomalies', [DashboardController::class, 'anomalies'])->name('anomalies');
        Route::post('/anomalies/{id}/resolve', [DashboardController::class, 'resolveAnomaly'])->name('anomaly-resolve');
        Route::post('/anomalies/{id}/ignore', [DashboardController::class, 'ignoreAnomaly'])->name('anomaly-ignore');
        
        Route::get('/logs', [DashboardController::class, 'logs'])->name('logs');
    });

// API Routes
if (config('smart-log-analyzer.api.enabled', true)) {
    $apiPrefix = config('smart-log-analyzer.api.route_prefix', 'api/smart-log-analyzer');
    $apiMiddleware = config('smart-log-analyzer.api.middleware', ['api']);

    Route::prefix($apiPrefix)
        ->middleware($apiMiddleware)
        ->name('smart-log-analyzer.api.')
        ->group(function () {
            Route::get('/stats', [ApiController::class, 'stats'])->name('stats');
            Route::get('/patterns', [ApiController::class, 'patterns'])->name('patterns');
            Route::get('/patterns/{id}', [ApiController::class, 'patternDetails'])->name('pattern-details');
            Route::get('/anomalies', [ApiController::class, 'anomalies'])->name('anomalies');
            Route::get('/logs', [ApiController::class, 'logs'])->name('logs');
        });
}