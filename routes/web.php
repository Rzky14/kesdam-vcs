<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ArchiveController;

Route::get('/', function () {
    return view('welcome');
});

// Report Routes
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/create-schedule', [ReportController::class, 'createScheduleReport'])->name('create.schedule');
    Route::post('/store-schedule', [ReportController::class, 'storeScheduleReport'])->name('store.schedule');
    Route::get('/create-document', [ReportController::class, 'createDocumentReport'])->name('create.document');
    Route::post('/store-document', [ReportController::class, 'storeDocumentReport'])->name('store.document');
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
