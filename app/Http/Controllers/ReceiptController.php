<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Receipt;
use App\Models\ReceiptSessionClaim;
use App\Services\LedgerService;
use App\Services\ReceiptOcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
            ->with(['items', 'sessionClaims'])
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

        $receipt->load(['items.sessionClaims', 'sessionClaims']);
        $accounts = Account::where('user_id', $request->user()->id)->get();
        $categories = Category::where(function ($q) use ($request) {
            $q->whereNull('user_id')->orWhere('user_id', $request->user()->id);
        })->get();

        $shareUrl = $receipt->share_token ? url("/receipts/session/{$receipt->share_token}") : null;

        return Inertia::render('Receipts/Show', [
            'receipt' => $receipt,
            'accounts' => $accounts,
            'categories' => $categories,
            'shareUrl' => $shareUrl,
        ]);
    }

    /**
     * Generate or fetch the live group session share link for a receipt.
     */
    public function createSession(Request $request, Receipt $receipt)
    {
        if ($receipt->user_id !== $request->user()->id) {
            abort(403);
        }

        if (!$receipt->share_token) {
            $receipt->update([
                'share_token' => Str::random(12),
            ]);
        }

        $shareUrl = url("/receipts/session/{$receipt->share_token}");

        return redirect()->back()->with('success', "Group session created! Share URL: {$shareUrl}");
    }

    /**
     * Public Guest Route: Interactive Group Session Splitting view.
     */
    public function showGroupSession(string $token): Response
    {
        $receipt = Receipt::where('share_token', $token)
            ->with(['items.sessionClaims', 'sessionClaims', 'user'])
            ->firstOrFail();

        $sessionUrl = url("/receipts/session/{$token}");

        return Inertia::render('Receipts/GroupSession', [
            'receipt' => $receipt,
            'sessionUrl' => $sessionUrl,
        ]);
    }

    /**
     * Public Guest Route: Claim items and mark as paid.
     */
    public function claimGroupSessionItems(string $token, Request $request)
    {
        $receipt = Receipt::where('share_token', $token)->with('items')->firstOrFail();

        $validated = $request->validate([
            'guest_name' => 'required|string|max:100',
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'integer|exists:receipt_items,id',
        ]);

        $guestName = trim($validated['guest_name']);
        $itemIds = $validated['item_ids'];

        // Calculate pro-rata split for these items
        $splitResult = $this->ocrService->calculateProRataSplit($receipt, $itemIds);
        $totalForGuest = $splitResult['final_total'];

        // Record session claim for each item
        foreach ($itemIds as $itemId) {
            ReceiptSessionClaim::where('receipt_item_id', $itemId)->delete();

            $itemObj = $receipt->items->firstWhere('id', $itemId);
            $itemSubtotal = $itemObj ? (float) $itemObj->total_price : 0;
            $itemProRataShare = ($splitResult['claimed_subtotal'] > 0)
                ? ($itemSubtotal / $splitResult['claimed_subtotal']) * $totalForGuest
                : $itemSubtotal;

            ReceiptSessionClaim::create([
                'receipt_id' => $receipt->id,
                'receipt_item_id' => $itemId,
                'guest_name' => $guestName,
                'amount_paid' => round($itemProRataShare, 2),
                'is_paid' => true,
            ]);
        }

        return redirect()->back()->with('success', "{$guestName} successfully claimed " . count($itemIds) . " items (Total: RM " . number_format($totalForGuest, 2) . ")!");
    }

    /**
     * Public Guest Route: Remove/Undo a claim.
     */
    public function deleteGroupSessionClaim(string $token, ReceiptSessionClaim $claim)
    {
        $receipt = Receipt::where('share_token', $token)->firstOrFail();

        if ($claim->receipt_id !== $receipt->id) {
            abort(403);
        }

        $guestName = $claim->guest_name;
        $claim->delete();

        return redirect()->back()->with('success', "Item claim for {$guestName} removed.");
    }

    /**
     * Receipt Owner Action: Undo/Reset any claim on a receipt item.
     */
    public function undoOwnerClaim(Request $request, Receipt $receipt, ReceiptSessionClaim $claim)
    {
        if ($receipt->user_id !== $request->user()->id || $claim->receipt_id !== $receipt->id) {
            abort(403);
        }

        $guestName = $claim->guest_name;
        $claim->delete();

        return redirect()->back()->with('success', "Claim by {$guestName} was reset and is available again.");
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

        if ($receipt->status === 'claimed') {
            return redirect()->back()->with('error', 'This receipt expense has already been logged to the ledger.');
        }

        $validated = $request->validate([
            'claimed_item_ids' => 'required|array',
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('user_id', $request->user()->id)],
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

    /**
     * Delete a scanned receipt and its associated files & claims.
     */
    public function destroy(Request $request, Receipt $receipt)
    {
        if ($receipt->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($receipt->image_path) {
            $relativePath = str_replace('/storage/', '', $receipt->image_path);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($relativePath);
        }

        $receipt->delete();

        return redirect()->route('receipts.index')->with('success', 'Receipt deleted successfully.');
    }
}
