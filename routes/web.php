<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SuratController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ArchiveController;

// Redirect root to dashboard if authenticated, otherwise to login
Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Guest routes (unauthenticated users)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getDocumentChartData'])->name('dashboard.chart-data');
    Route::get('/dashboard/quick-stats', [DashboardController::class, 'getQuickStats'])->name('dashboard.quick-stats');
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');
    
    // User Management Routes (requires users.* permissions)
    Route::resource('users', \App\Http\Controllers\UserController::class);
    
    // Profile Management Routes (accessible by all authenticated users)
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password.update');
    
    // Schedule Management Routes (requires schedules.* permissions)
    Route::resource('schedules', \App\Http\Controllers\ScheduleController::class);
    
    // Document Management Routes (requires documents.* permissions)
    Route::resource('surat', SuratController::class);
    Route::post('/surat/{surat}/submit', [SuratController::class, 'submit'])->name('surat.submit');
    Route::post('/surat/{surat}/archive', [SuratController::class, 'archive'])->name('surat.archive');
    Route::get('/surat/{surat}/download/{attachmentIndex}', [SuratController::class, 'download'])->name('surat.download');
    
    // Document Approval Actions (directly from document detail page)
    Route::post('/surat/{surat}/approve', [SuratController::class, 'approve'])->name('surat.approve');
    Route::post('/surat/{surat}/reject', [SuratController::class, 'reject'])->name('surat.reject');
    Route::post('/surat/{surat}/request-correction', [SuratController::class, 'requestCorrection'])->name('surat.request-correction');
    
    // Approval Workflow Routes (requires approval permissions)
    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/pending', [ApprovalController::class, 'pending'])->name('pending');
        Route::get('/corrections', [ApprovalController::class, 'corrections'])->name('corrections');
        
        // Document approval routes
        Route::get('/{document}', [ApprovalController::class, 'show'])->name('show');
        Route::get('/{document}/history', [ApprovalController::class, 'history'])->name('history');
        
        // Approval actions
        Route::get('/{document}/approve', [ApprovalController::class, 'approveForm'])->name('approve-form');
        Route::post('/{document}/approve', [ApprovalController::class, 'approve'])->name('approve');
        
        Route::get('/{document}/reject', [ApprovalController::class, 'rejectForm'])->name('reject-form');
        Route::post('/{document}/reject', [ApprovalController::class, 'reject'])->name('reject');
        
        Route::get('/{document}/correction', [ApprovalController::class, 'correctionForm'])->name('correction-form');
        Route::post('/{document}/correction', [ApprovalController::class, 'requestCorrection'])->name('correction');
        
        Route::get('/{document}/resubmit', [ApprovalController::class, 'resubmitForm'])->name('resubmit-form');
        Route::post('/{document}/resubmit', [ApprovalController::class, 'resubmit'])->name('resubmit');
        
        Route::get('/{document}/report', [ApprovalController::class, 'downloadReport'])->name('report');
    });
    
    // Notification Routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/recent', [NotificationController::class, 'recent'])->name('recent');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/delete-all', [NotificationController::class, 'deleteAll'])->name('delete-all');
        
        Route::get('/preferences', [NotificationController::class, 'preferences'])->name('preferences');
        Route::put('/preferences', [NotificationController::class, 'updatePreferences'])->name('preferences.update');
        
        Route::get('/{id}', [NotificationController::class, 'show'])->name('show');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    });
    
    // Report Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/create-schedule', [ReportController::class, 'createScheduleReport'])->name('create.schedule');
        Route::post('/store-schedule', [ReportController::class, 'storeScheduleReport'])->name('store.schedule');
        Route::get('/create-document', [ReportController::class, 'createDocumentReport'])->name('create.document');
        Route::post('/store-document', [ReportController::class, 'storeDocumentReport'])->name('store.document');
        Route::get('/create-effectiveness', [ReportController::class, 'createEffectivenessReport'])->name('create.effectiveness');
        Route::post('/store-effectiveness', [ReportController::class, 'storeEffectivenessReport'])->name('store.effectiveness');
        Route::get('/{report}', [ReportController::class, 'show'])->name('show');
        Route::get('/{report}/export-pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/{report}/export-excel', [ReportController::class, 'exportExcel'])->name('export.excel');
        Route::delete('/{report}', [ReportController::class, 'destroy'])->name('destroy');
    });
    
    // Archive Routes
    Route::prefix('archives')->name('archives.')->group(function () {
        Route::get('/', [ArchiveController::class, 'index'])->name('index');
        Route::get('/search', [ArchiveController::class, 'search'])->name('search');
        Route::get('/statistics', [ArchiveController::class, 'statistics'])->name('statistics');
        Route::get('/{archive}', [ArchiveController::class, 'show'])->name('show');
        Route::post('/{archive}/tags', [ArchiveController::class, 'addTags'])->name('add-tags');
        Route::delete('/{archive}', [ArchiveController::class, 'destroy'])->name('destroy');
        Route::post('/{archive}/restore', [ArchiveController::class, 'restore'])->name('restore');
    });
    
    // Settings Routes
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SettingController::class, 'index'])->name('index');
        Route::put('/notifications', [\App\Http\Controllers\SettingController::class, 'updateNotifications'])->name('notifications.update');
        Route::put('/system', [\App\Http\Controllers\SettingController::class, 'updateSystem'])->name('system.update');
        Route::post('/backup', [\App\Http\Controllers\SettingController::class, 'backup'])->name('backup');
        Route::get('/integration-config', [\App\Http\Controllers\SettingController::class, 'showIntegrationConfig'])->name('integration.config');
    });
});
