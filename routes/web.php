<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    // Auth Routes
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Public SmartSplit Live Group Session Routes (No Login Required)
    Route::get('/receipts/session/{token}', [ReceiptController::class, 'showGroupSession'])->name('receipts.session.show');
    Route::post('/receipts/session/{token}/claim', [ReceiptController::class, 'claimGroupSessionItems'])->name('receipts.session.claim');
    Route::delete('/receipts/session/{token}/claim/{claim}', [ReceiptController::class, 'deleteGroupSessionClaim'])->name('receipts.session.delete');

    // Authenticated Application Routes
    Route::middleware(['auth'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::redirect('/quick', '/');

        // Accounts Management
        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::put('/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
        Route::post('/accounts/{account}/toggle-pin', [DashboardController::class, 'togglePin'])->name('accounts.toggle-pin');
        Route::delete('/accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');

        // Core Financial Ledger & Transactions
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
        Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

        // SmartSplit Receipt Parser
        Route::get('/receipts', [ReceiptController::class, 'index'])->name('receipts.index');
        Route::post('/receipts/upload', [ReceiptController::class, 'upload'])->name('receipts.upload');
        Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
        Route::delete('/receipts/{receipt}', [ReceiptController::class, 'destroy'])->name('receipts.destroy');
        Route::post('/receipts/{receipt}/split', [ReceiptController::class, 'calculateSplit'])->name('receipts.split');
        Route::post('/receipts/{receipt}/claim', [ReceiptController::class, 'saveClaimedExpense'])->name('receipts.claim');
        Route::post('/receipts/{receipt}/create-session', [ReceiptController::class, 'createSession'])->name('receipts.create-session');
        Route::delete('/receipts/{receipt}/claims/{claim}', [ReceiptController::class, 'undoOwnerClaim'])->name('receipts.undo-claim');

        // Analytics & Categorization
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::post('/categories', [App\Http\Controllers\CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [App\Http\Controllers\CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [App\Http\Controllers\CategoryController::class, 'destroy'])->name('categories.destroy');

        // Subscriptions & Shared Bills Management
        Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('/subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
        Route::put('/subscriptions/{subscription}', [SubscriptionController::class, 'update'])->name('subscriptions.update');
        Route::delete('/subscriptions/{subscription}', [SubscriptionController::class, 'destroy'])->name('subscriptions.destroy');
        Route::post('/subscriptions/{subscription}/members', [SubscriptionController::class, 'storeMember'])->name('subscriptions.members.store');
        Route::delete('/subscription-members/{member}', [SubscriptionController::class, 'destroyMember'])->name('subscriptions.members.destroy');
        Route::post('/subscriptions/{subscription}/log-expense', [SubscriptionController::class, 'logMasterExpense'])->name('subscriptions.log-expense');
        Route::post('/subscriptions-log-payment', [SubscriptionController::class, 'logPayment'])->name('subscriptions.log-payment');

        // Settings & Device Security
        Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/preferences', [App\Http\Controllers\SettingsController::class, 'updatePreferences'])->name('settings.preferences.update');
        Route::delete('/settings/sessions/{session}', [App\Http\Controllers\SettingsController::class, 'destroySession'])->name('settings.sessions.destroy');
        Route::post('/settings/sessions/logout-others', [App\Http\Controllers\SettingsController::class, 'logoutOtherDevices'])->name('settings.sessions.logout-others');

        // Superuser Account Management
        Route::get('/admin/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/admin/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/admin/users/{user}/switch', [UserController::class, 'switchUser'])->name('users.switch');
    });
});
