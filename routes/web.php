<?php

use App\Http\Controllers\AgentAccountController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// semua role
Route::middleware(['auth', 'role:admin,user,auditor'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // profile
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // agent: baca untuk semua role
    Route::resource('agents', AgentController::class)->only(['index', 'show', 'create', 'edit']);

    // agent & akun platform: tulis hanya admin & user
    Route::middleware('role:admin,user')->group(function () {
        Route::resource('agents', AgentController::class)->only(['store', 'update', 'destroy']);

        Route::post('/agents/{agent}/accounts', [AgentAccountController::class, 'store'])->name('agents.accounts.store');
        Route::patch('/agents/{agent}/accounts/{account}', [AgentAccountController::class, 'update'])->name('agents.accounts.update');
        Route::delete('/agents/{agent}/accounts/{account}', [AgentAccountController::class, 'destroy'])->name('agents.accounts.destroy');
    });
});

require __DIR__.'/auth.php';
