<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Self-healing schema check for accounts table
        if (!\Illuminate\Support\Facades\Schema::hasColumn('accounts', 'is_pinned')) {
            \Illuminate\Support\Facades\Schema::table('accounts', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->boolean('is_pinned')->default(false);
            });
        }

        $query = Account::where('user_id', $user->id);
        if (\Illuminate\Support\Facades\Schema::hasColumn('accounts', 'sort_order')) {
            $query->orderBy('sort_order', 'asc');
        }
        $allAccounts = $query->orderBy('created_at', 'desc')->get();
        $totalNetWorth = (float) $allAccounts->sum('balance');

        // Pinned accounts
        $pinned = $allAccounts->where('is_pinned', true);
        $pinnedAccounts = $pinned->isEmpty() ? $allAccounts->take(3) : $pinned->values();

        // Daily spending calculations
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $todaySpending = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereDate('date', $today)
            ->sum('amount');

        $yesterdaySpending = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereDate('date', $yesterday)
            ->sum('amount');

        // Monthly calculations
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthlyExpenses = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with(['account', 'destinationAccount', 'category', 'receipt'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();

        $topCategories = Transaction::where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category_name, categories.color as color, SUM(transactions.amount) as total_amount')
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total_amount')
            ->take(5)
            ->get();

        Category::ensureSystemCategories();
        $categories = Category::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->get();

        return Inertia::render('Dashboard', [
            'accounts' => $allAccounts,
            'pinnedAccounts' => $pinnedAccounts,
            'totalNetWorth' => $totalNetWorth,
            'todaySpending' => $todaySpending,
            'yesterdaySpending' => $yesterdaySpending,
            'recentTransactions' => $recentTransactions,
            'monthlyExpenses' => $monthlyExpenses,
            'monthlyIncome' => $monthlyIncome,
            'topCategories' => $topCategories,
            'categories' => $categories,
        ]);
    }

    public function togglePin(Request $request, Account $account)
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized account action.');
        }

        // Self-healing schema check for SQLite database
        if (!\Illuminate\Support\Facades\Schema::hasColumn('accounts', 'is_pinned')) {
            \Illuminate\Support\Facades\Schema::table('accounts', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->boolean('is_pinned')->default(false);
            });
        }

        $account->update(['is_pinned' => !$account->is_pinned]);

        return redirect()->back()->with(
            'success',
            $account->is_pinned
                ? "'{$account->name}' pinned to Overview."
                : "'{$account->name}' unpinned from Overview."
        );
    }
}
