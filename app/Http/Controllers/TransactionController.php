<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Transaction::where('user_id', $user->id)
            ->with(['account', 'destinationAccount', 'category', 'receipt'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('account_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('account_id', $request->input('account_id'))
                  ->orWhere('destination_account_id', $request->input('account_id'));
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('notes', 'like', "%{$search}%");
        }

        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->input('start_date') . ' 00:00:00');
        }

        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->input('end_date') . ' 23:59:59');
        }

        $transactions = $query->paginate(15)->withQueryString();

        $accounts = Account::where('user_id', $user->id)->get();
        $categories = Category::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->get();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'categories' => $categories,
            'filters' => $request->only(['type', 'account_id', 'category_id', 'search', 'start_date', 'end_date']),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'type' => 'required|string|in:expense,income,transfer',
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('user_id', $user->id)],
            'destination_account_id' => [
                'nullable',
                'required_if:type,transfer',
                Rule::exists('accounts', 'id')->where('user_id', $user->id),
                'different:account_id',
            ],
            'category_id' => 'nullable|exists:categories,id',
            'receipt_id' => 'nullable|exists:receipts,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['user_id'] = $user->id;
        if ($validated['type'] === 'transfer') {
            $validated['category_id'] = null;
        } else {
            $validated['destination_account_id'] = null;
        }

        $this->ledgerService->createTransaction($validated);

        return redirect()->back()->with('success', 'Transaction logged successfully.');
    }

    public function update(Request $request, Transaction $transaction)
    {
        $this->authorizeOwner($request, $transaction);
        $user = $request->user();

        $validated = $request->validate([
            'type' => 'required|string|in:expense,income,transfer',
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('user_id', $user->id)],
            'destination_account_id' => [
                'nullable',
                'required_if:type,transfer',
                Rule::exists('accounts', 'id')->where('user_id', $user->id),
                'different:account_id',
            ],
            'category_id' => 'nullable|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validated['type'] === 'transfer') {
            $validated['category_id'] = null;
        } else {
            $validated['destination_account_id'] = null;
        }

        $this->ledgerService->updateTransaction($transaction, $validated);

        return redirect()->back()->with('success', 'Transaction updated successfully.');
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        $this->authorizeOwner($request, $transaction);

        $this->ledgerService->deleteTransaction($transaction);

        return redirect()->back()->with('success', 'Transaction deleted successfully.');
    }

    protected function authorizeOwner(Request $request, Transaction $transaction): void
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized transaction action.');
        }
    }
}
