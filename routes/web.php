<?php

use App\Http\Controllers\AiAnalysisController;
use App\Http\Controllers\AiStatusController;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InitiativeController;
use App\Http\Controllers\KPIMonitoringController;
use App\Http\Controllers\MeasurementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // KPI Monitoring
    Route::get('/kpi-monitoring', [KPIMonitoringController::class, 'index'])->name('kpi-monitoring.index');
    Route::get('/kpi-monitoring/{measurement}', [KPIMonitoringController::class, 'show'])->name('kpi-monitoring.show');
    Route::post('/kpi-monitoring/recalculate', [KPIMonitoringController::class, 'recalculate'])->name('kpi-monitoring.recalculate');

    // Measurements
    Route::resource('measurements', MeasurementController::class);

    // Targets
    Route::resource('targets', TargetController::class)->except(['show']);

    // Initiatives
    Route::resource('initiatives', InitiativeController::class)->except(['show']);

    // Upload Evidence
    Route::get('/uploads', [UploadController::class, 'index'])->name('uploads.index');
    Route::get('/uploads/create', [UploadController::class, 'create'])->name('uploads.create');
    Route::post('/uploads', [UploadController::class, 'store'])->name('uploads.store');
    Route::get('/uploads/{upload}', [UploadController::class, 'show'])->name('uploads.show');
    Route::delete('/uploads/{upload}', [UploadController::class, 'destroy'])->name('uploads.destroy');
    Route::post('/uploads/{upload}/retry', [UploadController::class, 'retry'])->name('uploads.retry');
    Route::post('/uploads/batch-process', [UploadController::class, 'batchProcess'])->name('uploads.batch-process');

    // AI Analysis
    Route::get('/ai-analysis', [AiAnalysisController::class, 'index'])->name('ai-analysis.index');
    Route::get('/ai-analysis/{aiResult}', [AiAnalysisController::class, 'show'])->name('ai-analysis.show');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/insight-regenerate', [ReportController::class, 'regenerate'])->name('reports.insight-regenerate');

    // Audit Trail
    Route::get('/audit-trail', [AuditTrailController::class, 'index'])->name('audit-trail.index');
    Route::get('/audit-trail/{auditTrail}', [AuditTrailController::class, 'show'])->name('audit-trail.show');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/gemini-key', [SettingsController::class, 'updateGeminiKey'])->name('settings.gemini-key');
    Route::get('/settings/system-info', [SettingsController::class, 'systemInfo'])->name('settings.system-info');

    // API Endpoints (JSON)
    Route::get('/search', SearchController::class)->name('search');
    Route::get('/api/ai-status', AiStatusController::class)->name('api.ai-status');
    Route::get('/api/notifications', NotificationController::class)->name('api.notifications');
});
