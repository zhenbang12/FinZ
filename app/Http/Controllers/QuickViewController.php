<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class QuickViewController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

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

        $allAccounts = Account::where('user_id', $user->id)->get();

        // Pinned accounts (if none pinned yet, select top 3 by default)
        $pinned = $allAccounts->where('is_pinned', true);
        if ($pinned->isEmpty()) {
            $pinnedAccounts = $allAccounts->take(3);
        } else {
            $pinnedAccounts = $pinned->values();
        }

        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with(['account', 'destinationAccount', 'category'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        $categories = Category::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->get();

        return Inertia::render('QuickView', [
            'todaySpending' => $todaySpending,
            'yesterdaySpending' => $yesterdaySpending,
            'allAccounts' => $allAccounts,
            'pinnedAccounts' => $pinnedAccounts,
            'recentTransactions' => $recentTransactions,
            'categories' => $categories,
        ]);
    }

    public function togglePin(Request $request, Account $account)
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized account action.');
        }

        $account->update(['is_pinned' => !$account->is_pinned]);

        return redirect()->back()->with(
            'success',
            $account->is_pinned
                ? "'{$account->name}' pinned to Quick Hub."
                : "'{$account->name}' unpinned from Quick Hub."
        );
    }
}
