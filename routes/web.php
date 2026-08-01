<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReceiptController;
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

    // Debug Route for PHP & Upload Limits
    Route::get('/debug-limits', function () {
        return response()->json([
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size'        => ini_get('post_max_size'),
            'memory_limit'         => ini_get('memory_limit'),
            'upload_tmp_dir'       => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
        ]);
    });

    // Authenticated Application Routes
    Route::middleware(['auth'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Accounts Management
        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::put('/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
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
        Route::post('/receipts/{receipt}/split', [ReceiptController::class, 'calculateSplit'])->name('receipts.split');
        Route::post('/receipts/{receipt}/claim', [ReceiptController::class, 'saveClaimedExpense'])->name('receipts.claim');
        Route::post('/receipts/{receipt}/create-session', [ReceiptController::class, 'createSession'])->name('receipts.create-session');
        Route::delete('/receipts/{receipt}/claims/{claim}', [ReceiptController::class, 'undoOwnerClaim'])->name('receipts.undo-claim');

        // Analytics & Categorization
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

        // Superuser Account Management
        Route::get('/admin/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/admin/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/admin/users/{user}/switch', [UserController::class, 'switchUser'])->name('users.switch');
    });
});
