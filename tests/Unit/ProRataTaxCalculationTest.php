<?php

namespace Tests\Unit;

use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\User;
use App\Services\ReceiptOcrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProRataTaxCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_rata_tax_and_fee_calculation_strictly_follows_srs_formula(): void
    {
        $user = User::factory()->create();

        // Create receipt: Subtotal RM50.00, Tax (SST) RM5.00, Service Charge RM5.00 -> Total RM60.00
        $receipt = Receipt::create([
            'user_id' => $user->id,
            'merchant_name' => 'Nasi Kandar Pelita',
            'subtotal' => 50.00,
            'tax_amount' => 5.00,
            'service_charge_amount' => 5.00,
            'discount_amount' => 0.00,
            'total_amount' => 60.00,
            'status' => 'parsed',
        ]);

        $item1 = ReceiptItem::create([
            'receipt_id' => $receipt->id,
            'name' => 'Nasi Kandar',
            'unit_price' => 20.00,
            'quantity' => 1,
            'total_price' => 20.00,
        ]);

        $item2 = ReceiptItem::create([
            'receipt_id' => $receipt->id,
            'name' => 'Ayam Goreng',
            'unit_price' => 30.00,
            'quantity' => 1,
            'total_price' => 30.00,
        ]);

        $service = new ReceiptOcrService();

        // User claims only item1 (RM20.00 of RM50.00 = 40% ratio)
        $result = $service->calculateProRataSplit($receipt, [$item1->id]);

        $this->assertEquals(20.00, $result['claimed_subtotal']);
        $this->assertEquals(2.00, $result['tax_share']);
        $this->assertEquals(2.00, $result['service_charge_share']);
        $this->assertEquals(0.00, $result['discount_share']);
        $this->assertEquals(4.00, $result['total_tax_share']);
        $this->assertEquals(24.00, $result['final_total']);
        $this->assertEquals(40.0, $result['pro_rata_percentage']);
    }

    public function test_pro_rata_discount_deduction_calculates_correctly(): void
    {
        $user = User::factory()->create();

        // Create receipt with Subtotal RM100.00, Tax RM10.00, Discount RM20.00 -> Total RM90.00
        $receipt = Receipt::create([
            'user_id' => $user->id,
            'merchant_name' => 'Starbucks Coffee',
            'subtotal' => 100.00,
            'tax_amount' => 10.00,
            'service_charge_amount' => 0.00,
            'discount_amount' => 20.00, // RM20 promo discount
            'total_amount' => 90.00,
            'status' => 'parsed',
        ]);

        $item1 = ReceiptItem::create([
            'receipt_id' => $receipt->id,
            'name' => 'Caramel Macchiato',
            'unit_price' => 50.00,
            'quantity' => 1,
            'total_price' => 50.00,
        ]);

        $service = new ReceiptOcrService();

        // User claims item1 (RM50.00 of RM100.00 = 50% ratio)
        // Tax Share = 50% of RM10.00 = RM5.00
        // Discount Share = 50% of RM20.00 = RM10.00
        // Net Adjustment = RM5.00 - RM10.00 = -RM5.00
        // Final Claimed Total = RM50.00 - RM5.00 = RM45.00
        $result = $service->calculateProRataSplit($receipt, [$item1->id]);

        $this->assertEquals(50.00, $result['claimed_subtotal']);
        $this->assertEquals(5.00, $result['tax_share']);
        $this->assertEquals(10.00, $result['discount_share']);
        $this->assertEquals(-5.00, $result['total_tax_share']);
        $this->assertEquals(45.00, $result['final_total']);
    }
}
