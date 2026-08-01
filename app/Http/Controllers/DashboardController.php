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

        // Mobile Device Detection: If user is on a mobile phone, render Quick Hub by default
        $userAgent = strtolower($request->header('User-Agent', ''));
        $isMobile = preg_match('/(android|iphone|ipod|blackberry|iemobile|opera mini|mobile)/i', $userAgent);

        if ($isMobile) {
            return (new QuickViewController())->index($request);
        }

        $accounts = Account::where('user_id', $user->id)->get();
        $totalNetWorth = (float) $accounts->sum('balance');

        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with(['account', 'destinationAccount', 'category', 'receipt'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();

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

        $topCategories = Transaction::where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category_name, categories.color as color, SUM(transactions.amount) as total_amount')
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total_amount')
            ->take(5)
            ->get();

        $categories = Category::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->get();

        return Inertia::render('Dashboard', [
            'accounts' => $accounts,
            'totalNetWorth' => $totalNetWorth,
            'recentTransactions' => $recentTransactions,
            'monthlyExpenses' => $monthlyExpenses,
            'monthlyIncome' => $monthlyIncome,
            'topCategories' => $topCategories,
            'categories' => $categories,
        ]);
    }
}
