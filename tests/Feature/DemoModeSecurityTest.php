<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoModeSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Category::ensureSystemCategories();
        config(['demo.enabled' => true]);
        config(['demo.token' => 'finz-preview-access']);
        config(['demo.user_email' => 'demo@finz.app']);
    }

    public function test_demo_access_requires_valid_token(): void
    {
        // 1. Without token -> redirected with error
        $response = $this->get('/demo');
        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
        $this->assertGuest();

        // 2. With invalid token -> redirected with error
        $responseInvalid = $this->get('/demo?token=wrong-secret-token');
        $responseInvalid->assertRedirect('/login');
        $responseInvalid->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_demo_access_straight_without_token_when_token_is_not_configured(): void
    {
        config(['demo.token' => null]);
        $response = $this->get('/demo');
        $response->assertRedirect('/');
        $response->assertSessionHas('is_demo_session', true);
        $this->assertAuthenticated();
        $this->assertEquals('demo@finz.app', auth()->user()->email);
    }

    public function test_demo_link_authenticates_demo_user_with_valid_token(): void
    {
        $response = $this->get('/demo?token=finz-preview-access');
        $response->assertRedirect('/');
        $response->assertSessionHas('success');
        $response->assertSessionHas('is_demo_session', true);

        $this->assertAuthenticated();
        $this->assertEquals('demo@finz.app', auth()->user()->email);
        $this->assertFalse(auth()->user()->is_admin);

        // Verify pre-seeded demo data exists
        $this->assertGreaterThanOrEqual(5, Account::where('user_id', auth()->id())->count());
        $this->assertGreaterThanOrEqual(10, Transaction::where('user_id', auth()->id())->count());
        $this->assertGreaterThanOrEqual(1, Receipt::where('user_id', auth()->id())->count());
    }

    public function test_demo_user_can_view_all_core_application_modules(): void
    {
        $this->get('/demo?token=finz-preview-access');

        // Overview / Dashboard
        $this->get('/')->assertStatus(200);

        // Accounts
        $this->get('/accounts')->assertStatus(200);

        // Transactions Ledger
        $this->get('/transactions')->assertStatus(200);

        // SmartSplit Receipts
        $this->get('/receipts')->assertStatus(200);

        // Subscriptions
        $this->get('/subscriptions')->assertStatus(200);

        // Analytics
        $this->get('/analytics')->assertStatus(200);

        // Settings
        $this->get('/settings')->assertStatus(200);
    }

    public function test_demo_mode_strictly_blocks_mutations(): void
    {
        $this->get('/demo?token=finz-preview-access');
        $demoUser = auth()->user();

        $initialAccountCount = Account::where('user_id', $demoUser->id)->count();

        // 1. Attempting to create an account
        $resAccount = $this->post('/accounts', [
            'name' => 'Hacker Account',
            'type' => 'bank',
            'category' => 'current',
            'currency' => 'MYR',
            'initial_balance' => 999999,
            'color' => '#000000',
            'icon' => 'Wallet',
        ]);

        $resAccount->assertRedirect();
        $resAccount->assertSessionHas('error');
        $this->assertEquals($initialAccountCount, Account::where('user_id', $demoUser->id)->count());

        // 2. Attempting to delete an account
        $account = Account::where('user_id', $demoUser->id)->first();
        $resDeleteAcc = $this->delete("/accounts/{$account->id}");
        $resDeleteAcc->assertRedirect();
        $resDeleteAcc->assertSessionHas('error');
        $this->assertDatabaseHas('accounts', ['id' => $account->id]);

        // 3. Attempting to create a transaction
        $initialTxCount = Transaction::where('user_id', $demoUser->id)->count();
        $resTx = $this->post('/transactions', [
            'type' => 'expense',
            'account_id' => $account->id,
            'amount' => 500,
            'date' => now()->format('Y-m-d'),
            'notes' => 'Malicious transaction',
        ]);
        $resTx->assertRedirect();
        $resTx->assertSessionHas('error');
        $this->assertEquals($initialTxCount, Transaction::where('user_id', $demoUser->id)->count());

        // 4. JSON API response check
        $resJson = $this->postJson('/accounts', [
            'name' => 'JSON Account',
            'type' => 'bank',
            'category' => 'current',
            'currency' => 'MYR',
            'initial_balance' => 100,
            'color' => '#000000',
            'icon' => 'Wallet',
        ]);
        $resJson->assertStatus(403);
        $resJson->assertJsonFragment(['demo_restricted' => true]);
    }

    public function test_demo_user_is_strictly_blocked_from_admin_routes(): void
    {
        $this->get('/demo?token=finz-preview-access');

        // Admin Users listing
        $res = $this->get('/admin/users');
        $res->assertRedirect('/');
        $res->assertSessionHas('error');

        // User switching
        $resSwitch = $this->post('/admin/users/1/switch');
        $resSwitch->assertRedirect('/');
        $resSwitch->assertSessionHas('error');
    }

    public function test_demo_user_can_perform_in_memory_pro_rata_split_calculations(): void
    {
        $this->get('/demo?token=finz-preview-access');
        $demoUser = auth()->user();

        $receipt = Receipt::where('user_id', $demoUser->id)->with('items')->first();
        $this->assertNotNull($receipt);

        $itemIds = $receipt->items->take(2)->pluck('id')->toArray();

        // In-memory calculation endpoint is whitelisted so visitors can test the calculator!
        $response = $this->postJson("/receipts/{$receipt->id}/split", [
            'claimed_item_ids' => $itemIds,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'claimed_subtotal',
            'tax_share',
            'service_charge_share',
            'final_total',
        ]);
    }

    public function test_demo_user_can_exit_demo_session(): void
    {
        $this->get('/demo?token=finz-preview-access');
        $this->assertAuthenticated();

        $res = $this->post('/demo/exit');
        $res->assertRedirect('/login');
        $this->assertGuest();
        $this->assertNull(session('is_demo_session'));
    }

    public function test_system_categories_cannot_be_deleted_by_any_user(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'regular@finz.app',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $systemCategory = Category::whereNull('user_id')->first();
        $this->assertNotNull($systemCategory);

        $res = $this->actingAs($user)->delete("/categories/{$systemCategory->id}");
        $res->assertStatus(403);

        $this->assertDatabaseHas('categories', ['id' => $systemCategory->id]);
    }
}
