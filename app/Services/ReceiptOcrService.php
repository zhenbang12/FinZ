<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\ReceiptItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ReceiptOcrService
{
    /**
     * Parse receipt image using strict Google Gemini 3.6 Flash / Flash Latest AI Vision API.
     */
    public function parseReceipt(UploadedFile $file, int $userId): Receipt
    {
        $path = $file->store('receipts', 'public');
        $fullPath = storage_path("app/public/{$path}");

        $ocrData = $this->parseStrictlyWithGoogleGemini($fullPath);

        // Create formal Receipt Record
        $receipt = Receipt::create([
            'user_id' => $userId,
            'image_path' => $path,
            'merchant_name' => $ocrData['merchant_name'] ?? 'Unknown Merchant',
            'subtotal' => (float) ($ocrData['subtotal'] ?? 0.00),
            'tax_amount' => (float) ($ocrData['tax_amount'] ?? 0.00),
            'service_charge_amount' => (float) ($ocrData['service_charge_amount'] ?? 0.00),
            'discount_amount' => (float) ($ocrData['discount_amount'] ?? 0.00),
            'total_amount' => (float) ($ocrData['total_amount'] ?? 0.00),
            'raw_ocr_data' => $ocrData,
            'status' => 'parsed',
        ]);

        // Save Line Items
        if (!empty($ocrData['items']) && is_array($ocrData['items'])) {
            foreach ($ocrData['items'] as $item) {
                $qty = isset($item['quantity']) ? (int) $item['quantity'] : 1;
                $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : 0.00;
                $totalPrice = isset($item['total_price']) ? (float) $item['total_price'] : ($unitPrice * $qty);

                // Check for multi-quantity item breakdown directive (SRS REQ-3.3)
                if ($qty > 1 && $totalPrice > 0) {
                    $individualPrice = round($totalPrice / $qty, 2);
                    for ($i = 1; $i <= $qty; $i++) {
                        ReceiptItem::create([
                            'receipt_id' => $receipt->id,
                            'name' => ($item['name'] ?? 'Item') . " (#{$i} of {$qty})",
                            'unit_price' => $individualPrice,
                            'quantity' => 1,
                            'total_price' => $individualPrice,
                        ]);
                    }
                } else {
                    ReceiptItem::create([
                        'receipt_id' => $receipt->id,
                        'name' => $item['name'] ?? 'Item',
                        'unit_price' => $unitPrice > 0 ? $unitPrice : $totalPrice,
                        'quantity' => 1,
                        'total_price' => $totalPrice,
                    ]);
                }
            }
        }

        return $receipt;
    }

    /**
     * Calculate user's specific total using strict pro-rata calculation (SRS REQ-3.4 & REQ-3.6).
     * Handles both Tax-Inclusive (e.g. SST built into prices) and Tax-Exclusive (tax added on top) shops.
     */
    public function calculateProRataSplit(Receipt $receipt, array $claimedItemIds): array
    {
        $claimedItems = ReceiptItem::where('receipt_id', $receipt->id)
            ->whereIn('id', $claimedItemIds)
            ->get();

        $claimedSubtotal = (float) $claimedItems->sum('total_price');

        $allItemsSubtotal = (float) ReceiptItem::where('receipt_id', $receipt->id)->sum('total_price');
        $receiptSubtotal = (float) $receipt->subtotal > 0 ? (float) $receipt->subtotal : $allItemsSubtotal;

        if ($receiptSubtotal <= 0) {
            $receiptSubtotal = max(0.01, $claimedSubtotal);
        }

        // Pro-Rata Ratio
        $proRataRatio = $claimedSubtotal / $receiptSubtotal;

        // Extract raw OCR extra details
        $rawOcr = $receipt->raw_ocr_data ?? [];
        $roundingAmount = (float) ($rawOcr['rounding_amount'] ?? $rawOcr['rounding'] ?? 0.00);

        $taxAmount = (float) $receipt->tax_amount;
        $serviceAmount = (float) $receipt->service_charge_amount;
        $discountAmount = (float) $receipt->discount_amount;
        $totalAmount = (float) $receipt->total_amount;

        // Determine if Tax is Inclusive vs Exclusive
        // Inclusive formula: sum of item prices - discount + service + rounding == total
        // Exclusive formula: sum of item prices - discount + tax + service + rounding == total
        $isTaxInclusive = false;
        if (isset($rawOcr['is_tax_inclusive'])) {
            $isTaxInclusive = (bool) $rawOcr['is_tax_inclusive'];
        } else if ($totalAmount > 0) {
            $exclusiveEst = $allItemsSubtotal - $discountAmount + $taxAmount + $serviceAmount + $roundingAmount;
            $inclusiveEst = $allItemsSubtotal - $discountAmount + $serviceAmount + $roundingAmount;

            $diffInclusive = abs($inclusiveEst - $totalAmount);
            $diffExclusive = abs($exclusiveEst - $totalAmount);

            // If inclusive formula is closer to receipt total than exclusive formula, tax is built into item prices
            if ($diffInclusive < $diffExclusive && $diffInclusive <= 0.75) {
                $isTaxInclusive = true;
            }
        }

        // Pro-Rata Shares
        $taxShare = $taxAmount * $proRataRatio;
        $serviceChargeShare = $serviceAmount * $proRataRatio;
        $discountShare = $discountAmount * $proRataRatio;
        $roundingShare = $roundingAmount * $proRataRatio;

        // Final User Total Math
        if ($isTaxInclusive) {
            // Tax is already included in item prices; do NOT add taxShare on top
            $finalTotal = $claimedSubtotal - $discountShare + $serviceChargeShare + $roundingShare;
            $netAdjustment = -$discountShare + $serviceChargeShare + $roundingShare;
        } else {
            // Tax is exclusive; add taxShare on top
            $finalTotal = $claimedSubtotal + $taxShare + $serviceChargeShare - $discountShare + $roundingShare;
            $netAdjustment = $taxShare + $serviceChargeShare - $discountShare + $roundingShare;
        }

        return [
            'claimed_subtotal' => round($claimedSubtotal, 2),
            'pro_rata_ratio' => round($proRataRatio, 4),
            'pro_rata_percentage' => round($proRataRatio * 100, 1),
            'tax_share' => round($taxShare, 2),
            'service_charge_share' => round($serviceChargeShare, 2),
            'discount_share' => round($discountShare, 2),
            'rounding_share' => round($roundingShare, 2),
            'is_tax_inclusive' => $isTaxInclusive,
            'total_tax_share' => round($netAdjustment, 2),
            'net_adjustment' => round($netAdjustment, 2),
            'final_total' => max(0, round($finalTotal, 2)),
        ];
    }

    /**
     * Strictly execute Google Gemini Flash AI Vision API.
     */
    protected function parseStrictlyWithGoogleGemini(string $fullPath): array
    {
        $apiKey = config('services.gemini.key')
            ?: env('GEMINI_API_KEY')
            ?: getenv('GEMINI_API_KEY');

        if (empty($apiKey)) {
            throw new RuntimeException("GEMINI_API_KEY is missing. Please set GEMINI_API_KEY in your .env file.");
        }

        if (!file_exists($fullPath)) {
            throw new RuntimeException("Receipt file not found at: {$fullPath}");
        }

        $imageData = base64_encode(file_get_contents($fullPath));
        $mimeType = mime_content_type($fullPath) ?: 'image/jpeg';

        $prompt = <<<PROMPT
You are an expert Malaysian receipt OCR engine. Analyze this receipt image and extract data into strict JSON format:
{
  "merchant_name": "string",
  "subtotal": float,
  "tax_amount": float (SST / Service Tax),
  "service_charge_amount": float (Service Fee / Service Charge),
  "discount_amount": float (Total Discount / Voucher / Staff Discount / Rebate),
  "rounding_amount": float (Rounding adjustment like -0.02 or +0.01 if present, else 0.00),
  "total_amount": float (Final Net Total / Cash Amount paid),
  "is_tax_inclusive": boolean (true if prices include SST/tax like 'PRICE SUB TO SERV TAX', false if SST is added on top below subtotal),
  "items": [
    {
      "name": "string",
      "quantity": int,
      "unit_price": float,
      "total_price": float
    }
  ]
}

Rules:
1. Extract line items strictly (purchased food/drinks/products). Do NOT include discount lines or rounding lines inside "items" array; put discounts under "discount_amount" and rounding under "rounding_amount".
2. If line item quantity > 1, extract exact unit_price and total_price.
3. If receipt says "PRICE SUB TO SERV TAX" or prices already contain tax, set is_tax_inclusive = true.
4. All numeric amounts in MYR (RM).
5. Return raw valid JSON only without markdown formatting.
PROMPT;

        // Try gemini-2.5-flash then fallback to gemini-flash-latest / gemini-1.5-flash
        $models = ['gemini-2.5-flash', 'gemini-flash-latest', 'gemini-1.5-flash'];
        $response = null;
        $lastException = null;

        foreach ($models as $model) {
            try {
                $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

                $res = Http::timeout(30)->post($endpoint, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $imageData,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

                if ($res->successful()) {
                    $response = $res;
                    break;
                }
            } catch (\Exception $e) {
                $lastException = $e;
            }
        }

        if (!$response || !$response->successful()) {
            $errText = $response ? $response->body() : ($lastException ? $lastException->getMessage() : 'Unknown error');
            Log::error("Google Gemini AI Vision API Call Failed: " . $errText);
            throw new RuntimeException("Google Gemini AI Vision API Call Failed: " . $errText);
        }

        $json = $response->json();
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        // Clean markdown backticks if returned
        $text = preg_replace('/^```json\s*/i', '', $text);
        $text = preg_replace('/^```\s*/i', '', $text);
        $text = preg_replace('/```$/', '', trim($text));

        $data = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            Log::warning("Gemini OCR response failed JSON parsing. Raw: " . $text);
            return [
                'merchant_name' => 'Scanned Receipt',
                'subtotal' => 0.00,
                'tax_amount' => 0.00,
                'service_charge_amount' => 0.00,
                'discount_amount' => 0.00,
                'total_amount' => 0.00,
                'items' => [],
                'ocr_engine' => 'Google Gemini AI Vision',
            ];
        }

        // Clean items array: remove negative items (discounts) and add to discount_amount if not already captured
        if (!empty($data['items']) && is_array($data['items'])) {
            $cleanItems = [];
            $extraDiscount = 0.0;
            foreach ($data['items'] as $it) {
                $itemPrice = (float) ($it['total_price'] ?? 0);
                if ($itemPrice < 0) {
                    $extraDiscount += abs($itemPrice);
                } else if (preg_match('/disc|voucher|rebate|rounding/i', $it['name'] ?? '')) {
                    $extraDiscount += abs($itemPrice);
                } else {
                    $cleanItems[] = $it;
                }
            }
            $data['items'] = $cleanItems;
            if ($extraDiscount > 0 && empty($data['discount_amount'])) {
                $data['discount_amount'] = $extraDiscount;
            }
        }

        $data['ocr_engine'] = 'Google Gemini AI Vision';
        return $data;
    }
}
