<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->string('share_token', 64)->nullable()->unique()->after('status');
        });

        Schema::create('receipt_session_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('receipt_item_id')->constrained()->cascadeOnDelete();
            $table->string('guest_name');
            $table->decimal('amount_paid', 12, 2)->default(0.00);
            $table->boolean('is_paid')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_session_claims');
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn('share_token');
        });
    }
};
