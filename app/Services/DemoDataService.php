<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\ReceiptSessionClaim;
use App\Models\Subscription;
use App\Models\SubscriptionMember;
use App\Models\SubscriptionPayment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataService
{
    /**
     * Ensure the demo user exists and has a complete, rich dataset.
     */
    public function ensureDemoData(bool $forceReset = false): User
    {
        $demoEmail = config('demo.user_email', 'demo@finz.app');

        $user = User::firstOrCreate(
            ['email' => $demoEmail],
            [
                'name' => 'FinZ Demo User',
                'password' => Hash::make(Str::random(32)),
                'is_admin' => false,
                'timezone' => 'Asia/Kuala_Lumpur',
                'currency' => 'MYR',
            ]
        );

        // Always ensure system categories exist
        Category::ensureSystemCategories();

        $accountCount = Account::where('user_id', $user->id)->count();
        $txCount = Transaction::where('user_id', $user->id)->count();

        if ($forceReset || $accountCount === 0 || $txCount < 8) {
            $this->seedPristineDemoData($user);
        }

        return $user;
    }

    /**
     * Seed pristine demonstration data for the demo user.
     */
    public function seedPristineDemoData(User $user): void
    {
        // 1. Setup Accounts
        $accountsConfig = [
            [
                'name' => 'Maybank Savings',
                'category' => 'current',
                'type' => 'bank',
                'currency' => 'MYR',
                'initial_balance' => 9500.00,
                'color' => '#FBBF24', // Maybank Tiger Yellow
                'icon' => 'Building',
                'sort_order' => 0,
                'is_pinned' => true,
            ],
            [
                'name' => 'GXBank Savings',
                'category' => 'savings',
                'type' => 'bank',
                'currency' => 'MYR',
                'initial_balance' => 5200.00,
                'color' => '#06B6D4', // GX Cyan
                'icon' => 'ShieldCheck',
                'sort_order' => 1,
                'is_pinned' => true,
            ],
            [
                'name' => 'Touch \'n Go eWallet',
                'category' => 'other',
                'type' => 'e-wallet',
                'currency' => 'MYR',
                'initial_balance' => 450.00,
                'color' => '#F97316', // TnG Orange
                'icon' => 'Smartphone',
                'sort_order' => 2,
                'is_pinned' => true,
            ],
            [
                'name' => 'Boost PayFlex',
                'category' => 'other',
                'type' => 'e-wallet',
                'currency' => 'MYR',
                'initial_balance' => 180.00,
                'color' => '#2563EB', // Boost Blue
                'icon' => 'CreditCard',
                'sort_order' => 3,
                'is_pinned' => false,
            ],
            [
                'name' => 'UOB One Card',
                'category' => 'credit_card',
                'type' => 'credit_card',
                'currency' => 'MYR',
                'initial_balance' => 0.00,
                'color' => '#EC4899', // UOB Pink
                'icon' => 'CreditCard',
                'sort_order' => 4,
                'is_pinned' => false,
            ],
        ];

        $accountMap = [];
        foreach ($accountsConfig as $accData) {
            $acc = Account::firstOrNew(
                ['name' => $accData['name'], 'user_id' => $user->id]
            );
            $acc->fill($accData);
            $acc->balance = $accData['initial_balance'];
            $acc->save();
            $accountMap[$accData['name']] = $acc;
        }

        $maybank = $accountMap['Maybank Savings'];
        $gxbank = $accountMap['GXBank Savings'];
        $tng = $accountMap['Touch \'n Go eWallet'];
        $boost = $accountMap['Boost PayFlex'];

        // 2. Map system categories
        $categoryMap = Category::whereNull('user_id')->pluck('id', 'name')->toArray();

        // 3. Transactions spanning current & previous month
        $sampleTransactions = [
            // Current month recent expenses
            ['type' => 'expense', 'acc' => $tng, 'cat' => 'Food & Dining', 'amount' => 28.50, 'notes' => 'Zus Coffee & Tuna Croissant', 'days_ago' => 0],
            ['type' => 'expense', 'acc' => $maybank, 'cat' => 'Transport & Petrol', 'amount' => 95.00, 'notes' => 'Shell V-Power 97 Fuel Topup', 'days_ago' => 1],
            ['type' => 'expense', 'acc' => $tng, 'cat' => 'Transport & Petrol', 'amount' => 12.50, 'notes' => 'LDP Highway SmartTAG Toll fare', 'days_ago' => 2],
            ['type' => 'expense', 'acc' => $maybank, 'cat' => 'Shopping', 'amount' => 189.00, 'notes' => 'Uniqlo AIRism Oversized Tees', 'days_ago' => 3],
            ['type' => 'expense', 'acc' => $maybank, 'cat' => 'Utilities & Bills', 'amount' => 210.00, 'notes' => 'TNB Electricity Monthly Bill', 'days_ago' => 4],
            ['type' => 'income', 'acc' => $tng, 'cat' => 'Payback & Reimbursements', 'amount' => 45.00, 'notes' => 'Din Tai Fung Lunch Payback from Chloe', 'days_ago' => 5],
            ['type' => 'expense', 'acc' => $tng, 'cat' => 'Groceries', 'amount' => 165.80, 'notes' => 'Jaya Grocer Fresh Produce @ Mid Valley', 'days_ago' => 6],
            ['type' => 'income', 'acc' => $gxbank, 'cat' => 'Investment & Interest', 'amount' => 14.20, 'notes' => 'GXBank Daily 3% p.a. Interest Credit', 'days_ago' => 7],
            ['type' => 'transfer', 'acc' => $maybank, 'dest' => $tng, 'cat' => 'Transfer', 'amount' => 300.00, 'notes' => 'TnG eWallet Auto Reload', 'days_ago' => 8],
            ['type' => 'expense', 'acc' => $maybank, 'cat' => 'Food & Dining', 'amount' => 138.00, 'notes' => 'Din Tai Fung Dumplings Dinner', 'days_ago' => 10],
            ['type' => 'transfer', 'acc' => $maybank, 'dest' => $gxbank, 'cat' => 'Transfer', 'amount' => 2000.00, 'notes' => 'Monthly High-Yield Savings to GXBank', 'days_ago' => 14],
            ['type' => 'income', 'acc' => $maybank, 'cat' => 'Salary / Income', 'amount' => 6800.00, 'notes' => 'Monthly Engineering Salary Payment', 'days_ago' => 15],
            ['type' => 'income', 'acc' => $maybank, 'cat' => 'Refunds & Cashbacks', 'amount' => 55.00, 'notes' => 'Shopee Return Refund #20268841', 'days_ago' => 18],
            ['type' => 'expense', 'acc' => $boost, 'cat' => 'Shopping', 'amount' => 79.00, 'notes' => 'Watson Healthcare Essentials', 'days_ago' => 22],
            // Previous month anchor transactions
            ['type' => 'income', 'acc' => $maybank, 'cat' => 'Salary / Income', 'amount' => 6800.00, 'notes' => 'Prior Month Salary Payment', 'days_ago' => 45],
            ['type' => 'expense', 'acc' => $maybank, 'cat' => 'Utilities & Bills', 'amount' => 195.00, 'notes' => 'Prior Month TNB Electricity', 'days_ago' => 48],
            ['type' => 'expense', 'acc' => $tng, 'cat' => 'Groceries', 'amount' => 210.50, 'notes' => 'Village Grocer Weekly Groceries', 'days_ago' => 52],
        ];

        foreach ($sampleTransactions as $tx) {
            $catId = $categoryMap[$tx['cat']] ?? null;
            $existing = Transaction::where('user_id', $user->id)
                ->where('notes', $tx['notes'])
                ->first();

            if (!$existing) {
                Transaction::create([
                    'user_id' => $user->id,
                    'type' => $tx['type'],
                    'account_id' => $tx['acc']->id,
                    'destination_account_id' => isset($tx['dest']) ? $tx['dest']->id : null,
                    'category_id' => $catId,
                    'amount' => $tx['amount'],
                    'notes' => $tx['notes'],
                    'date' => Carbon::now()->subDays($tx['days_ago'])->format('Y-m-d H:i:s'),
                ]);
            }
        }

        // 4. Seed SmartSplit Haidilao Receipt
        $receipt = Receipt::firstOrCreate(
            ['merchant_name' => 'Haidilao Hotpot (TRX)', 'user_id' => $user->id],
            [
                'image_path' => null,
                'subtotal' => 180.00,
                'tax_amount' => 10.80, // 6% SST
                'service_charge_amount' => 18.00, // 10% Service Charge
                'total_amount' => 208.80,
                'status' => 'parsed',
                'share_token' => 'demo-hdl-split',
            ]
        );

        if ($receipt->items()->count() === 0) {
            $item1 = ReceiptItem::create(['receipt_id' => $receipt->id, 'name' => 'Tomato Soup Base (Double)', 'unit_price' => 32.00, 'quantity' => 1, 'total_price' => 32.00]);
            $item2 = ReceiptItem::create(['receipt_id' => $receipt->id, 'name' => 'Sliced Australian Beef Shortplate', 'unit_price' => 58.00, 'quantity' => 1, 'total_price' => 58.00]);
            $item3 = ReceiptItem::create(['receipt_id' => $receipt->id, 'name' => 'Handmade Fish Paste', 'unit_price' => 28.00, 'quantity' => 1, 'total_price' => 28.00]);
            $item4 = ReceiptItem::create(['receipt_id' => $receipt->id, 'name' => 'Kungfu Noodles + Dancing Show', 'unit_price' => 12.00, 'quantity' => 2, 'total_price' => 24.00]);
            $item5 = ReceiptItem::create(['receipt_id' => $receipt->id, 'name' => 'Free Flow Sauce Bar & Dessert', 'unit_price' => 9.00, 'quantity' => 4, 'total_price' => 38.00]);

            // Seed sample claim demonstrating SmartSplit pro-rata logic
            ReceiptSessionClaim::create([
                'receipt_id' => $receipt->id,
                'receipt_item_id' => $item2->id,
                'guest_name' => 'Chloe',
                'amount_paid' => 67.28,
                'is_paid' => true,
            ]);
        }

        // 5. Seed Shared Subscriptions
        $sub = Subscription::firstOrCreate(
            ['name' => 'YouTube Premium Family', 'user_id' => $user->id],
            [
                'billing_cycle_day' => 27,
                'start_month' => (int) Carbon::now()->subMonths(2)->format('m'),
                'start_year' => (int) Carbon::now()->subMonths(2)->format('Y'),
                'total_monthly_cost' => 45.00,
                'currency' => 'MYR',
                'notes' => 'Family group billed on the 27th of every month. Managed via GXBank card.',
            ]
        );

        if ($sub->members()->count() === 0) {
            $m1 = SubscriptionMember::create(['subscription_id' => $sub->id, 'name' => 'Alex (Brother)', 'default_share_amount' => 9.00]);
            $m2 = SubscriptionMember::create(['subscription_id' => $sub->id, 'name' => 'Sarah (Friend)', 'default_share_amount' => 9.00]);
            $m3 = SubscriptionMember::create(['subscription_id' => $sub->id, 'name' => 'Jason (Housemate)', 'default_share_amount' => 9.00]);
            $mMe = SubscriptionMember::create(['subscription_id' => $sub->id, 'name' => 'Me (Demo User)', 'default_share_amount' => 18.00]);

            $now = Carbon::now();
            // Seed paid cycle for previous month
            SubscriptionPayment::create([
                'subscription_member_id' => $m1->id,
                'billing_year' => $now->copy()->subMonth()->year,
                'billing_month' => $now->copy()->subMonth()->month,
                'billing_cycle_label' => $now->copy()->subMonth()->format('M Y'),
                'status' => 'paid',
                'amount' => 9.00,
                'payment_date' => $now->copy()->subDays(25)->format('Y-m-d'),
                'reference_no' => 'TNG-PAY-882190',
            ]);

            SubscriptionPayment::create([
                'subscription_member_id' => $m2->id,
                'billing_year' => $now->copy()->subMonth()->year,
                'billing_month' => $now->copy()->subMonth()->month,
                'billing_cycle_label' => $now->copy()->subMonth()->format('M Y'),
                'status' => 'paid',
                'amount' => 9.00,
                'payment_date' => $now->copy()->subDays(24)->format('Y-m-d'),
                'reference_no' => 'DUITNOW-994102',
            ]);
        }
    }
}
