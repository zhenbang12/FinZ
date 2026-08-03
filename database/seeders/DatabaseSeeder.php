<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Subscription;
use App\Models\SubscriptionMember;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with realistic test data.
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

        $users = [$admin, $user];

        // System Categories
        $categories = [
            ['name' => 'Food & Dining', 'type' => 'expense', 'icon' => 'utensils', 'color' => '#F59E0B'],
            ['name' => 'Groceries', 'type' => 'expense', 'icon' => 'shopping-cart', 'color' => '#10B981'],
            ['name' => 'Transport & Petrol', 'type' => 'expense', 'icon' => 'car', 'color' => '#3B82F6'],
            ['name' => 'Utilities & Bills', 'type' => 'expense', 'icon' => 'zap', 'color' => '#8B5CF6'],
            ['name' => 'Entertainment & Tech', 'type' => 'expense', 'icon' => 'film', 'color' => '#EC4899'],
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

        foreach ($users as $u) {
            // Seed 5 Accounts with Custom Hex Accents for each user
            $accountsData = [
                [
                    'name' => 'Maybank Savings',
                    'category' => 'current',
                    'type' => 'bank',
                    'currency' => 'MYR',
                    'initial_balance' => 9500.00,
                    'balance' => 9500.00,
                    'color' => '#FBBF24', // Maybank Tiger Yellow
                    'sort_order' => 0,
                ],
                [
                    'name' => 'GXBank Savings',
                    'category' => 'savings',
                    'type' => 'bank',
                    'currency' => 'MYR',
                    'initial_balance' => 5200.00,
                    'balance' => 5200.00,
                    'color' => '#06B6D4', // GX Cyan
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Touch \'n Go eWallet',
                    'category' => 'other',
                    'type' => 'e-wallet',
                    'currency' => 'MYR',
                    'initial_balance' => 450.00,
                    'balance' => 450.00,
                    'color' => '#F97316', // TnG Orange
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Boost PayFlex',
                    'category' => 'other',
                    'type' => 'e-wallet',
                    'currency' => 'MYR',
                    'initial_balance' => 180.00,
                    'balance' => 180.00,
                    'color' => '#2563EB', // Boost Blue
                    'sort_order' => 3,
                ],
                [
                    'name' => 'UOB One Card',
                    'category' => 'other',
                    'type' => 'credit_card',
                    'currency' => 'MYR',
                    'initial_balance' => 0.00,
                    'balance' => 0.00,
                    'color' => '#EC4899', // UOB Pink
                    'sort_order' => 4,
                ],
            ];

            $userAccountMap = [];
            foreach ($accountsData as $acc) {
                $created = Account::firstOrCreate(
                    ['name' => $acc['name'], 'user_id' => $u->id],
                    $acc
                );
                $userAccountMap[$acc['name']] = $created;
            }

            $maybank = $userAccountMap['Maybank Savings'];
            $gxbank = $userAccountMap['GXBank Savings'];
            $tng = $userAccountMap['Touch \'n Go eWallet'];

            // Seed Past 60 Days Transactions
            $sampleTransactions = [
                ['type' => 'income', 'acc' => $maybank, 'cat' => 'Salary / Income', 'amount' => 6800.00, 'notes' => 'Monthly Salary Payment', 'days_ago' => 45],
                ['type' => 'income', 'acc' => $maybank, 'cat' => 'Salary / Income', 'amount' => 6800.00, 'notes' => 'Monthly Salary Payment', 'days_ago' => 15],
                ['type' => 'transfer', 'acc' => $maybank, 'dest' => $gxbank, 'cat' => 'Transfer', 'amount' => 2000.00, 'notes' => 'Monthly Auto-Savings to GXBank', 'days_ago' => 14],
                ['type' => 'transfer', 'acc' => $maybank, 'dest' => $tng, 'cat' => 'Transfer', 'amount' => 300.00, 'notes' => 'Topup TnG eWallet', 'days_ago' => 10],
                ['type' => 'expense', 'acc' => $tng, 'cat' => 'Groceries', 'amount' => 148.50, 'notes' => 'Jaya Grocer @ Mid Valley', 'days_ago' => 8],
                ['type' => 'expense', 'acc' => $tng, 'cat' => 'Food & Dining', 'amount' => 35.80, 'notes' => 'Din Tai Fung Lunch', 'days_ago' => 7],
                ['type' => 'expense', 'acc' => $maybank, 'cat' => 'Transport & Petrol', 'amount' => 90.00, 'notes' => 'Shell RON95 Petrol', 'days_ago' => 6],
                ['type' => 'expense', 'acc' => $maybank, 'cat' => 'Utilities & Bills', 'amount' => 210.00, 'notes' => 'TNB Electricity Bill', 'days_ago' => 5],
                ['type' => 'expense', 'acc' => $tng, 'cat' => 'Transport & Petrol', 'amount' => 12.50, 'notes' => 'LDP Highway Toll fare', 'days_ago' => 4],
                ['type' => 'expense', 'acc' => $maybank, 'cat' => 'Shopping', 'amount' => 249.00, 'notes' => 'Uniqlo Wireless Headphones', 'days_ago' => 3],
                ['type' => 'expense', 'acc' => $tng, 'cat' => 'Food & Dining', 'amount' => 28.50, 'notes' => 'Zus Coffee & Sandwich', 'days_ago' => 1],
            ];

            foreach ($sampleTransactions as $tx) {
                $exists = Transaction::where('user_id', $u->id)->where('notes', $tx['notes'])->exists();
                if (!$exists) {
                    Transaction::create([
                        'user_id' => $u->id,
                        'type' => $tx['type'],
                        'account_id' => $tx['acc']->id,
                        'destination_account_id' => isset($tx['dest']) ? $tx['dest']->id : null,
                        'category_id' => $categoryMap[$tx['cat']],
                        'amount' => $tx['amount'],
                        'notes' => $tx['notes'],
                        'date' => Carbon::now()->subDays($tx['days_ago']),
                    ]);

                    // Recalculate balances
                    if ($tx['type'] === 'income') {
                        $tx['acc']->increment('balance', $tx['amount']);
                    } elseif ($tx['type'] === 'expense') {
                        $tx['acc']->decrement('balance', $tx['amount']);
                    } elseif ($tx['type'] === 'transfer' && isset($tx['dest'])) {
                        $tx['acc']->decrement('balance', $tx['amount']);
                        $tx['dest']->increment('balance', $tx['amount']);
                    }
                }
            }

            // Seed Sample SmartSplit Receipt
            $receiptExists = Receipt::where('user_id', $u->id)->where('merchant_name', 'Haidilao Hotpot (TRX)')->exists();
            if (!$receiptExists) {
                $receipt = Receipt::create([
                    'user_id' => $u->id,
                    'image_path' => null,
                    'merchant_name' => 'Haidilao Hotpot (TRX)',
                    'subtotal' => 180.00,
                    'tax_amount' => 10.80, // 6% SST
                    'service_charge_amount' => 18.00, // 10% Service Charge
                    'total_amount' => 208.80,
                    'status' => 'parsed',
                ]);

                ReceiptItem::create(['receipt_id' => $receipt->id, 'name' => 'Tomato Soup Base (Double)', 'unit_price' => 32.00, 'quantity' => 1, 'total_price' => 32.00]);
                ReceiptItem::create(['receipt_id' => $receipt->id, 'name' => 'Sliced Australian Beef Shortplate', 'unit_price' => 58.00, 'quantity' => 1, 'total_price' => 58.00]);
                ReceiptItem::create(['receipt_id' => $receipt->id, 'name' => 'Handmade Fish Paste', 'unit_price' => 28.00, 'quantity' => 1, 'total_price' => 28.00]);
                ReceiptItem::create(['receipt_id' => $receipt->id, 'name' => 'Kungfu Noodles + Dancing Show', 'unit_price' => 12.00, 'quantity' => 2, 'total_price' => 24.00]);
                ReceiptItem::create(['receipt_id' => $receipt->id, 'name' => 'Free Flow Sauce Bar & Dessert', 'unit_price' => 9.00, 'quantity' => 4, 'total_price' => 38.00]);
            }

            // Seed Sample Subscriptions
            $subExists = Subscription::where('user_id', $u->id)->where('name', 'YouTube Premium Family')->exists();
            if (!$subExists) {
                $sub = Subscription::create([
                    'user_id' => $u->id,
                    'name' => 'YouTube Premium Family',
                    'billing_cycle_day' => 27,
                    'total_monthly_cost' => 45.00,
                    'currency' => 'MYR',
                    'notes' => 'Billed on 27th of every month',
                ]);

                SubscriptionMember::create(['subscription_id' => $sub->id, 'name' => 'Alex (Brother)', 'default_share_amount' => 9.00]);
                SubscriptionMember::create(['subscription_id' => $sub->id, 'name' => 'Sarah (Friend)', 'default_share_amount' => 9.00]);
                SubscriptionMember::create(['subscription_id' => $sub->id, 'name' => 'Jason (Housemate)', 'default_share_amount' => 9.00]);
            }
        }
    }
}
