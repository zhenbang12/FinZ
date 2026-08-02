<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $currentSessionId = session()->getId();

        // 1. Fetch active devices / sessions for current user
        $rawSessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get();

        $sessions = $rawSessions->map(function ($s) use ($currentSessionId) {
            $agent = $s->user_agent ?: '';
            
            return [
                'id' => $s->id,
                'ip_address' => $s->ip_address ?: 'Unknown IP',
                'device_name' => $this->parseDeviceName($agent),
                'browser' => $this->parseBrowser($agent),
                'platform' => $this->parsePlatform($agent),
                'is_current_device' => ($s->id === $currentSessionId),
                'last_activity' => date('Y-m-d H:i:s', $s->last_activity),
                'last_activity_human' => \Illuminate\Support\Carbon::createFromTimestamp($s->last_activity)->diffForHumans(),
            ];
        });

        // 2. Fetch users list if superuser
        $users = [];
        if ($user->is_admin) {
            $users = User::withCount(['accounts', 'transactions'])
                ->orderBy('id', 'asc')
                ->get();
        }

        // 3. Fetch registered passkeys for current user
        if (!\Illuminate\Support\Facades\Schema::hasTable('passkeys')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {}
        }

        $passkeys = \Illuminate\Support\Facades\Schema::hasTable('passkeys')
            ? \App\Models\Passkey::where('user_id', $user->id)
                ->latest()
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'created_at' => $p->created_at->format('Y-m-d H:i:s'),
                    'created_at_human' => $p->created_at->diffForHumans(),
                ])
            : collect([]);

        return Inertia::render('Settings/Index', [
            'sessions' => $sessions,
            'users' => $users,
            'passkeys' => $passkeys,
            'preferences' => [
                'timezone' => $user->timezone ?: 'Asia/Kuala_Lumpur',
                'currency' => $user->currency ?: 'MYR',
            ],
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $request->validate([
            'timezone' => 'required|string|max:50',
            'currency' => 'required|string|max:10',
        ]);

        $user = $request->user();
        $user->update([
            'timezone' => $request->timezone,
            'currency' => strtoupper($request->currency),
        ]);

        return redirect()->back()->with('success', 'General preferences updated successfully.');
    }

    public function destroySession(Request $request, string $sessionId)
    {
        $user = $request->user();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', $sessionId)
            ->delete();

        return redirect()->back()->with('success', 'Logged out device session successfully.');
    }

    public function logoutOtherDevices(Request $request)
    {
        $user = $request->user();
        $currentSessionId = session()->getId();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        return redirect()->back()->with('success', 'Successfully logged out all other active devices.');
    }

    public function exportBackup(Request $request)
    {
        $user = $request->user();

        $accounts = \App\Models\Account::where('user_id', $user->id)->get();
        $categories = \App\Models\Category::where('user_id', $user->id)->get();
        $transactions = \App\Models\Transaction::where('user_id', $user->id)->get();
        $receipts = \App\Models\Receipt::where('user_id', $user->id)->with(['items', 'claims'])->get();
        $subscriptions = \App\Models\Subscription::where('user_id', $user->id)->with(['members.payments'])->get();

        $backupData = [
            'meta' => [
                'app' => 'FinZ Financial Tracker',
                'version' => '1.0',
                'exported_at' => now()->toIso8601String(),
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'timezone' => $user->timezone,
                    'currency' => $user->currency,
                ],
            ],
            'accounts' => $accounts->toArray(),
            'categories' => $categories->toArray(),
            'transactions' => $transactions->toArray(),
            'receipts' => $receipts->toArray(),
            'subscriptions' => $subscriptions->toArray(),
        ];

        $jsonContent = json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $filename = 'finz-backup-' . now()->format('Y-m-d-His') . '.json';

        return response($jsonContent, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function restoreBackup(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|max:10240', // 10MB limit
            'mode' => 'nullable|string|in:replace,merge',
        ]);

        $user = $request->user();
        $mode = $request->input('mode', 'replace');

        $content = file_get_contents($request->file('backup_file')->getRealPath());
        $data = json_decode($content, true);

        if (!$data || !is_array($data) || !isset($data['accounts'])) {
            return redirect()->back()->with('error', 'Invalid FinZ backup file format.');
        }

        DB::transaction(function () use ($user, $data, $mode) {
            if ($mode === 'replace') {
                \App\Models\Transaction::where('user_id', $user->id)->delete();
                \App\Models\Receipt::where('user_id', $user->id)->delete();
                \App\Models\Subscription::where('user_id', $user->id)->delete();
                \App\Models\Account::where('user_id', $user->id)->delete();
                \App\Models\Category::where('user_id', $user->id)->delete();
            }

            $accountMap = [];
            $categoryMap = [];

            // 1. Restore Categories
            if (!empty($data['categories'])) {
                foreach ($data['categories'] as $cat) {
                    $oldId = $cat['id'] ?? null;
                    unset($cat['id'], $cat['user_id'], $cat['created_at'], $cat['updated_at']);
                    $cat['user_id'] = $user->id;
                    $cat['type'] = $cat['type'] ?? 'expense';
                    $cat['color'] = $cat['color'] ?? '#64748B';
                    $created = \App\Models\Category::create($cat);
                    if ($oldId) $categoryMap[$oldId] = $created->id;
                }
            }

            // 2. Restore Accounts
            if (!empty($data['accounts'])) {
                foreach ($data['accounts'] as $acc) {
                    $oldId = $acc['id'] ?? null;
                    unset($acc['id'], $acc['user_id'], $acc['created_at'], $acc['updated_at']);
                    $acc['user_id'] = $user->id;
                    $acc['category'] = $acc['category'] ?? 'current';
                    $acc['sort_order'] = $acc['sort_order'] ?? 0;
                    $acc['is_pinned'] = $acc['is_pinned'] ?? false;
                    $created = \App\Models\Account::create($acc);
                    if ($oldId) $accountMap[$oldId] = $created->id;
                }
            }

            // 3. Restore Transactions
            if (!empty($data['transactions'])) {
                foreach ($data['transactions'] as $tx) {
                    unset($tx['id'], $tx['user_id'], $tx['created_at'], $tx['updated_at']);
                    $tx['user_id'] = $user->id;
                    if (isset($tx['account_id']) && isset($accountMap[$tx['account_id']])) {
                        $tx['account_id'] = $accountMap[$tx['account_id']];
                    }
                    if (isset($tx['destination_account_id']) && isset($accountMap[$tx['destination_account_id']])) {
                        $tx['destination_account_id'] = $accountMap[$tx['destination_account_id']];
                    }
                    if (isset($tx['category_id']) && isset($categoryMap[$tx['category_id']])) {
                        $tx['category_id'] = $categoryMap[$tx['category_id']];
                    }
                    \App\Models\Transaction::create($tx);
                }
            }

            // 4. Restore Receipts
            if (!empty($data['receipts'])) {
                foreach ($data['receipts'] as $r) {
                    $items = $r['items'] ?? [];
                    unset($r['id'], $r['user_id'], $r['created_at'], $r['updated_at'], $r['items'], $r['claims']);
                    $r['user_id'] = $user->id;
                    $createdReceipt = \App\Models\Receipt::create($r);

                    foreach ($items as $item) {
                        unset($item['id'], $item['receipt_id'], $item['created_at'], $item['updated_at']);
                        $item['receipt_id'] = $createdReceipt->id;
                        \App\Models\ReceiptItem::create($item);
                    }
                }
            }

            // 5. Restore Subscriptions
            if (!empty($data['subscriptions'])) {
                foreach ($data['subscriptions'] as $sub) {
                    $members = $sub['members'] ?? [];
                    unset($sub['id'], $sub['user_id'], $sub['created_at'], $sub['updated_at'], $sub['members']);
                    $sub['user_id'] = $user->id;
                    $createdSub = \App\Models\Subscription::create($sub);

                    foreach ($members as $m) {
                        $payments = $m['payments'] ?? [];
                        unset($m['id'], $m['subscription_id'], $m['created_at'], $m['updated_at'], $m['payments']);
                        $m['subscription_id'] = $createdSub->id;
                        $createdMember = \App\Models\SubscriptionMember::create($m);

                        foreach ($payments as $p) {
                            unset($p['id'], $p['subscription_member_id'], $p['transaction_id'], $p['created_at'], $p['updated_at']);
                            $p['subscription_member_id'] = $createdMember->id;
                            \App\Models\SubscriptionPayment::create($p);
                        }
                    }
                }
            }
        });

        return redirect()->back()->with('success', 'Data backup restored successfully!');
    }

    protected function parseDeviceName(string $agent): string
    {
        if (str_contains($agent, 'iPhone')) return 'iPhone';
        if (str_contains($agent, 'iPad')) return 'iPad';
        if (str_contains($agent, 'Android')) return 'Android Phone';
        if (str_contains($agent, 'Windows')) return 'Windows PC';
        if (str_contains($agent, 'Macintosh') || str_contains($agent, 'Mac OS')) return 'MacBook / Mac';
        if (str_contains($agent, 'Linux')) return 'Linux PC';
        return 'Unknown Device';
    }

    protected function parseBrowser(string $agent): string
    {
        if (str_contains($agent, 'Chrome') && !str_contains($agent, 'Edg')) return 'Google Chrome';
        if (str_contains($agent, 'Safari') && !str_contains($agent, 'Chrome')) return 'Apple Safari';
        if (str_contains($agent, 'Edg')) return 'Microsoft Edge';
        if (str_contains($agent, 'Firefox')) return 'Mozilla Firefox';
        return 'Browser';
    }

    protected function parsePlatform(string $agent): string
    {
        if (str_contains($agent, 'iPhone') || str_contains($agent, 'iPad')) return 'iOS';
        if (str_contains($agent, 'Android')) return 'Android';
        if (str_contains($agent, 'Windows')) return 'Windows';
        if (str_contains($agent, 'Macintosh') || str_contains($agent, 'Mac OS')) return 'macOS';
        if (str_contains($agent, 'Linux')) return 'Linux';
        return 'OS';
    }
}
