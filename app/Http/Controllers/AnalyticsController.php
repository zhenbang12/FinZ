<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $period = $request->input('period', 'monthly'); // daily, weekly, monthly, yearly
        $now = Carbon::now();
        if ($period === 'daily') {
            $startDate = $now->copy()->startOfDay();
            $endDate = $now->copy()->endOfDay();
        } elseif ($period === 'weekly') {
            $startDate = $now->copy()->startOfWeek();
            $endDate = $now->copy()->endOfWeek();
        } elseif ($period === 'yearly') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfYear();
        } else { // monthly default
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfMonth();
        }

        // Category breakdown (Expenses only)
        $categoryBreakdown = Transaction::where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category_name, categories.color as color, SUM(transactions.amount) as total_amount')
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total_amount')
            ->get();

        // Monthly trends for the past 6 months
        $monthlyTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $mStart = $now->copy()->subMonths($i)->startOfMonth();
            $mEnd = $now->copy()->subMonths($i)->endOfMonth();

            $expenseSum = Transaction::where('user_id', $user->id)
                ->where('type', 'expense')
                ->whereBetween('date', [$mStart, $mEnd])
                ->sum('amount');

            $incomeSum = Transaction::where('user_id', $user->id)
                ->where('type', 'income')
                ->whereBetween('date', [$mStart, $mEnd])
                ->sum('amount');

            $monthlyTrends[] = [
                'month' => $mStart->format('M Y'),
                'expense' => (float) $expenseSum,
                'income' => (float) $incomeSum,
            ];
        }

        $totalExpenses = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $totalIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        return Inertia::render('Analytics/Index', [
            'period' => $period,
            'categoryBreakdown' => $categoryBreakdown,
            'monthlyTrends' => $monthlyTrends,
            'totalExpenses' => $totalExpenses,
            'totalIncome' => $totalIncome,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
        ]);
    }
}
