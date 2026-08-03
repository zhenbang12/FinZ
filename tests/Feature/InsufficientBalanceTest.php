<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsufficientBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_logging_expense_exceeding_account_balance_is_rejected(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Test Account',
            'type' => 'bank',
            'currency' => 'MYR',
            'initial_balance' => 100.00,
            'balance' => 100.00,
            'color' => '#0f172a',
            'icon' => 'wallet',
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Food',
            'type' => 'expense',
            'color' => '#f59e0b',
        ]);

        $response = $this->actingAs($user)->post('/transactions', [
            'type' => 'expense',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 150.00, // Exceeds balance of 100.00
            'date' => now()->toDateString(),
            'notes' => 'Expensive Dinner',
        ]);

        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals(100.00, $account->fresh()->balance);
    }

    public function test_logging_expense_within_account_balance_is_accepted(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Test Account',
            'type' => 'bank',
            'currency' => 'MYR',
            'initial_balance' => 100.00,
            'balance' => 100.00,
            'color' => '#0f172a',
            'icon' => 'wallet',
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Food',
            'type' => 'expense',
            'color' => '#f59e0b',
        ]);

        $response = $this->actingAs($user)->post('/transactions', [
            'type' => 'expense',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 60.00, // Valid
            'date' => now()->toDateString(),
            'notes' => 'Lunch',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(40.00, $account->fresh()->balance);
    }

    public function test_transfer_exceeding_origin_account_balance_is_rejected(): void
    {
        $user = User::factory()->create();
        $acc1 = Account::create([
            'user_id' => $user->id,
            'name' => 'Account 1',
            'type' => 'bank',
            'currency' => 'MYR',
            'initial_balance' => 50.00,
            'balance' => 50.00,
            'color' => '#0f172a',
            'icon' => 'wallet',
        ]);

        $acc2 = Account::create([
            'user_id' => $user->id,
            'name' => 'Account 2',
            'type' => 'bank',
            'currency' => 'MYR',
            'initial_balance' => 10.00,
            'balance' => 10.00,
            'color' => '#0f172a',
            'icon' => 'wallet',
        ]);

        $response = $this->actingAs($user)->post('/transactions', [
            'type' => 'transfer',
            'account_id' => $acc1->id,
            'destination_account_id' => $acc2->id,
            'amount' => 70.00, // Exceeds balance of 50.00
            'date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals(50.00, $acc1->fresh()->balance);
        $this->assertEquals(10.00, $acc2->fresh()->balance);
    }

    public function test_transfer_to_another_users_account_is_rejected(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $acc1 = Account::create([
            'user_id' => $user1->id,
            'name' => 'User1 Account',
            'type' => 'bank',
            'currency' => 'MYR',
            'initial_balance' => 500.00,
            'balance' => 500.00,
            'color' => '#0f172a',
            'icon' => 'wallet',
        ]);

        $acc2 = Account::create([
            'user_id' => $user2->id,
            'name' => 'User2 Account',
            'type' => 'bank',
            'currency' => 'MYR',
            'initial_balance' => 500.00,
            'balance' => 500.00,
            'color' => '#0f172a',
            'icon' => 'wallet',
        ]);

        $response = $this->actingAs($user1)->post('/transactions', [
            'type' => 'transfer',
            'account_id' => $acc1->id,
            'destination_account_id' => $acc2->id, // Belongs to user2
            'amount' => 100.00,
            'date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['destination_account_id']);
    }
}
