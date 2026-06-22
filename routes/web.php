<?php

use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TrackingController;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('forgot-password', [AuthController::class, 'forgot'])->name('password.email');
    Route::get('reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('reset-password', [AuthController::class, 'reset'])->name('password.update');
});

Route::prefix('admin')
    ->middleware(['auth'])
    ->name('admin.')
    ->group(function () {
        Route::get('/tracking', [TrackingController::class, 'index'])
            ->name('tracking.index');

        Route::get('/tracking/history', [TrackingController::class, 'history'])
            ->name('tracking.history');
    });

Route::middleware(['auth', 'active', 'force.logout'])->group(function () {
    Route::redirect('/', '/dashboard');
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('change-password', [AuthController::class, 'changePassword'])->name('password.change');
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('resources/{module}', [ResourceController::class, 'index'])->name('resources.index');
        Route::get('resources/{module}/create', [ResourceController::class, 'create'])->name('resources.create');
        Route::post('resources/{module}', [ResourceController::class, 'store'])->name('resources.store');
        Route::get('resources/{module}/{id}', [ResourceController::class, 'show'])->name('resources.show');
        Route::get('resources/{module}/{id}/edit', [ResourceController::class, 'edit'])->name('resources.edit');
        Route::put('resources/{module}/{id}', [ResourceController::class, 'update'])->name('resources.update');
        Route::delete('resources/{module}/{id}', [ResourceController::class, 'destroy'])->name('resources.destroy');
        Route::post('users/{user}/{action}', [ResourceController::class, 'userAction'])->name('users.action');
        Route::get('tickets', [SupportController::class, 'index'])->name('tickets.index');
        Route::get('tickets/{ticket}', [SupportController::class, 'show'])->name('tickets.show');
        Route::patch('tickets/{ticket}', [SupportController::class, 'update'])->name('tickets.update');
        Route::post('tickets/{ticket}/reply', [SupportController::class, 'reply'])->name('tickets.reply');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{type}/{format}', [ReportController::class, 'export'])->name('reports.export');
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});
