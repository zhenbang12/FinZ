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
        $accounts = Account::where('user_id', $user->id)
            ->withCount(['outgoingTransactions', 'incomingTransfers'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalNetWorth = (float) $accounts->sum('balance');

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
            'totalNetWorth' => $totalNetWorth,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:bank,e-wallet,cash,credit_card',
            'currency' => 'required|string|max:10',
            'initial_balance' => 'required|numeric',
            'color' => 'required|string',
            'icon' => 'required|string',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['balance'] = $validated['initial_balance'];

        Account::create($validated);

        return redirect()->back()->with('success', 'Account created successfully.');
    }

    public function update(Request $request, Account $account)
    {
        $this->authorizeOwner($request, $account);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:bank,e-wallet,cash,credit_card',
            'currency' => 'required|string|max:10',
            'color' => 'required|string',
            'icon' => 'required|string',
        ]);

        $account->update($validated);

        return redirect()->back()->with('success', 'Account updated successfully.');
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
