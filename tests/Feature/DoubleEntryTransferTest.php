<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoubleEntryTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_cross_account_transfer_debits_origin_and_credits_destination_simultaneously(): void
    {
        $user = User::factory()->create();

        $maybank = Account::create([
            'user_id' => $user->id,
            'name' => 'Maybank Savings',
            'type' => 'bank',
            'currency' => 'MYR',
            'initial_balance' => 1000.00,
            'balance' => 1000.00,
        ]);

        $tng = Account::create([
            'user_id' => $user->id,
            'name' => 'Touch n Go eWallet',
            'type' => 'e-wallet',
            'currency' => 'MYR',
            'initial_balance' => 100.00,
            'balance' => 100.00,
        ]);

        $category = Category::create([
            'name' => 'Transfer',
            'type' => 'transfer',
        ]);

        $ledgerService = new LedgerService();

        // Transfer RM250 from Maybank to TnG
        $transaction = $ledgerService->createTransaction([
            'user_id' => $user->id,
            'type' => 'transfer',
            'account_id' => $maybank->id,
            'destination_account_id' => $tng->id,
            'category_id' => $category->id,
            'amount' => 250.00,
            'date' => now(),
            'notes' => 'Top up TnG wallet',
        ]);

        $maybank->refresh();
        $tng->refresh();

        // Assert Double-Entry ACID balance updates
        $this->assertEquals(750.00, $maybank->balance);
        $this->assertEquals(350.00, $tng->balance);
        $this->assertEquals(250.00, $transaction->amount);
    }
}
