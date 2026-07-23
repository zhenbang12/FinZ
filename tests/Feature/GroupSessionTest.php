<?php

namespace Tests\Feature;

use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_group_session_link_and_guest_can_claim_items(): void
    {
        $user = User::create([
            'name' => 'Owner User',
            'email' => 'owner@finz.app',
            'password' => bcrypt('password'),
        ]);

        $receipt = Receipt::create([
            'user_id' => $user->id,
            'merchant_name' => 'Nasi Kandar Pelita',
            'subtotal' => 30.00,
            'tax_amount' => 1.80, // 6% SST
            'service_charge_amount' => 3.00, // 10% Service Charge
            'total_amount' => 34.80,
            'status' => 'parsed',
        ]);

        $item1 = ReceiptItem::create([
            'receipt_id' => $receipt->id,
            'name' => 'Nasi Kandar Ayam',
            'unit_price' => 15.00,
            'quantity' => 1,
            'total_price' => 15.00,
        ]);

        $item2 = ReceiptItem::create([
            'receipt_id' => $receipt->id,
            'name' => 'Teh Tarik Ais',
            'unit_price' => 15.00,
            'quantity' => 1,
            'total_price' => 15.00,
        ]);

        // 1. Owner generates group session link
        $sessionResponse = $this->actingAs($user)->post("/receipts/{$receipt->id}/create-session");
        $sessionResponse->assertRedirect();

        $receipt->refresh();
        $this->assertNotNull($receipt->share_token);

        // 2. Guest visits public session link (no authentication required)
        $guestView = $this->get("/receipts/session/{$receipt->share_token}");
        $guestView->assertStatus(200);

        // 3. Guest claims item1 (Nasi Kandar Ayam = RM 15.00 + 50% pro-rata tax/service fee = RM 17.40 total)
        $claimResponse = $this->post("/receipts/session/{$receipt->share_token}/claim", [
            'guest_name' => 'Chloe',
            'item_ids' => [$item1->id],
        ]);

        $claimResponse->assertRedirect();

        // 4. Verify item1 is claimed by Chloe with pro-rata amount of RM 17.40
        $this->assertDatabaseHas('receipt_session_claims', [
            'receipt_id' => $receipt->id,
            'receipt_item_id' => $item1->id,
            'guest_name' => 'Chloe',
            'amount_paid' => 17.40,
            'is_paid' => true,
        ]);
    }
}
