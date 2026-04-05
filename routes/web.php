<?php

declare(strict_types=1);

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetPresetController;
use App\Http\Controllers\CategorizationRuleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\ScheduledTransactionController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WalletTransferController;
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

    Route::get('/budget-presets', [BudgetPresetController::class, 'index'])->name('budget-presets.index');
    Route::post('/budget-presets', [BudgetPresetController::class, 'store'])->name('budget-presets.store');
    Route::put('/budget-presets/{budgetPreset}', [BudgetPresetController::class, 'update'])->name('budget-presets.update');
    Route::delete('/budget-presets/{budgetPreset}', [BudgetPresetController::class, 'destroy'])->name('budget-presets.destroy');
    Route::patch('/budget-presets/reorder', [BudgetPresetController::class, 'reorder'])->name('budget-presets.reorder');

    Route::get('/plan', [PlanController::class, 'index'])->name('plan.index');
    Route::post('/plan/upgrade', [PlanController::class, 'upgrade'])->name('plan.upgrade');
    Route::post('/plan/downgrade', [PlanController::class, 'downgrade'])->name('plan.downgrade');

    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');
    Route::get('/overview', [OverviewController::class, 'index'])->name('overview.index');
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::get('/import/template', [ImportController::class, 'template'])->name('import.template');
    Route::post('/import/preview', [ImportController::class, 'preview'])->name('import.preview');
    Route::post('/import/process', [ImportController::class, 'process'])->name('import.process');
    Route::get('/goals', [GoalController::class, 'index'])->name('goals.index');
    Route::post('/goals', [GoalController::class, 'store'])->name('goals.store');
    Route::put('/goals/{goal}', [GoalController::class, 'update'])->name('goals.update');
    Route::post('/goals/{goal}/deposit', [GoalController::class, 'deposit'])->name('goals.deposit');
    Route::delete('/goals/{goal}', [GoalController::class, 'destroy'])->name('goals.destroy');
    Route::get('/recurring', [RecurringTransactionController::class, 'index'])->name('recurring.index');
    Route::post('/recurring', [RecurringTransactionController::class, 'store'])->name('recurring.store');
    Route::put('/recurring/{recurringTransaction}', [RecurringTransactionController::class, 'update'])->name('recurring.update');
    Route::patch('/recurring/{recurringTransaction}/toggle', [RecurringTransactionController::class, 'toggle'])->name('recurring.toggle');
    Route::delete('/recurring/{recurringTransaction}', [RecurringTransactionController::class, 'destroy'])->name('recurring.destroy');
    Route::post('/scheduled', [ScheduledTransactionController::class, 'store'])->name('scheduled.store');
    Route::put('/scheduled/{scheduledTransaction}', [ScheduledTransactionController::class, 'update'])->name('scheduled.update');
    Route::delete('/scheduled/{scheduledTransaction}', [ScheduledTransactionController::class, 'destroy'])->name('scheduled.destroy');
    Route::get('/categorization-rules', [CategorizationRuleController::class, 'index'])->name('categorization-rules.index');
    Route::get('/categorization-rules/suggest', [CategorizationRuleController::class, 'suggest'])->name('categorization-rules.suggest');
    Route::post('/categorization-rules/suggest-bulk', [CategorizationRuleController::class, 'suggestBulk'])->name('categorization-rules.suggest-bulk');
    Route::put('/categorization-rules/{categorizationRule}', [CategorizationRuleController::class, 'update'])->name('categorization-rules.update');
    Route::delete('/categorization-rules/{categorizationRule}', [CategorizationRuleController::class, 'destroy'])->name('categorization-rules.destroy');

    Route::post('/categories/quick', [CategoryController::class, 'storeQuick'])->name('categories.storeQuick');
    Route::resource('categories', CategoryController::class);
    Route::resource('transactions', TransactionController::class)->only(['store', 'update', 'destroy']);
    Route::post('/transactions/split', [TransactionController::class, 'storeSplit'])->name('transactions.split.store');
    Route::get('/transactions/{transaction}/attachment', [TransactionController::class, 'attachment'])->name('transactions.attachment');
    Route::delete('/transactions/split/{splitId}', [TransactionController::class, 'destroySplit'])->name('transactions.split.destroy');
    Route::post('/transfers', [WalletTransferController::class, 'store'])->name('transfers.store');
    Route::delete('/transfers/{transferId}', [WalletTransferController::class, 'destroy'])->name('transfers.destroy');
    Route::patch('/wallets/reorder', [WalletController::class, 'reorder'])->name('wallets.reorder');
    Route::resource('wallets', WalletController::class);
    Route::post('/wallets/{wallet}/favorite', [WalletController::class, 'toggleFavorite'])->name('wallets.favorite');

    // Budget routes (nested under wallet)
    Route::get('/wallets/{wallet}/budget', [BudgetController::class, 'show'])->name('wallets.budget.show');
    Route::post('/wallets/{wallet}/budget/copy-previous', [BudgetController::class, 'copyFromPrevious'])->name('wallets.budget.copy-previous');
    Route::post('/wallets/{wallet}/budget/items', [BudgetController::class, 'storeItem'])->name('wallets.budget.items.store');
    Route::put('/wallets/{wallet}/budget/items/{item}', [BudgetController::class, 'updateItem'])->name('wallets.budget.items.update');
    Route::delete('/wallets/{wallet}/budget/items/{item}', [BudgetController::class, 'destroyItem'])->name('wallets.budget.items.destroy');
    Route::patch('/wallets/{wallet}/budget/items/reorder', [BudgetController::class, 'reorderItems'])->name('wallets.budget.items.reorder');
    Route::get('/wallets/{wallet}/budget/items/{item}/transactions', [BudgetController::class, 'itemTransactions'])->name('wallets.budget.items.transactions');
    Route::post('/wallets/{wallet}/budget/items/{item}/duplicate', [BudgetController::class, 'duplicateItem'])->name('wallets.budget.items.duplicate');
    Route::post('/wallets/{wallet}/budget/copy-repeat', [BudgetController::class, 'copyRepeat'])->name('wallets.budget.copy-repeat');
    Route::post('/wallets/{wallet}/budget/quick-start', [BudgetController::class, 'quickStart'])->name('wallets.budget.quick-start');
    Route::delete('/wallets/{wallet}/budget/clear', [BudgetController::class, 'clearItems'])->name('wallets.budget.clear');
    Route::patch('/wallets/{wallet}/budget/notes', [BudgetController::class, 'updateNotes'])->name('wallets.budget.notes.update');
    Route::get('/wallets/{wallet}/budget/year', [BudgetController::class, 'yearView'])->name('wallets.budget.year');
});

require __DIR__.'/auth.php';
