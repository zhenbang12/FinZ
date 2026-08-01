<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Subscription;
use App\Models\SubscriptionMember;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_shared_subscription_with_members(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/subscriptions', [
            'name' => 'YouTube Premium Payment Sheets',
            'billing_cycle_day' => 27,
            'total_monthly_cost' => 45.00,
            'currency' => 'MYR',
            'notes' => '27th of Every Month Starts a new cycle',
            'members' => [
                ['name' => 'Melissa', 'default_share_amount' => 14.00],
                ['name' => 'Waiz', 'default_share_amount' => 14.00],
                ['name' => 'Tan Jing', 'default_share_amount' => 14.00],
                ['name' => 'Hong Yu', 'default_share_amount' => 7.00],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'name' => 'YouTube Premium Payment Sheets',
            'billing_cycle_day' => 27,
        ]);

        $this->assertDatabaseHas('subscription_members', [
            'name' => 'Melissa',
            'default_share_amount' => 14.00,
        ]);

        $this->assertDatabaseHas('subscription_members', [
            'name' => 'Hong Yu',
            'default_share_amount' => 7.00,
        ]);
    }

    public function test_user_can_log_member_payment_and_auto_post_income(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Touch n Go eWallet',
            'type' => 'e-wallet',
            'currency' => 'MYR',
            'balance' => 100.00,
        ]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'name' => 'YouTube Premium Payment Sheets',
            'billing_cycle_day' => 27,
            'total_monthly_cost' => 45.00,
        ]);

        $member = $subscription->members()->create([
            'name' => 'Melissa',
            'default_share_amount' => 14.00,
        ]);

        $response = $this->actingAs($user)->post('/subscriptions-log-payment', [
            'subscription_member_id' => $member->id,
            'billing_year' => 2026,
            'billing_month' => 1,
            'billing_cycle_label' => 'January 2026',
            'status' => 'paid',
            'amount' => 14.00,
            'payment_date' => '2026-01-01',
            'reference_no' => '2026010110110000010000TNGOW3',
            'notes' => 'Receive from Wallet202601011111 MELISSA ONG JING NING',
            'account_id' => $account->id,
            'auto_post_income' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('subscription_payments', [
            'subscription_member_id' => $member->id,
            'billing_year' => 2026,
            'billing_month' => 1,
            'status' => 'paid',
            'amount' => 14.00,
            'reference_no' => '2026010110110000010000TNGOW3',
        ]);

        // Verify account balance updated by 14.00 (from 100.00 to 114.00)
        $account->refresh();
        $this->assertEquals(114.00, $account->balance);
    }
}
