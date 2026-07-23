<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Superuser Account
        $admin = User::firstOrCreate(
            ['email' => 'admin@finz.app'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('adminpassword'),
                'is_admin' => true,
            ]
        );

        // 2. Regular User Account
        $user = User::firstOrCreate(
            ['email' => 'demo@finz.app'],
            [
                'name' => 'FinZ Demo User',
                'password' => Hash::make('password'),
                'is_admin' => false,
            ]
        );

        // System Categories
        $categories = [
            ['name' => 'Food & Dining', 'type' => 'expense', 'icon' => 'utensils', 'color' => '#F59E0B'],
            ['name' => 'Groceries', 'type' => 'expense', 'icon' => 'shopping-cart', 'color' => '#10B981'],
            ['name' => 'Transport', 'type' => 'expense', 'icon' => 'car', 'color' => '#3B82F6'],
            ['name' => 'Utilities & Bills', 'type' => 'expense', 'icon' => 'zap', 'color' => '#8B5CF6'],
            ['name' => 'Entertainment', 'type' => 'expense', 'icon' => 'film', 'color' => '#EC4899'],
            ['name' => 'Shopping', 'type' => 'expense', 'icon' => 'shopping-bag', 'color' => '#6366F1'],
            ['name' => 'Salary / Income', 'type' => 'income', 'icon' => 'dollar-sign', 'color' => '#22C55E'],
            ['name' => 'Transfer', 'type' => 'transfer', 'icon' => 'arrow-right-left', 'color' => '#64748B'],
        ];

        $categoryMap = [];
        foreach ($categories as $cat) {
            $created = Category::firstOrCreate(
                ['name' => $cat['name'], 'user_id' => null],
                ['type' => $cat['type'], 'icon' => $cat['icon'], 'color' => $cat['color']]
            );
            $categoryMap[$cat['name']] = $created->id;
        }

        // Malaysian Accounts
        $accounts = [
            [
                'name' => 'Maybank Savings',
                'type' => 'bank',
                'currency' => 'MYR',
                'initial_balance' => 8500.00,
                'balance' => 8500.00,
                'color' => '#EAB308', // Maybank Yellow
                'icon' => 'building-bank',
            ],
            [
                'name' => 'Touch \'n Go eWallet',
                'type' => 'e-wallet',
                'currency' => 'MYR',
                'initial_balance' => 320.50,
                'balance' => 320.50,
                'color' => '#0284C7', // TnG Blue
                'icon' => 'smartphone',
            ],
            [
                'name' => 'GrabPay Wallet',
                'type' => 'e-wallet',
                'currency' => 'MYR',
                'initial_balance' => 145.00,
                'balance' => 145.00,
                'color' => '#16A34A', // Grab Green
                'icon' => 'wallet',
            ],
            [
                'name' => 'Cash Wallet',
                'type' => 'cash',
                'currency' => 'MYR',
                'initial_balance' => 250.00,
                'balance' => 250.00,
                'color' => '#64748B',
                'icon' => 'banknote',
            ],
        ];

        $accountMap = [];
        foreach ($accounts as $acc) {
            $created = Account::firstOrCreate(
                ['name' => $acc['name'], 'user_id' => $user->id],
                $acc
            );
            $accountMap[$acc['name']] = $created;
        }

        // Sample Transactions in MYR
        $maybank = $accountMap['Maybank Savings'];
        $tng = $accountMap['Touch \'n Go eWallet'];
        $cash = $accountMap['Cash Wallet'];

        // Sample Income
        Transaction::firstOrCreate([
            'user_id' => $user->id,
            'notes' => 'Monthly Salary Deposit',
        ], [
            'type' => 'income',
            'account_id' => $maybank->id,
            'category_id' => $categoryMap['Salary / Income'],
            'amount' => 5500.00,
            'date' => now()->subDays(10),
        ]);
        $maybank->increment('balance', 5500.00);

        // Sample Transfer (Maybank -> TnG)
        Transaction::firstOrCreate([
            'user_id' => $user->id,
            'notes' => 'Top up TnG eWallet from Maybank',
        ], [
            'type' => 'transfer',
            'account_id' => $maybank->id,
            'destination_account_id' => $tng->id,
            'category_id' => $categoryMap['Transfer'],
            'amount' => 200.00,
            'date' => now()->subDays(5),
        ]);
        $maybank->decrement('balance', 200.00);
        $tng->increment('balance', 200.00);

        // Sample Expenses
        Transaction::firstOrCreate([
            'user_id' => $user->id,
            'notes' => 'Groceries at Jaya Grocer',
        ], [
            'type' => 'expense',
            'account_id' => $tng->id,
            'category_id' => $categoryMap['Groceries'],
            'amount' => 48.55,
            'date' => now()->subDays(2),
        ]);
        $tng->decrement('balance', 48.55);

        // Sample Receipt for SmartSplit Demo
        $receipt = Receipt::create([
            'user_id' => $user->id,
            'image_path' => null,
            'merchant_name' => 'Nasi Kandar Pelita (KLCC)',
            'subtotal' => 52.00,
            'tax_amount' => 3.12, // 6% SST
            'service_charge_amount' => 5.20, // 10% Service Charge
            'total_amount' => 60.32,
            'status' => 'parsed',
        ]);

        ReceiptItem::create(['receipt_id' => $receipt->id, 'name' => 'Nasi Kandar Ayam Goreng + Telur', 'unit_price' => 14.50, 'quantity' => 2, 'total_price' => 29.00]);
        ReceiptItem::create(['receipt_id' => $receipt->id, 'name' => 'Teh Tarik Ais', 'unit_price' => 3.50, 'quantity' => 2, 'total_price' => 7.00]);
        ReceiptItem::create(['receipt_id' => $receipt->id, 'name' => 'Roti Canai Bawang', 'unit_price' => 3.00, 'quantity' => 2, 'total_price' => 6.00]);
        ReceiptItem::create(['receipt_id' => $receipt->id, 'name' => 'Ayam Varuval (Side)', 'unit_price' => 10.00, 'quantity' => 1, 'total_price' => 10.00]);
    }
}
