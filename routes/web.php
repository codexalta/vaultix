<?php

use Illuminate\Support\Facades\Route;
use Codexalta\Vaultix\Http\Controllers\VaultixController;

Route::middleware(['web', 'vaultix.auth'])->prefix('vaultix')->group(function () {
    Route::get('/', [VaultixController::class, 'index'])->name('vaultix.index');
    
    // Destinations
    Route::post('/backups/run-now', [VaultixController::class, 'runNow'])->name('vaultix.run_now');
    Route::get('/backups/export', [VaultixController::class, 'exportConfigs'])->name('vaultix.export');
    Route::post('/backups/import', [VaultixController::class, 'importConfigs'])->name('vaultix.import');
    Route::get('/destinations/create', [VaultixController::class, 'createDestination'])->name('vaultix.destinations.create');
    Route::post('/destinations', [VaultixController::class, 'storeDestination'])->name('vaultix.destinations.store');
    Route::get('/destinations/{destination}/edit', [VaultixController::class, 'editDestination'])->name('vaultix.destinations.edit');
    Route::put('/destinations/{destination}', [VaultixController::class, 'updateDestination'])->name('vaultix.destinations.update');
    Route::delete('/destinations/{destination}', [VaultixController::class, 'destroyDestination'])->name('vaultix.destinations.destroy');
    Route::post('/destinations/{destination}/test', [VaultixController::class, 'testConnection'])->name('vaultix.destinations.test');
    
    // Jobs
    Route::post('/run/{job}', [VaultixController::class, 'runNow'])->name('vaultix.run');

    // Google OAuth Helpers
    Route::get('/auth/google/redirect', [VaultixController::class, 'redirectToGoogle'])->name('vaultix.auth.google.redirect');
    Route::get('/auth/google/callback', [VaultixController::class, 'handleGoogleCallback'])->name('vaultix.auth.google.callback');

    // Backup History Management
    Route::get('/backups/latest-id', [VaultixController::class, 'getLatestBackupId'])->name('vaultix.backups.latest-id');
    Route::get('/backups/{backup}/download', [VaultixController::class, 'downloadBackup'])->name('vaultix.backups.download')->middleware('signed');
    Route::delete('/backups/{backup}', [VaultixController::class, 'destroyBackup'])->name('vaultix.backups.destroy');

    // Settings & Access Control
    Route::get('/settings', [VaultixController::class, 'settings'])->name('vaultix.settings');
    Route::post('/settings/authorized-emails', [VaultixController::class, 'updateAuthorizedEmails'])->name('vaultix.settings.emails');
    Route::delete('/settings/authorized-emails', [VaultixController::class, 'removeAuthorizedEmail'])->name('vaultix.settings.emails.remove');
    Route::post('/settings/threshold', [VaultixController::class, 'updateThreshold'])->name('vaultix.settings.threshold');
    Route::post('/settings/timezone', [VaultixController::class, 'updateTimezone'])->name('vaultix.settings.timezone');
    Route::post('/settings/log-retention', [VaultixController::class, 'updateLogRetention'])->name('vaultix.settings.log_retention');
    Route::get('/activities', [VaultixController::class, 'activities'])->name('vaultix.activities');
    Route::get('/activities/export', [VaultixController::class, 'exportActivities'])->name('vaultix.activities.export');
});
