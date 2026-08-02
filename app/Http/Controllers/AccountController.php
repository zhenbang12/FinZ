<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\LedgerService;
use Illuminate\Http\Request;
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

        if (!\Illuminate\Support\Facades\Schema::hasColumn('accounts', 'category')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {}
        }

        $accounts = Account::where('user_id', $user->id)
            ->withCount(['outgoingTransactions', 'incomingTransfers'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalNetWorth = (float) $accounts->sum('balance');
        $currentTotal = (float) $accounts->where('category', 'current')->sum('balance');
        $savingsTotal = (float) $accounts->where('category', 'savings')->sum('balance');
        $otherTotal = (float) $accounts->whereNotIn('category', ['current', 'savings'])->sum('balance');

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
            'totalNetWorth' => $totalNetWorth,
            'categoryTotals' => [
                'current' => $currentTotal,
                'savings' => $savingsTotal,
                'other' => $otherTotal,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:bank,e-wallet,cash,credit_card',
            'category' => 'nullable|string|in:current,savings,wallet,credit_card,investment',
            'currency' => 'required|string|max:10',
            'initial_balance' => 'required|numeric',
            'color' => 'required|string',
            'icon' => 'required|string',
        ]);

        if (empty($validated['category'])) {
            $validated['category'] = match ($validated['type']) {
                'e-wallet' => 'wallet',
                'credit_card' => 'credit_card',
                default => 'current',
            };
        }

        $validated['user_id'] = $request->user()->id;
        $validated['balance'] = $validated['initial_balance'];
        $validated['sort_order'] = (Account::where('user_id', $request->user()->id)->max('sort_order') ?? 0) + 1;

        Account::create($validated);

        return redirect()->back()->with('success', 'Account created successfully.');
    }

    public function update(Request $request, Account $account)
    {
        $this->authorizeOwner($request, $account);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:bank,e-wallet,cash,credit_card',
            'category' => 'required|string|in:current,savings,wallet,credit_card,investment',
            'currency' => 'required|string|max:10',
            'color' => 'required|string',
            'icon' => 'required|string',
        ]);

        $account->update($validated);

        return redirect()->back()->with('success', 'Account updated successfully.');
    }

    public function reorder(Request $request)
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('accounts', 'sort_order')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {}
        }

        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:accounts,id',
            'orders.*.sort_order' => 'required|integer',
        ]);

        $user = $request->user();

        if (\Illuminate\Support\Facades\Schema::hasColumn('accounts', 'sort_order')) {
            foreach ($validated['orders'] as $order) {
                Account::where('id', $order['id'])
                    ->where('user_id', $user->id)
                    ->update(['sort_order' => $order['sort_order']]);
            }
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
