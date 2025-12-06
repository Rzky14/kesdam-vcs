<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ApprovalController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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
    Route::resource('documents', \App\Http\Controllers\DocumentController::class);
    Route::post('/documents/{document}/submit', [\App\Http\Controllers\DocumentController::class, 'submit'])->name('documents.submit');
    Route::post('/documents/{document}/archive', [\App\Http\Controllers\DocumentController::class, 'archive'])->name('documents.archive');
    Route::get('/documents/{document}/download/{attachmentIndex}', [\App\Http\Controllers\DocumentController::class, 'download'])->name('documents.download');
    
    // Approval Workflow Routes (requires approval permissions)
    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/dashboard', [ApprovalController::class, 'dashboard'])->name('dashboard');
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
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::get('/preferences', [\App\Http\Controllers\NotificationPreferenceController::class, 'show'])->name('preferences');
        Route::post('/preferences', [\App\Http\Controllers\NotificationPreferenceController::class, 'update'])->name('preferences.update');
        
        // API endpoints for notifications
        Route::get('/api/unread', [\App\Http\Controllers\NotificationController::class, 'getUnread'])->name('api.unread');
        Route::get('/api/all', [\App\Http\Controllers\NotificationController::class, 'getAll'])->name('api.all');
        Route::get('/api/by-type/{type}', [\App\Http\Controllers\NotificationController::class, 'getByType'])->name('api.by-type');
        Route::get('/api/count', [\App\Http\Controllers\NotificationController::class, 'getCount'])->name('api.count');
        Route::get('/api/statistics', [\App\Http\Controllers\NotificationController::class, 'getStatistics'])->name('api.statistics');
        
        // Mark as read
        Route::post('/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::post('/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        
        // Delete
        Route::delete('/{notification}', [\App\Http\Controllers\NotificationController::class, 'delete'])->name('delete');
        Route::post('/delete-all', [\App\Http\Controllers\NotificationController::class, 'deleteAll'])->name('delete-all');
    });
});
