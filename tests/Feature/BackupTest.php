<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_export_backup_json(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Maybank Test',
            'type' => 'bank',
            'currency' => 'MYR',
            'initial_balance' => 1000.00,
            'balance' => 1000.00,
        ]);

        $response = $this->actingAs($user)->get('/settings/backup/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $this->assertStringContainsString('finz-backup-', $response->headers->get('Content-Disposition'));

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('FinZ Financial Tracker', $data['meta']['app']);
        $this->assertCount(1, $data['accounts']);
        $this->assertEquals('Maybank Test', $data['accounts'][0]['name']);
    }

    public function test_user_can_restore_backup_json_with_backward_compatibility(): void
    {
        $user = User::factory()->create();

        // Simulate an older V1.0 backup file lacking newly added schema columns (category, sort_order, is_pinned)
        $oldBackupData = [
            'meta' => [
                'app' => 'FinZ Financial Tracker',
                'version' => '1.0',
                'exported_at' => now()->toIso8601String(),
            ],
            'categories' => [
                ['id' => 1, 'name' => 'Test Dining', 'type' => 'expense', 'icon' => 'utensils'],
            ],
            'accounts' => [
                ['id' => 10, 'name' => 'Legacy Wallet', 'type' => 'cash', 'currency' => 'MYR', 'initial_balance' => 500.00, 'balance' => 500.00],
            ],
            'transactions' => [
                ['id' => 100, 'account_id' => 10, 'category_id' => 1, 'type' => 'expense', 'amount' => 50.00, 'date' => '2026-08-01', 'notes' => 'Old Transaction'],
            ],
            'receipts' => [],
            'subscriptions' => [],
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'bkp_') . '.json';
        file_put_contents($tempFile, json_encode($oldBackupData));

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempFile,
            'legacy-backup.json',
            'application/json',
            null,
            true
        );

        $response = $this->actingAs($user)->post('/settings/backup/restore', [
            'backup_file' => $uploadedFile,
            'mode' => 'replace',
        ]);

        $response->assertRedirect();

        // Verify account restored with schema defaults for new columns
        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'name' => 'Legacy Wallet',
            'category' => 'current',
            'sort_order' => 0,
            'is_pinned' => 0,
        ]);

        // Verify transaction restored and re-linked to new account and category IDs
        $restoredAccount = Account::where('user_id', $user->id)->first();
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'account_id' => $restoredAccount->id,
            'amount' => 50.00,
            'notes' => 'Old Transaction',
        ]);

        @unlink($tempFile);
    }
}
