<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Subscription;
use App\Models\SubscriptionMember;
use App\Models\SubscriptionPayment;
use App\Models\Transaction;
use App\Services\LedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }
    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $subscriptions = Subscription::with(['members.payments'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($sub) use ($user) {
                $startYear = (int) ($sub->start_year ?? date('Y'));
                $startMonth = (int) ($sub->start_month ?? date('m'));
                $currentYear = (int) date('Y');
                $currentMonth = (int) date('m');

                $uncollectedDues = 0;
                $userNameLower = strtolower(trim($user->name));
                foreach ($sub->members as $member) {
                    $memberNameLower = strtolower(trim($member->name));
                    $isOwner = ($memberNameLower === 'me' || $memberNameLower === 'myself' || $memberNameLower === $userNameLower);
                    if ($isOwner) {
                        continue; // Exclude owner from uncollected dues from others
                    }

                    $existingPayments = $member->payments->keyBy(function ($p) {
                        return $p->billing_year . '-' . $p->billing_month;
                    });

                    for ($year = $currentYear; $year >= $startYear; $year--) {
                        $maxM = ($year === $currentYear) ? $currentMonth : 12;
                        $minM = ($year === $startYear) ? $startMonth : 1;

                        for ($month = $maxM; $month >= $minM; $month--) {
                            $key = $year . '-' . $month;
                            $payment = $existingPayments->get($key);

                            if ($payment) {
                                if ($payment->status === 'pending') {
                                    $uncollectedDues += (float) $payment->amount;
                                }
                            } else {
                                // Default pending cycle up to current month
                                if ($year < $currentYear || ($year === $currentYear && $month <= $currentMonth)) {
                                    $uncollectedDues += (float) $member->default_share_amount;
                                }
                            }
                        }
                    }
                }

                $sub->uncollected_dues = (float) $uncollectedDues;
                return $sub;
            });

        $totalSubscriptions = $subscriptions->count();
        $totalMonthlyCost = $subscriptions->sum('total_monthly_cost');
        $totalUncollectedDues = $subscriptions->sum('uncollected_dues');

        return Inertia::render('Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'stats' => [
                'totalSubscriptions' => $totalSubscriptions,
                'totalMonthlyCost' => (float) $totalMonthlyCost,
                'totalUncollectedDues' => (float) $totalUncollectedDues,
            ],
        ]);
    }

    /**
     * Store a new shared subscription.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'billing_cycle_day' => 'required|integer|min:1|max:31',
            'start_month' => 'nullable|integer|min:1|max:12',
            'start_year' => 'nullable|integer|min:2020|max:2030',
            'total_monthly_cost' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
            'members' => 'nullable|array',
            'members.*.name' => 'required|string|max:255',
            'members.*.default_share_amount' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $subscription = Subscription::create([
                'user_id' => $request->user()->id,
                'name' => $validated['name'],
                'billing_cycle_day' => $validated['billing_cycle_day'],
                'start_month' => $validated['start_month'] ?? (int) date('m'),
                'start_year' => $validated['start_year'] ?? (int) date('Y'),
                'total_monthly_cost' => $validated['total_monthly_cost'],
                'currency' => $validated['currency'] ?? 'MYR',
                'notes' => $validated['notes'] ?? null,
            ]);

            if (!empty($validated['members'])) {
                foreach ($validated['members'] as $m) {
                    $subscription->members()->create([
                        'name' => $m['name'],
                        'default_share_amount' => $m['default_share_amount'],
                    ]);
                }
            }
        });

        return redirect()->back()->with('success', 'Shared subscription created successfully!');
    }

    /**
     * Display detailed payment sheet for a specific subscription.
     */
    public function show(Request $request, Subscription $subscription): Response
    {
        if ($subscription->user_id !== $request->user()->id) {
            abort(403);
        }

        $subscription->load(['members.payments']);

        $accounts = Account::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        $startYear = (int) ($subscription->start_year ?? date('Y'));
        $startMonth = (int) ($subscription->start_month ?? date('m'));
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        $userNameLower = strtolower(trim($request->user()->name));

        // Format members with cycle grids starting strictly from subscription start date
        $membersData = $subscription->members->map(function ($member) use ($startYear, $startMonth, $currentYear, $currentMonth, $monthNames, $subscription, $userNameLower) {
            $memberNameLower = strtolower(trim($member->name));
            $isOwner = ($memberNameLower === 'me' || $memberNameLower === 'myself' || $memberNameLower === $userNameLower);

            $existingPayments = $member->payments->keyBy(function ($p) {
                return $p->billing_year . '-' . $p->billing_month;
            });

            $cycles = [];
            $totalDueAmount = 0;

            // Generate cycles from current/max year down to start year
            for ($year = $currentYear; $year >= $startYear; $year--) {
                $maxM = ($year === $currentYear) ? $currentMonth : 12;
                $minM = ($year === $startYear) ? $startMonth : 1;

                for ($month = $maxM; $month >= $minM; $month--) {
                    $key = $year . '-' . $month;
                    $payment = $existingPayments->get($key);

                    $cycleLabel = $monthNames[$month] . ' ' . $year;
                    $defaultDueDate = sprintf('%04d-%02d-%02d', $year, $month, min($subscription->billing_cycle_day, 28));

                    if ($payment) {
                        $status = $payment->status;
                        $amount = (float) $payment->amount;
                        if ($status === 'pending') {
                            $totalDueAmount += $amount;
                        }
                    } else {
                        $status = 'pending';
                        $amount = (float) $member->default_share_amount;
                        if ($year < $currentYear || ($year === $currentYear && $month <= $currentMonth)) {
                            $totalDueAmount += $amount;
                        }
                    }

                    $cycles[] = [
                        'id' => $payment ? $payment->id : null,
                        'billing_year' => $year,
                        'billing_month' => $month,
                        'cycle_label' => $cycleLabel,
                        'due_date' => $payment && $payment->due_date ? $payment->due_date->format('Y-m-d') : $defaultDueDate,
                        'payment_date' => $payment && $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                        'status' => $status,
                        'amount' => $amount,
                        'reference_no' => $payment ? $payment->reference_no : null,
                        'notes' => $payment ? $payment->notes : null,
                        'proof_image_path' => $payment ? $payment->proof_image_path : null,
                        'transaction_id' => $payment ? $payment->transaction_id : null,
                    ];
                }
            }

            $dueStatusText = $totalDueAmount > 0
                ? ($isOwner ? 'RM' . number_format($totalDueAmount, 2) . ' Self Share' : 'RM' . number_format($totalDueAmount, 2) . ' Due')
                : ($isOwner ? 'Self Paid' : 'Clear');

            return [
                'id' => $member->id,
                'name' => $member->name,
                'is_owner' => $isOwner,
                'default_share_amount' => (float) $member->default_share_amount,
                'total_due_amount' => (float) $totalDueAmount,
                'due_status' => $dueStatusText,
                'cycles' => $cycles,
            ];
        });

        return Inertia::render('Subscriptions/Show', [
            'subscription' => [
                'id' => $subscription->id,
                'name' => $subscription->name,
                'billing_cycle_day' => $subscription->billing_cycle_day,
                'start_month' => $subscription->start_month ?? (int) date('m'),
                'start_year' => $subscription->start_year ?? (int) date('Y'),
                'total_monthly_cost' => (float) $subscription->total_monthly_cost,
                'currency' => $subscription->currency,
                'notes' => $subscription->notes,
            ],
            'members' => $membersData,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Update subscription details.
     */
    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        if ($subscription->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'billing_cycle_day' => 'required|integer|min:1|max:31',
            'total_monthly_cost' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
        ]);

        $subscription->update($validated);

        return redirect()->back()->with('success', 'Subscription updated successfully!');
    }

    /**
     * Delete subscription.
     */
    public function destroy(Request $request, Subscription $subscription): RedirectResponse
    {
        if ($subscription->user_id !== $request->user()->id) {
            abort(403);
        }

        $subscription->delete();

        return redirect()->route('subscriptions.index')->with('success', 'Subscription deleted!');
    }

    /**
     * Add member to subscription.
     */
    public function storeMember(Request $request, Subscription $subscription): RedirectResponse
    {
        if ($subscription->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'default_share_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $subscription->members()->create($validated);

        return redirect()->back()->with('success', 'Member added successfully!');
    }

    /**
     * Delete member from subscription.
     */
    public function destroyMember(Request $request, SubscriptionMember $member): RedirectResponse
    {
        if ($member->subscription->user_id !== $request->user()->id) {
            abort(403);
        }

        $member->delete();

        return redirect()->back()->with('success', 'Member removed successfully!');
    }

    /**
     * Record or update payment for a member & billing cycle.
     */
    public function logPayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subscription_member_id' => 'required|exists:subscription_members,id',
            'billing_year' => 'required|integer|min:2020|max:2030',
            'billing_month' => 'required|integer|min:1|max:12',
            'billing_cycle_label' => 'nullable|string',
            'status' => 'required|in:paid,pending,waived',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'payment_date' => 'nullable|date',
            'reference_no' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'proof_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'remove_proof' => 'nullable|boolean',
            'account_id' => 'nullable|exists:accounts,id',
            'auto_post_income' => 'nullable|boolean',
        ]);

        $member = SubscriptionMember::with('subscription')->findOrFail($validated['subscription_member_id']);
        if ($member->subscription->user_id !== $request->user()->id) {
            abort(403);
        }

        // Handle optional receipt image upload or removal
        $proofPath = null;
        $shouldRemoveProof = !empty($validated['remove_proof']);

        if ($request->hasFile('proof_image')) {
            $file = $request->file('proof_image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('proofs'), $filename);
            $proofPath = '/proofs/' . $filename;
        }

        DB::transaction(function () use ($request, $validated, $member, $proofPath, $shouldRemoveProof) {
            $payment = SubscriptionPayment::firstOrNew([
                'subscription_member_id' => $member->id,
                'billing_year' => $validated['billing_year'],
                'billing_month' => $validated['billing_month'],
            ]);

            $payment->billing_cycle_label = $validated['billing_cycle_label'] ?? ($validated['billing_month'] . '/' . $validated['billing_year']);
            $payment->due_date = $validated['due_date'] ?? null;
            $payment->payment_date = $validated['payment_date'] ?? date('Y-m-d');
            $payment->status = $validated['status'];
            $payment->amount = $validated['amount'];
            $payment->reference_no = $validated['reference_no'] ?? null;
            $payment->notes = $validated['notes'] ?? null;
            if ($proofPath) {
                $payment->proof_image_path = $proofPath;
            } elseif ($shouldRemoveProof) {
                $payment->proof_image_path = null;
            }

            // Auto post as income transaction to FinZ ledger if requested
            if (!empty($validated['auto_post_income']) && !empty($validated['account_id']) && $validated['status'] === 'paid') {
                $account = Account::where('user_id', $request->user()->id)->findOrFail($validated['account_id']);

                // Find default Income category or general category
                $category = Category::where(function ($q) use ($request) {
                    $q->whereNull('user_id')->orWhere('user_id', $request->user()->id);
                })->where('type', 'income')->first();

                $txNotes = "[Shared Sub] {$member->name} - {$member->subscription->name} ({$payment->billing_cycle_label})";

                $txData = [
                    'user_id' => $request->user()->id,
                    'account_id' => $account->id,
                    'category_id' => $category?->id,
                    'type' => 'income',
                    'amount' => $validated['amount'],
                    'date' => $payment->payment_date ?? date('Y-m-d'),
                    'notes' => $txNotes,
                ];

                if ($payment->transaction_id) {
                    $transaction = Transaction::find($payment->transaction_id);
                    if ($transaction) {
                        $this->ledgerService->updateTransaction($transaction, $txData);
                    } else {
                        $transaction = $this->ledgerService->createTransaction($txData);
                        $payment->transaction_id = $transaction->id;
                    }
                } else {
                    $transaction = $this->ledgerService->createTransaction($txData);
                    $payment->transaction_id = $transaction->id;
                }
            } else {
                // If auto_post_income is false or status is pending/waived, safely revert & delete any linked ledger transaction
                if ($payment->transaction_id) {
                    $transaction = Transaction::find($payment->transaction_id);
                    if ($transaction) {
                        $this->ledgerService->deleteTransaction($transaction);
                    }
                    $payment->transaction_id = null;
                }
            }

            $payment->save();
        });

        return redirect()->back()->with('success', 'Payment status updated successfully!');
    }

    /**
     * Log master subscription bill expense to FinZ ledger.
     */
    public function logMasterExpense(Request $request, Subscription $subscription): RedirectResponse
    {
        if ($subscription->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $account = Account::where('user_id', $request->user()->id)->findOrFail($validated['account_id']);

        $category = Category::where(function ($q) use ($request) {
            $q->whereNull('user_id')->orWhere('user_id', $request->user()->id);
        })->where('type', 'expense')->first();

        $txNotes = "[Master Subscription Bill] {$subscription->name}";
        if (!empty($validated['notes'])) {
            $txNotes .= " - " . $validated['notes'];
        }

        $this->ledgerService->createTransaction([
            'user_id' => $request->user()->id,
            'account_id' => $account->id,
            'category_id' => $category?->id,
            'type' => 'expense',
            'amount' => $validated['amount'],
            'date' => $validated['transaction_date'],
            'notes' => $txNotes,
        ]);

        return redirect()->back()->with('success', 'Master subscription expense logged to account!');
    }
}
