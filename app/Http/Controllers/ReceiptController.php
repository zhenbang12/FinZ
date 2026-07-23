<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Receipt;
use App\Services\LedgerService;
use App\Services\ReceiptOcrService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptController extends Controller
{
    protected ReceiptOcrService $ocrService;
    protected LedgerService $ledgerService;

    public function __construct(ReceiptOcrService $ocrService, LedgerService $ledgerService)
    {
        $this->ocrService = $ocrService;
        $this->ledgerService = $ledgerService;
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $receipts = Receipt::where('user_id', $user->id)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();

        $accounts = Account::where('user_id', $user->id)->get();
        $categories = Category::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->get();

        return Inertia::render('Receipts/Scan', [
            'receipts' => $receipts,
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $receipt = $this->ocrService->parseReceipt($request->file('image'), $request->user()->id);

        return redirect()->route('receipts.show', $receipt->id)->with('success', 'Receipt scanned and parsed successfully.');
    }

    public function show(Request $request, Receipt $receipt): Response
    {
        if ($receipt->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized receipt access.');
        }

        $receipt->load('items');
        $accounts = Account::where('user_id', $request->user()->id)->get();
        $categories = Category::where(function ($q) use ($request) {
            $q->whereNull('user_id')->orWhere('user_id', $request->user()->id);
        })->get();

        return Inertia::render('Receipts/Show', [
            'receipt' => $receipt,
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }

    /**
     * Calculate user's specific total using strict pro-rata calculation (REQ-3.4).
     */
    public function calculateSplit(Request $request, Receipt $receipt)
    {
        if ($receipt->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'claimed_item_ids' => 'required|array',
            'claimed_item_ids.*' => 'integer|exists:receipt_items,id',
        ]);

        $splitResult = $this->ocrService->calculateProRataSplit($receipt, $validated['claimed_item_ids']);

        return response()->json($splitResult);
    }

    /**
     * Convert claimed total into a logged formal transaction expense (REQ-3.5).
     */
    public function saveClaimedExpense(Request $request, Receipt $receipt)
    {
        if ($receipt->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'claimed_item_ids' => 'required|array',
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'required|exists:categories,id',
            'notes' => 'nullable|string|max:255',
        ]);

        $splitResult = $this->ocrService->calculateProRataSplit($receipt, $validated['claimed_item_ids']);

        $transaction = $this->ledgerService->createTransaction([
            'user_id' => $request->user()->id,
            'type' => 'expense',
            'account_id' => $validated['account_id'],
            'category_id' => $validated['category_id'],
            'receipt_id' => $receipt->id,
            'amount' => $splitResult['final_total'],
            'date' => now(),
            'notes' => $validated['notes'] ?: ("SmartSplit Expense from " . ($receipt->merchant_name ?: 'Receipt')),
        ]);

        $receipt->update(['status' => 'claimed']);

        return redirect()->route('transactions.index')->with('success', 'SmartSplit expense of RM ' . number_format($splitResult['final_total'], 2) . ' logged successfully!');
    }
}
