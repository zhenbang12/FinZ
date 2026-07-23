<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LedgerService
{
    /**
     * Log a manual transaction (expense, income, or transfer) with ACID compliance.
     */
    public function createTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = Transaction::create($data);
            $this->syncAccountBalancesForTransaction($transaction);
            return $transaction;
        });
    }

    /**
     * Update an existing transaction and adjust balances accordingly.
     */
    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            // Revert original transaction balance impact
            $this->revertAccountBalancesForTransaction($transaction);

            // Update transaction attributes
            $transaction->update($data);
            $transaction->refresh();

            // Apply new transaction balance impact
            $this->syncAccountBalancesForTransaction($transaction);

            return $transaction;
        });
    }

    /**
     * Delete a transaction and revert its balance impact.
     */
    public function deleteTransaction(Transaction $transaction): bool
    {
        return DB::transaction(function () use ($transaction) {
            $this->revertAccountBalancesForTransaction($transaction);
            return $transaction->delete();
        });
    }

    /**
     * Recalculate account balance based on initial balance and all past transactions.
     */
    public function recalculateAccountBalance(Account $account): Account
    {
        return DB::transaction(function () use ($account) {
            $outgoingExpenses = Transaction::where('account_id', $account->id)
                ->where('type', 'expense')
                ->sum('amount');

            $incomingIncome = Transaction::where('account_id', $account->id)
                ->where('type', 'income')
                ->sum('amount');

            $outgoingTransfers = Transaction::where('account_id', $account->id)
                ->where('type', 'transfer')
                ->sum('amount');

            $incomingTransfers = Transaction::where('destination_account_id', $account->id)
                ->where('type', 'transfer')
                ->sum('amount');

            $newBalance = $account->initial_balance + $incomingIncome + $incomingTransfers - $outgoingExpenses - $outgoingTransfers;
            $account->update(['balance' => $newBalance]);

            return $account;
        });
    }

    /**
     * Apply transaction effects to account balances.
     */
    protected function syncAccountBalancesForTransaction(Transaction $transaction): void
    {
        $amount = (float) $transaction->amount;
        $originAccount = Account::findOrFail($transaction->account_id);

        if ($transaction->type === 'expense') {
            $originAccount->decrement('balance', $amount);
        } elseif ($transaction->type === 'income') {
            $originAccount->increment('balance', $amount);
        } elseif ($transaction->type === 'transfer') {
            if (!$transaction->destination_account_id) {
                throw new InvalidArgumentException("Destination account is required for transfers.");
            }
            $destAccount = Account::findOrFail($transaction->destination_account_id);
            $originAccount->decrement('balance', $amount);
            $destAccount->increment('balance', $amount);
        }
    }

    /**
     * Revert transaction effects from account balances.
     */
    protected function revertAccountBalancesForTransaction(Transaction $transaction): void
    {
        $amount = (float) $transaction->amount;
        $originAccount = Account::find($transaction->account_id);

        if (!$originAccount) {
            return;
        }

        if ($transaction->type === 'expense') {
            $originAccount->increment('balance', $amount);
        } elseif ($transaction->type === 'income') {
            $originAccount->decrement('balance', $amount);
        } elseif ($transaction->type === 'transfer') {
            $destAccount = Account::find($transaction->destination_account_id);
            $originAccount->increment('balance', $amount);
            if ($destAccount) {
                $destAccount->decrement('balance', $amount);
            }
        }
    }
}
