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

Route::get('/debug-recovery', function () {
    $output = "=== DEEP DATABASE RECOVERY SEARCH ===\n";
    $searchDirs = ['/app/database', '/app/storage', '/tmp', '/var/tmp', '/app'];
    $foundFiles = [];
    foreach ($searchDirs as $dir) {
        if (is_dir($dir)) {
            try {
                $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
                foreach ($rii as $file) {
                    if ($file->isDir()) continue;
                    $path = $file->getPathname();
                    if (preg_match('/\.(sqlite|db|bak|sql)/i', $path)) {
                        $size = filesize($path);
                        $foundFiles[] = "$path ($size bytes)";
                        try {
                            $pdo = new \PDO("sqlite:" . $path);
                            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(\PDO::FETCH_COLUMN);
                            $foundFiles[] = "   -> Tables: " . implode(', ', $tables);
                            if (in_array('transactions', $tables)) {
                                $txCount = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
                                $foundFiles[] = "   -> Transactions Count: " . $txCount;
                            }
                        } catch (\Throwable $t) {}
                    }
                }
            } catch (\Throwable $e) {}
        }
    }
    $output .= implode("\n", array_unique($foundFiles)) . "\n";
    return response($output, 200)->header('Content-Type', 'text/plain');
})->withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    \App\Http\Middleware\HandleInertiaRequests::class,
]);

Route::get('/debug-migrate', function () {
    try {
        $output = '';
        
        // 0. Deep Recovery Search for previous SQLite/database files
        $output .= "=== DEEP DATABASE RECOVERY SEARCH ===\n";
        $searchDirs = ['/app/database', '/app/storage', '/tmp', '/var/tmp', '/app'];
        $foundFiles = [];
        foreach ($searchDirs as $dir) {
            if (is_dir($dir)) {
                $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
                foreach ($rii as $file) {
                    if ($file->isDir()) continue;
                    $path = $file->getPathname();
                    if (preg_match('/\.(sqlite|db|bak|sql)/i', $path)) {
                        $size = filesize($path);
                        $foundFiles[] = "$path ($size bytes)";
                        // Try querying sqlite table counts
                        try {
                            $pdo = new \PDO("sqlite:" . $path);
                            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(\PDO::FETCH_COLUMN);
                            $foundFiles[] = "   -> Tables: " . implode(', ', $tables);
                            if (in_array('transactions', $tables)) {
                                $txCount = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
                                $foundFiles[] = "   -> Transactions Count: " . $txCount;
                            }
                        } catch (\Throwable $t) {}
                    }
                }
            }
        }
        $output .= implode("\n", array_unique($foundFiles)) . "\n\n";

        // 1. Run status
        \Illuminate\Support\Facades\Artisan::call('migrate:status');
        $output .= "=== MIGRATION STATUS ===\n" . \Illuminate\Support\Facades\Artisan::output() . "\n";
        
        // 2. Run migrate & seed
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output .= "=== MIGRATION RUN ===\n" . \Illuminate\Support\Facades\Artisan::output() . "\n";
        
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        $output .= "=== DB SEED RUN ===\n" . \Illuminate\Support\Facades\Artisan::output() . "\n";

        // 3. User & Record Counts
        $output .= "=== TABLE COUNTS ===\n";
        $output .= "Users: " . \App\Models\User::count() . "\n";
        $output .= "Accounts: " . \App\Models\Account::count() . "\n";
        $output .= "Transactions: " . \App\Models\Transaction::count() . "\n";
        $output .= "Receipts: " . \App\Models\Receipt::count() . "\n";
        
        return response($output, 200)->header('Content-Type', 'text/plain');
    } catch (\Throwable $e) {
        return response("Error: " . $e->getMessage() . "\n\nStack:\n" . $e->getTraceAsString(), 500)->header('Content-Type', 'text/plain');
    }
})->withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \App\Http\Middleware\HandleInertiaRequests::class,
]);

Route::middleware(['web'])->group(function () {
    // Auth Routes
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/passkeys/login/options', [\App\Http\Controllers\PasskeyController::class, 'loginOptions']);
    Route::post('/passkeys/login', [\App\Http\Controllers\PasskeyController::class, 'login']);

    // Public SmartSplit Live Group Session Routes (No Login Required)
    Route::get('/receipts/session/{token}', [ReceiptController::class, 'showGroupSession'])->name('receipts.session.show');
    Route::post('/receipts/session/{token}/claim', [ReceiptController::class, 'claimGroupSessionItems'])->name('receipts.session.claim');
    Route::delete('/receipts/session/{token}/claim/{claim}', [ReceiptController::class, 'deleteGroupSessionClaim'])->name('receipts.session.delete');

    // Authenticated Application Routes
    Route::middleware(['auth'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // Passkey Registration & Management
        Route::get('/passkeys/register/options', [\App\Http\Controllers\PasskeyController::class, 'registerOptions']);
        Route::post('/passkeys/register', [\App\Http\Controllers\PasskeyController::class, 'register']);
        Route::delete('/passkeys/{passkey}', [\App\Http\Controllers\PasskeyController::class, 'destroy'])->name('passkeys.destroy');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::redirect('/quick', '/');

        // Accounts Management
        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::post('/accounts/reorder', [AccountController::class, 'reorder'])->name('accounts.reorder');
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
        Route::get('/settings/backup/export', [App\Http\Controllers\SettingsController::class, 'exportBackup'])->name('settings.backup.export');
        Route::post('/settings/backup/restore', [App\Http\Controllers\SettingsController::class, 'restoreBackup'])->name('settings.backup.restore');

        // Superuser Account Management
        Route::get('/admin/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/admin/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/admin/users/{user}/switch', [UserController::class, 'switchUser'])->name('users.switch');
    });
});
