<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\ReceiptItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ReceiptOcrService
{
    /**
     * Parse an uploaded receipt image strictly using Google Gemini AI Vision.
     */
    public function parseReceipt(UploadedFile $file, int $userId): Receipt
    {
        $path = $file->store('receipts', 'public');
        $fullPath = Storage::disk('public')->path($path);

        // Strictly run Google Gemini Vision API
        $parsedData = $this->parseStrictlyWithGoogleGemini($fullPath);

        $receipt = Receipt::create([
            'user_id' => $userId,
            'image_path' => $path,
            'merchant_name' => $parsedData['merchant_name'] ?? 'Extracted Merchant',
            'subtotal' => $parsedData['subtotal'] ?? 0.00,
            'tax_amount' => $parsedData['tax_amount'] ?? 0.00,
            'service_charge_amount' => $parsedData['service_charge_amount'] ?? 0.00,
            'discount_amount' => $parsedData['discount_amount'] ?? 0.00,
            'total_amount' => $parsedData['total_amount'] ?? 0.00,
            'raw_ocr_data' => array_merge($parsedData, [
                'ocr_engine' => 'Google Gemini AI Vision (' . ($parsedData['model_used'] ?? 'gemini-flash') . ')',
                'timestamp' => now()->toIso8601String(),
            ]),
            'status' => 'parsed',
        ]);

        if (!empty($parsedData['items']) && is_array($parsedData['items'])) {
            foreach ($parsedData['items'] as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $unitPrice = (float) ($item['unit_price'] ?? 0.00);
                $totalPrice = (float) ($item['total_price'] ?? ($unitPrice * $qty));

                if ($qty > 1) {
                    // Breakdown multi-quantity items e.g. x3 into 3 separate selectable line items
                    $individualPrice = round($unitPrice > 0 ? $unitPrice : ($totalPrice / $qty), 2);
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
                        'unit_price' => $unitPrice,
                        'quantity' => 1,
                        'total_price' => $totalPrice,
                    ]);
                }
            }
        }

        return $receipt->load('items');
    }

    /**
     * Calculate user's specific total using strict pro-rata tax, service charge, and discount distribution (REQ-3.4).
     */
    public function calculateProRataSplit(Receipt $receipt, array $claimedItemIds): array
    {
        $claimedItems = ReceiptItem::where('receipt_id', $receipt->id)
            ->whereIn('id', $claimedItemIds)
            ->get();

        $claimedSubtotal = (float) $claimedItems->sum('total_price');
        $receiptSubtotal = (float) ($receipt->subtotal > 0 ? $receipt->subtotal : $receipt->items->sum('total_price'));

        $taxAmount = (float) $receipt->tax_amount;
        $serviceChargeAmount = (float) $receipt->service_charge_amount;
        $discountAmount = (float) $receipt->discount_amount;

        if ($receiptSubtotal > 0) {
            $proRataRatio = $claimedSubtotal / $receiptSubtotal;
            $taxShare = round($taxAmount * $proRataRatio, 2);
            $serviceChargeShare = round($serviceChargeAmount * $proRataRatio, 2);
            $discountShare = round($discountAmount * $proRataRatio, 2);
            $totalTaxShare = $taxShare + $serviceChargeShare - $discountShare;
        } else {
            $proRataRatio = 0;
            $taxShare = 0.00;
            $serviceChargeShare = 0.00;
            $discountShare = 0.00;
            $totalTaxShare = 0.00;
        }

        $finalClaimedTotal = round($claimedSubtotal + $totalTaxShare, 2);

        return [
            'claimed_subtotal' => round($claimedSubtotal, 2),
            'tax_share' => $taxShare,
            'service_charge_share' => $serviceChargeShare,
            'discount_share' => $discountShare,
            'total_tax_share' => round($totalTaxShare, 2),
            'final_total' => $finalClaimedTotal,
            'pro_rata_percentage' => round($proRataRatio * 100, 1),
            'claimed_items' => $claimedItems,
        ];
    }

    /**
     * Strictly execute Google Gemini Flash AI Vision API.
     * Extracts merchant_name, subtotal, tax_amount, service_charge_amount, discount_amount, total_amount, and items.
     */
    protected function parseStrictlyWithGoogleGemini(string $fullPath): array
    {
        $apiKey = config('services.gemini.key') ?: env('GEMINI_API_KEY');

        if (empty($apiKey)) {
            throw new RuntimeException("GEMINI_API_KEY is missing. Please set GEMINI_API_KEY in your .env file.");
        }

        if (!file_exists($fullPath)) {
            throw new RuntimeException("Receipt file not found at: {$fullPath}");
        }

        $imageData = base64_encode(file_get_contents($fullPath));
        $mimeType = mime_content_type($fullPath) ?: 'image/jpeg';

        $prompt = 'Analyze this receipt image. Extract merchant_name, subtotal, tax_amount (SST/GST), service_charge_amount, discount_amount (vouchers/rebates/promos as positive number to deduct), total_amount, and line items (name, unit_price, quantity, total_price) in MYR currency. Return ONLY valid JSON matching schema: {"merchant_name": "...", "subtotal": 0.00, "tax_amount": 0.00, "service_charge_amount": 0.00, "discount_amount": 0.00, "total_amount": 0.00, "items": [{"name": "...", "unit_price": 0.00, "quantity": 1, "total_price": 0.00}]}';

        $candidateModels = ['gemini-3.6-flash', 'gemini-flash-latest', 'gemini-flash-lite-latest', 'gemini-2.0-flash'];
        $lastError = '';

        foreach ($candidateModels as $model) {
            try {
                $response = Http::timeout(20)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                    [
                                        'inline_data' => [
                                            'mime_type' => $mimeType,
                                            'data' => $imageData
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'response_mime_type' => 'application/json'
                        ]
                    ]);

                if ($response->successful()) {
                    $jsonContent = $response->json('candidates.0.content.parts.0.text');
                    $decoded = json_decode($jsonContent, true);

                    if ($decoded && is_array($decoded)) {
                        $decoded['model_used'] = $model;
                        return $decoded;
                    }
                } else {
                    $lastError = "Model {$model} error: " . ($response->json('error.message') ?: $response->body());
                }
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        Log::error('Google Gemini API Error: ' . $lastError);
        throw new RuntimeException("Google Gemini AI Vision API Call Failed: " . $lastError);
    }
}
