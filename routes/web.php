<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ScreenshotController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    // Login
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Register
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Dashboard
    Route::get('/dashboard', function () {
        if (auth()->user()->isAdmin()) {
            return redirect('/admin/dashboard');
        }
        return view('employee.dashboard');
    })->name('dashboard');

    // Employee screenshot upload
    Route::post('/screenshot/store', [ScreenshotController::class, 'store'])
        ->name('screenshot.store');
});

// Admin routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::group(['middleware' => function ($request, $next) {
        if (!auth()->user()->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access.');
        }
        return $next($request);
    }], function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/filter', [AdminController::class, 'filter'])->name('filter');

        // IMPORTANT: This route must come BEFORE the cleanup route
        Route::get('/screenshot/{path}', [ScreenshotController::class, 'getScreenshot'])
            ->where('path', '.*') // Allow slashes in path
            ->name('screenshot.view');

        Route::post('/cleanup', [AdminController::class, 'deleteOldScreenshots'])
            ->name('cleanup');
    });
});
