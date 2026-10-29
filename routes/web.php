<?php

use App\Http\Controllers\AgentAccountController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// publik: dashboard read-only, aksi tulis tetap di grup auth di bawah
Route::get('/dashboard', DashboardController::class)->name('dashboard');

// semua role
Route::middleware(['auth', 'role:admin,user,auditor'])->group(function () {
    Route::get('/kpi', KpiController::class)->name('kpi');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // profile
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // agent: profile (JSON) untuk semua role
    Route::get('/agents/{agent}', [AgentController::class, 'show'])
        ->whereNumber('agent')->name('agents.show');

    // agent & akun platform: tulis hanya admin & user
    Route::middleware('role:admin,user')->group(function () {
        Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
        Route::patch('/agents/{agent}', [AgentController::class, 'update'])
            ->whereNumber('agent')->name('agents.update');
        Route::delete('/agents/{agent}', [AgentController::class, 'destroy'])
            ->whereNumber('agent')->name('agents.destroy');

        Route::post('/agents/{agent}/accounts', [AgentAccountController::class, 'store'])
            ->whereNumber('agent')->name('agents.accounts.store');
        Route::patch('/agents/{agent}/accounts/{account}', [AgentAccountController::class, 'update'])
            ->whereNumber('agent')->whereNumber('account')->name('agents.accounts.update');
        Route::delete('/agents/{agent}/accounts/{account}', [AgentAccountController::class, 'destroy'])
            ->whereNumber('agent')->whereNumber('account')->name('agents.accounts.destroy');
    });
});

require __DIR__.'/auth.php';
