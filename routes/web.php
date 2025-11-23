<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
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
    Route::get('/dashboard', [LoginController::class, 'dashboard'])->name('dashboard');
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
});
