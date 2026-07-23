<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_manage_accounts(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/accounts', [
            'name' => 'GrabPay Wallet',
            'type' => 'e-wallet',
            'currency' => 'MYR',
            'initial_balance' => 150.00,
            'color' => '#16A34A',
            'icon' => 'wallet',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'name' => 'GrabPay Wallet',
            'balance' => 150.00,
            'currency' => 'MYR',
        ]);
    }
}
