<?php

declare(strict_types=1);

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/locale', [LocaleController::class, 'update'])->name('locale.update');

    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');
    Route::resource('categories', CategoryController::class);
    Route::resource('transactions', TransactionController::class);
    Route::resource('wallets', WalletController::class);

    // Budget routes (nested under wallet)
    Route::get('/wallets/{wallet}/budget', [BudgetController::class, 'show'])->name('wallets.budget.show');
    Route::post('/wallets/{wallet}/budget/copy-previous', [BudgetController::class, 'copyFromPrevious'])->name('wallets.budget.copy-previous');
    Route::post('/wallets/{wallet}/budget/items', [BudgetController::class, 'storeItem'])->name('wallets.budget.items.store');
    Route::put('/wallets/{wallet}/budget/items/{item}', [BudgetController::class, 'updateItem'])->name('wallets.budget.items.update');
    Route::delete('/wallets/{wallet}/budget/items/{item}', [BudgetController::class, 'destroyItem'])->name('wallets.budget.items.destroy');
    Route::get('/wallets/{wallet}/budget/items/{item}/transactions', [BudgetController::class, 'itemTransactions'])->name('wallets.budget.items.transactions');
});

require __DIR__.'/auth.php';
