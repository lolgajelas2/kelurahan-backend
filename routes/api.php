<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LayananController;
use App\Http\Controllers\Api\PermohonanController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\KontakController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\BerkasController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::get('/layanan', [LayananController::class, 'index']);
Route::get('/layanan/{id}', [LayananController::class, 'show']);
Route::post('/permohonan', [PermohonanController::class, 'store']);
Route::post('/status/check', [StatusController::class, 'check']);
Route::post('/kontak', [KontakController::class, 'store']);
Route::post('/berkas', [BerkasController::class, 'store']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Download berkas (hanya untuk user yang login)
    Route::get('/berkas/download/{id}', [BerkasController::class, 'download']);
    
    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    
    // Admin routes
    Route::middleware('admin')->group(function () {
        // Layanan management
        Route::post('/layanan', [LayananController::class, 'store']);
        Route::put('/layanan/{id}', [LayananController::class, 'update']);
        Route::delete('/layanan/{id}', [LayananController::class, 'destroy']);
        
        // User management routes would go here
        Route::get('/users', [UserController::class, 'index']); // <- inilah endpoint ambil data user admin
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });
    
    // Permohonan management (admin & petugas)
    Route::get('/permohonan', [PermohonanController::class, 'index']);
    Route::get('/permohonan/{id}', [PermohonanController::class, 'show']);
    Route::put('/permohonan/{id}/status', [PermohonanController::class, 'updateStatus']);
    Route::post('/permohonan/bulk-update-status', [PermohonanController::class, 'bulkUpdateStatus']);
    Route::delete('/permohonan/bulk-delete', [PermohonanController::class, 'bulkDelete']);
    
    // Berkas management
    Route::get('/berkas/{id}', [BerkasController::class, 'show']);
    Route::delete('/berkas/{id}', [BerkasController::class, 'destroy']);
    
    // Kontak management
    Route::get('/kontak', [KontakController::class, 'index']);
    Route::get('/kontak/{id}', [KontakController::class, 'show']);
    Route::put('/kontak/{id}', [KontakController::class, 'update']);
    Route::delete('/kontak/{id}', [KontakController::class, 'destroy']);
    Route::post('/kontak/{id}/reply', [KontakController::class, 'reply']);
});
