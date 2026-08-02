<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $hasCategory = Schema::hasColumn('accounts', 'category');
        $hasSortOrder = Schema::hasColumn('accounts', 'sort_order');

        $query = Account::where('user_id', $user->id)
            ->withCount(['outgoingTransactions', 'incomingTransfers']);

        if ($hasSortOrder) {
            $query->orderBy('sort_order', 'asc');
        }
        $query->orderBy('created_at', 'desc');
        $accounts = $query->get();

        $totalNetWorth = (float) $accounts->sum('balance');

        $categoryTotals = ['current' => $totalNetWorth, 'savings' => 0, 'other' => 0];
        if ($hasCategory) {
            $categoryTotals = [
                'current' => (float) $accounts->where('category', 'current')->sum('balance'),
                'savings' => (float) $accounts->where('category', 'savings')->sum('balance'),
                'other' => (float) $accounts->whereNotIn('category', ['current', 'savings'])->sum('balance'),
            ];
        }

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
            'totalNetWorth' => $totalNetWorth,
            'categoryTotals' => $categoryTotals,
        ]);
    }

    public function store(Request $request)
    {
        $hasCategory = Schema::hasColumn('accounts', 'category');
        $hasSortOrder = Schema::hasColumn('accounts', 'sort_order');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:bank,e-wallet,cash,credit_card',
            'category' => 'nullable|string|in:current,savings,wallet,credit_card,investment',
            'currency' => 'required|string|max:10',
            'initial_balance' => 'required|numeric',
            'color' => 'required|string',
            'icon' => 'required|string',
        ]);

        // Only set category if column exists
        if ($hasCategory) {
            if (empty($validated['category'])) {
                $validated['category'] = match ($validated['type']) {
                    'e-wallet' => 'wallet',
                    'credit_card' => 'credit_card',
                    default => 'current',
                };
            }
        } else {
            unset($validated['category']);
        }

        $validated['user_id'] = $request->user()->id;
        $validated['balance'] = $validated['initial_balance'];

        if ($hasSortOrder) {
            $validated['sort_order'] = (Account::where('user_id', $request->user()->id)->max('sort_order') ?? 0) + 1;
        }

        Account::create($validated);

        return redirect()->back()->with('success', 'Account created successfully.');
    }

    public function update(Request $request, Account $account)
    {
        $this->authorizeOwner($request, $account);
        $hasCategory = Schema::hasColumn('accounts', 'category');

        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:bank,e-wallet,cash,credit_card',
            'currency' => 'required|string|max:10',
            'color' => 'required|string',
            'icon' => 'required|string',
        ];

        if ($hasCategory) {
            $rules['category'] = 'nullable|string|in:current,savings,wallet,credit_card,investment';
        }

        $validated = $request->validate($rules);

        // Strip category from update if column doesn't exist
        if (!$hasCategory) {
            unset($validated['category']);
        }

        $account->update($validated);

        return redirect()->back()->with('success', 'Account updated successfully.');
    }

    public function reorder(Request $request)
    {
        if (!Schema::hasColumn('accounts', 'sort_order')) {
            return redirect()->back()->with('info', 'Reordering not available yet. Please wait for database migration.');
        }

        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:accounts,id',
            'orders.*.sort_order' => 'required|integer',
        ]);

        $user = $request->user();

        foreach ($validated['orders'] as $order) {
            Account::where('id', $order['id'])
                ->where('user_id', $user->id)
                ->update(['sort_order' => $order['sort_order']]);
        }

        return redirect()->back()->with('success', 'Account order updated.');
    }

    public function destroy(Request $request, Account $account)
    {
        $this->authorizeOwner($request, $account);

        $account->delete();

        return redirect()->back()->with('success', 'Account deleted successfully.');
    }

    protected function authorizeOwner(Request $request, Account $account): void
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized account access.');
        }
    }
}
